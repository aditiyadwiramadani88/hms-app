<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    public function getTodayStatus(int $employeeId): ?Attendance
    {
        $today = $this->getTodayDate();

        return Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->first();
    }

    public function checkIn(int $employeeId, string $photoBase64, float $lat, float $lng): Attendance
    {
        $hotelId = active_hotel_id();
        $today = $this->getTodayDate();
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');
        $now = now();

        // GUARD: Check if there's an unclosed overnight shift from yesterday
        // If employee has yesterday's attendance with check-in but no check-out,
        // AND that shift is overnight (end_time < start_time), AND current time is before shift end,
        // THEN they should check-out yesterday's shift first, not create a new check-in.
        $unclosedYesterday = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $yesterday)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->with('shift')
            ->first();

        if ($unclosedYesterday && $unclosedYesterday->shift) {
            $shift = $unclosedYesterday->shift;
            // Overnight shift: end_time < start_time (e.g. 16:00-07:00)
            if ($shift->end_time && $shift->start_time && $shift->end_time < $shift->start_time) {
                $shiftEndToday = Carbon::parse($today . ' ' . $shift->end_time);
                // If current time is before shift end (still within yesterday's overnight shift)
                if ($now->lt($shiftEndToday)) {
                    throw new \RuntimeException('Shift kemarin (' . $shift->name . ') belum checkout. Silakan checkout terlebih dahulu sebelum check-in baru.');
                }
            }
        }

        // Try today's schedule first (exclude OFF/LIBUR shifts)
        $schedule = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('schedule_date', $today)
            ->with('shift')
            ->whereHas('shift', function ($q) {
                $q->where('is_off', false);
            })
            ->first();

        // If no valid schedule today, check yesterday's schedule (for cross-midnight night shifts)
        // Night shift example: schedule_date = yesterday, shift 22:00-06:00
        if (!$schedule || !$schedule->shift) {
            $yesterdaySchedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $yesterday)
                ->with('shift')
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->first();

            if ($yesterdaySchedule && $yesterdaySchedule->shift) {
                // Check if this is an overnight shift (end_time < start_time means crosses midnight)
                $shift = $yesterdaySchedule->shift;
                if ($shift->end_time < $shift->start_time) {
                    $schedule = $yesterdaySchedule;
                    $today = $yesterday; // Use yesterday as the attendance date for this overnight shift
                }
            }
        }

        if (!$schedule || !$schedule->shift) {
            throw new \RuntimeException('Anda tidak memiliki jadwal shift hari ini.');
        }

        // Validate check-in time is within reasonable range of shift
        // Allow check-in max 2 hours before shift start, or anytime during shift
        $shift = $schedule->shift;
        $shiftStartTime = Carbon::parse($today . ' ' . $shift->start_time);
        $shiftEndTime = Carbon::parse($today . ' ' . $shift->end_time);
        
        // Handle overnight shift end time
        if ($shiftEndTime <= $shiftStartTime) {
            $shiftEndTime->addDay();
        }

        $earliestCheckin = $shiftStartTime->copy()->subHours(2);
        
        // For overnight shifts where we're checking in after midnight (using yesterday's schedule)
        // the $now will be valid if it's before shift end
        $isWithinShiftWindow = $now->between($earliestCheckin, $shiftEndTime);
        
        if (!$isWithinShiftWindow) {
            $startFormatted = Carbon::parse($shift->start_time)->format('H:i');
            throw new \RuntimeException("Belum bisa check-in. Shift {$shift->name} dimulai jam {$startFormatted}. Check-in dibuka mulai " . $earliestCheckin->format('H:i') . ".");
        }

        $existing = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->first();

        if ($existing) {
            throw new \RuntimeException('Anda sudah melakukan check-in hari ini.');
        }

        $location = $this->geofenceService->validateLocation($hotelId, $lat, $lng);

        $hasActiveLocations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveLocations && $location === null) {
            $nearest = $this->geofenceService->getNearestWithDistance($hotelId, $lat, $lng);
            $distanceInfo = $nearest ? $nearest['distance'] . 'm dari ' . $nearest['location']->name : '';
            throw new \RuntimeException('Anda berada di luar area absensi (' . $distanceInfo . '). Silakan mendekat ke lokasi hotel untuk melakukan absensi.');
        }

        $photoPath = $this->savePhoto($photoBase64, $hotelId, $employeeId, $today, 'checkin');

        $shift = $schedule->shift;
        $lateMinutes = $this->calculateLateMinutes($now, $shift, $today);

        $status = $lateMinutes > 0 ? 'late' : 'present';

        $attendance = Attendance::updateOrCreate(
            [
                'hotel_id' => $hotelId,
                'employee_id' => $employeeId,
                'attendance_date' => $today,
            ],
            [
                'employee_schedule_id' => $schedule->id,
                'shift_id' => $shift->id,
                'check_in_time' => $now,
                'check_in_photo' => $photoPath,
                'check_in_latitude' => $lat,
                'check_in_longitude' => $lng,
                'check_in_location_id' => $location?->id,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ]
        );

        return $attendance;
    }

    public function checkOut(int $employeeId, string $photoBase64, float $lat, float $lng): Attendance
    {
        $hotelId = active_hotel_id();
        $today = $this->getTodayDate();
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');
        $now = now();

        // Try to find open attendance for today first
        // Must have check_in_time to avoid picking up 'absent' placeholders
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        // If not found, check yesterday (for overnight shifts)
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $yesterday)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
        }

        if (!$attendance) {
            throw new \RuntimeException('Anda belum melakukan check-in atau sudah melakukan check-out hari ini.');
        }

        $location = $this->geofenceService->validateLocation($hotelId, $lat, $lng);

        $hasActiveLocations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveLocations && $location === null) {
            $nearest = $this->geofenceService->getNearestWithDistance($hotelId, $lat, $lng);
            $distanceInfo = $nearest ? $nearest['distance'] . 'm dari ' . $nearest['location']->name : '';
            throw new \RuntimeException('Anda berada di luar area absensi (' . $distanceInfo . '). Silakan mendekat ke lokasi hotel untuk melakukan check-out.');
        }

        // Use the actual attendance date for photo storage
        $photoPath = $this->savePhoto($photoBase64, $hotelId, $employeeId, $attendance->attendance_date->format('Y-m-d'), 'checkout');

        $shift = $attendance->shift;

        if ($shift) {
            $earlyLeaveMinutes = $this->calculateEarlyLeaveMinutes($now, $shift, $attendance->attendance_date->format('Y-m-d'));
            $overtimeMinutes = 0;

            if ($attendance->schedule && $attendance->schedule->is_overtime) {
                $overtimeMinutes = $this->calculateOvertimeMinutes($now, $shift, $attendance->attendance_date->format('Y-m-d'));
            }

            $newStatus = $attendance->status;
            if ($earlyLeaveMinutes > 0) {
                if ($newStatus === 'late') {
                    $newStatus = 'late_and_early_leave';
                } else {
                    $newStatus = 'early_leave';
                }
            } else {
                // If not early, ensure status is not 'early_leave' (relevant if it was 'absent' before check-in update)
                if ($newStatus === 'early_leave' || $newStatus === 'absent') {
                    $newStatus = $attendance->late_minutes > 0 ? 'late' : 'present';
                } elseif ($newStatus === 'late_and_early_leave') {
                    $newStatus = 'late';
                }
            }

            $attendance->update([
                'check_out_time' => $now,
                'check_out_photo' => $photoPath,
                'check_out_latitude' => $lat,
                'check_out_longitude' => $lng,
                'check_out_location_id' => $location?->id,
                'status' => $newStatus,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ]);
        } else {
            $attendance->update([
                'check_out_time' => $now,
                'check_out_photo' => $photoPath,
                'check_out_latitude' => $lat,
                'check_out_longitude' => $lng,
                'check_out_location_id' => $location?->id,
            ]);
        }

        return $attendance->fresh();
    }

    public function startBreak(int $employeeId, string $photoBase64, float $lat, float $lng): Attendance
    {
        $hotelId = active_hotel_id();
        $today = $this->getTodayDate();
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');
        $now = now();

        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $yesterday)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
        }

        if (!$attendance) {
            throw new \RuntimeException('Anda belum melakukan check-in.');
        }

        if ($attendance->break_start_time) {
            throw new \RuntimeException('Anda sudah mulai istirahat.');
        }

        $location = $this->geofenceService->validateLocation($hotelId, $lat, $lng);
        $hasActiveLocations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveLocations && $location === null) {
            $nearest = $this->geofenceService->getNearestWithDistance($hotelId, $lat, $lng);
            $distanceInfo = $nearest ? $nearest['distance'] . 'm dari ' . $nearest['location']->name : '';
            throw new \RuntimeException('Anda berada di luar area absensi (' . $distanceInfo . '). Silakan mendekat ke lokasi hotel untuk mencatat istirahat.');
        }

        $attendance->update([
            'break_start_time' => $now,
        ]);

        return $attendance->fresh();
    }

    public function endBreak(int $employeeId, string $photoBase64, float $lat, float $lng): Attendance
    {
        $hotelId = active_hotel_id();
        $today = $this->getTodayDate();
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');
        $now = now();

        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $yesterday)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
        }

        if (!$attendance) {
            throw new \RuntimeException('Anda belum melakukan check-in.');
        }

        if (!$attendance->break_start_time) {
            throw new \RuntimeException('Anda belum mulai istirahat.');
        }

        if ($attendance->break_end_time) {
            throw new \RuntimeException('Anda sudah selesai istirahat.');
        }

        $location = $this->geofenceService->validateLocation($hotelId, $lat, $lng);
        $hasActiveLocations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveLocations && $location === null) {
            $nearest = $this->geofenceService->getNearestWithDistance($hotelId, $lat, $lng);
            $distanceInfo = $nearest ? $nearest['distance'] . 'm dari ' . $nearest['location']->name : '';
            throw new \RuntimeException('Anda berada di luar area absensi (' . $distanceInfo . '). Silakan mendekat ke lokasi hotel untuk mencatat selesai istirahat.');
        }

        $attendance->update([
            'break_end_time' => $now,
        ]);

        return $attendance->fresh();
    }

    public function markAbsentees(string $date): int
    {
        $hotelId = active_hotel_id();
        $count = 0;

        $existingIds = Attendance::where('attendance_date', $date)
            ->pluck('employee_id')
            ->toArray();

        $schedules = EmployeeSchedule::where('schedule_date', $date)
            ->whereHas('shift', function ($q) {
                $q->where('is_off', false);
            })
            ->whereNotIn('employee_id', $existingIds)
            ->get();

        foreach ($schedules as $schedule) {
            try {
                Attendance::create([
                    'hotel_id' => $schedule->hotel_id,
                    'employee_id' => $schedule->employee_id,
                    'employee_schedule_id' => $schedule->id,
                    'shift_id' => $schedule->shift_id,
                    'attendance_date' => $date,
                    'status' => 'absent',
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to mark absentee: ' . $e->getMessage(), [
                    'employee_id' => $schedule->employee_id,
                    'date' => $date,
                ]);
            }
        }

        return $count;
    }

    protected function savePhoto(string $base64, int $hotelId, int $employeeId, string $date, string $type): string
    {
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($base64);

        if ($imageData === false) {
            throw new \RuntimeException('Foto tidak valid.');
        }

        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            throw new \RuntimeException('Foto tidak dapat diproses.');
        }

        $path = "attendance/{$hotelId}/{$employeeId}/{$date}_{$type}.jpg";
        $directory = dirname(storage_path("app/public/{$path}"));

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        ob_start();
        imagejpeg($image, null, 85);
        $compressed = ob_get_clean();
        imagedestroy($image);

        // Compress to max 500KB
        $quality = 85;
        $compressedData = $this->compressToSize($imageData, 512000);

        Storage::disk('public')->put($path, $compressedData);

        return $path;
    }

    protected function compressToSize(string $data, int $maxSize): string
    {
        $source = @imagecreatefromstring($data);
        if ($source === false) {
            return $data;
        }

        $quality = 80;
        $output = null;

        while ($quality >= 20) {
            ob_start();
            imagejpeg($source, null, $quality);
            $output = ob_get_clean();

            if (strlen($output) <= $maxSize) {
                break;
            }

            $quality -= 10;
        }

        imagedestroy($source);

        return $output ?: $data;
    }

    protected function calculateLateMinutes(Carbon $checkInTime, Shift $shift, string $attendanceDate): int
    {
        $shiftStart = Carbon::parse($attendanceDate . ' ' . $shift->start_time);

        $toleranceMinutes = 5;
        $diffMinutes = $shiftStart->diffInMinutes($checkInTime, false);

        if ($diffMinutes > $toleranceMinutes) {
            return (int) floor($diffMinutes);
        }

        return 0;
    }

    protected function calculateEarlyLeaveMinutes(Carbon $checkOutTime, Shift $shift, string $attendanceDate): int
    {
        $endTime = $shift->end_time_2 ?: $shift->end_time;
        $shiftEnd = Carbon::parse($attendanceDate . ' ' . $endTime);
        $shiftStart = Carbon::parse($attendanceDate . ' ' . $shift->start_time);

        // Handle overnight shifts
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }

        $toleranceMinutes = 5;
        $diffMinutes = $checkOutTime->diffInMinutes($shiftEnd, false);

        if ($diffMinutes > $toleranceMinutes) {
            return (int) floor($diffMinutes);
        }

        return 0;
    }

    protected function calculateOvertimeMinutes(Carbon $checkOutTime, Shift $shift, string $attendanceDate): int
    {
        $endTime = $shift->end_time_2 ?: $shift->end_time;
        $shiftEnd = Carbon::parse($attendanceDate . ' ' . $endTime);
        $shiftStart = Carbon::parse($attendanceDate . ' ' . $shift->start_time);

        // Handle overnight shifts
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }

        $diffMinutes = $shiftEnd->diffInMinutes($checkOutTime, false);

        if ($diffMinutes > 0) {
            return (int) floor($diffMinutes);
        }

        return 0;
    }

    protected function getTodayDate(): string
    {
        return now()->format('Y-m-d');
    }
}
