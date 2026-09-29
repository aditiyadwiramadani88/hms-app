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
        $unclosedYesterday = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $yesterday)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->with(['shift', 'schedule.shift'])
            ->first();

        if ($unclosedYesterday) {
            $shift = $unclosedYesterday->shift ?? $unclosedYesterday->schedule?->shift;
            $hoursSince = $unclosedYesterday->check_in_time ? $unclosedYesterday->check_in_time->diffInHours($now) : 0;

            // Check if user has an active schedule starting now or today
            $todayScheduleCheck = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $today)
                ->with('shift')
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->first();

            $isNextShiftTime = false;
            if ($todayScheduleCheck && $todayScheduleCheck->shift) {
                $shiftStart = Carbon::parse($today . ' ' . $todayScheduleCheck->shift->start_time)->subHours(2);
                $shiftEnd = Carbon::parse($today . ' ' . $todayScheduleCheck->shift->end_time);
                if ($shiftEnd <= $shiftStart) {
                    $shiftEnd->addDay();
                }
                if ($now->between($shiftStart, $shiftEnd)) {
                    $isNextShiftTime = true;
                }
            }

            // Option 2: If the next shift time HAS arrived (or > 18 hours), auto-close yesterday's unclosed shift
            // so the employee is NEVER blocked from checking in to their new shift!
            if ($isNextShiftTime || $hoursSince > 18) {
                $endTime = $shift ? ($shift->end_time_2 ?: $shift->end_time) : null;
                $autoOutTime = $endTime ? Carbon::parse($yesterday . ' ' . $endTime) : $unclosedYesterday->check_in_time->copy()->addHours(8);
                if ($autoOutTime <= $unclosedYesterday->check_in_time) {
                    $autoOutTime->addDay();
                }
                $unclosedYesterday->update([
                    'check_out_time' => $autoOutTime,
                    'notes' => trim(($unclosedYesterday->notes ?? '') . ' [Auto-closed by next shift check-in]'),
                ]);
            } else {
                $shiftName = $shift ? $shift->name : 'Shift Kemarin';
                throw new \RuntimeException("Shift kemarin ({$shiftName}) belum checkout. Silakan lakukan Check-Out terlebih dahulu.");
            }
        }

        // If current time is early morning (00:00 - 08:00), check if checking in for yesterday's overnight shift
        $schedule = null;
        if ($now->hour < 8) {
            $yesterdaySchedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $yesterday)
                ->with('shift')
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->first();

            if ($yesterdaySchedule && $yesterdaySchedule->shift) {
                $shift = $yesterdaySchedule->shift;
                if ($shift->end_time < $shift->start_time) {
                    $yesterdayCheckedIn = Attendance::where('employee_id', $employeeId)
                        ->where('attendance_date', $yesterday)
                        ->whereNotNull('check_in_time')
                        ->exists();

                    if (!$yesterdayCheckedIn) {
                        $shiftEnd = Carbon::parse($today . ' ' . $shift->end_time)->addHours(2);
                        if ($now->lte($shiftEnd)) {
                            $schedule = $yesterdaySchedule;
                            $today = $yesterday;
                        }
                    }
                }
            }
        }

        if (!$schedule) {
            // Try today's schedule first (exclude OFF/LIBUR shifts)
            $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $today)
                ->with('shift')
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->first();

            // If no valid schedule today, check yesterday's schedule (for cross-midnight night shifts)
            if (!$schedule || !$schedule->shift) {
                $yesterdaySchedule = EmployeeSchedule::where('employee_id', $employeeId)
                    ->where('schedule_date', $yesterday)
                    ->with('shift')
                    ->whereHas('shift', function ($q) {
                        $q->where('is_off', false);
                    })
                    ->first();

                if ($yesterdaySchedule && $yesterdaySchedule->shift) {
                    $shift = $yesterdaySchedule->shift;
                    if ($shift->end_time < $shift->start_time) {
                        $schedule = $yesterdaySchedule;
                        $today = $yesterday; // Use yesterday as the attendance date for this overnight shift
                    }
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
            // Self-healing: if existing attendance was already checked out earlier today (e.g. morning handover checkout),
            // and the employee has an active evening/night shift starting now, reattribute morning checkout to yesterday
            if ($existing->check_out_time && $schedule && $schedule->shift) {
                $shiftStart = Carbon::parse($today . ' ' . $schedule->shift->start_time);
                $coTime = Carbon::parse($existing->check_out_time);

                // Morning checkout before 13:00, and current shift is evening/night (>= 15:00)
                if ($shiftStart->hour >= 15 && $coTime->hour < 13) {
                    $yesterdayHasAtt = Attendance::where('employee_id', $employeeId)
                        ->where('attendance_date', $yesterday)
                        ->exists();

                    if (!$yesterdayHasAtt) {
                        $existing->update(['attendance_date' => $yesterday]);
                        $existing = null;
                    }
                }
            }

            if ($existing) {
                throw new \RuntimeException('Anda sudah melakukan check-in hari ini.');
            }
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

        // During morning/handover (before 14:00), prioritize yesterday's unclosed overnight shift
        $yesterdayAttendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $yesterday)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if ($yesterdayAttendance && $now->hour < 14) {
            $attendance = $yesterdayAttendance;
        } else {
            // Otherwise, look for today's open attendance first
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $today)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();

            if (!$attendance && $yesterdayAttendance) {
                $attendance = $yesterdayAttendance;
            }
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
