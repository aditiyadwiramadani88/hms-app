<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function __construct(
        protected AttendanceService $attendanceService,
        protected GeofenceService $geofenceService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');

        $todayAttendance = $this->attendanceService->getTodayStatus($user->id);

        // If no attendance today, carry over an open shift from yesterday ONLY if it's
        // a genuine overnight shift still within its window (e.g. 21:00-07:00, checked
        // in last night, still before 07:00 today). A day shift or a lapsed overnight
        // shift left open must NOT "connect" into today's schedule/duration -- that
        // was making a missed checkout look like a still-ongoing shift indefinitely.
        if (!$todayAttendance) {
            $yesterdayAttendance = \App\Models\Attendance::where('employee_id', $user->id)
                ->where('attendance_date', $yesterday)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->with('shift')
                ->first();

            if ($yesterdayAttendance && $yesterdayAttendance->shift) {
                $shift = $yesterdayAttendance->shift;
                $isOvernight = $shift->end_time && $shift->start_time && $shift->end_time < $shift->start_time;
                $shiftEndToday = $isOvernight ? Carbon::parse($today . ' ' . $shift->end_time) : null;

                if ($isOvernight && now()->lt($shiftEndToday)) {
                    $todayAttendance = $yesterdayAttendance;
                    $today = $yesterday; // Show yesterday's schedule as active
                }
            }
        }

        $schedule = \App\Models\EmployeeSchedule::where('employee_id', $user->id)
            ->where('schedule_date', $today)
            ->with('shift')
            ->first();

        $currentMonth = request('month', now()->format('m'));
        $currentYear = request('year', now()->format('Y'));

        $monthlyAttendances = Attendance::where('employee_id', $user->id)
            ->whereYear('attendance_date', $currentYear)
            ->whereMonth('attendance_date', $currentMonth)
            ->with(['employee:id,name', 'shift:id,name'])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->attendance_date)->format('Y-m-d');
            });

        $summary = $this->getMonthlySummary($user->id, $currentMonth, $currentYear);

        $activeLocations = \App\Models\AttendanceLocation::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->get();

        return view('attendance.index', compact(
            'todayAttendance',
            'schedule',
            'monthlyAttendances',
            'summary',
            'currentMonth',
            'currentYear',
            'activeLocations'
        ));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $attendance = $this->attendanceService->checkIn(
                auth()->id(),
                $request->input('photo'),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            $distanceInfo = $this->getDistanceInfo(
                active_hotel_id(),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            return $this->ajaxSuccess('Check-in berhasil pada ' . $attendance->check_in_time->format('H:i') . '.', array_merge([
                'status' => $attendance->status,
                'check_in_time' => $attendance->check_in_time->format('H:i'),
                'late_minutes' => $attendance->late_minutes,
            ], $distanceInfo));
        } catch (\RuntimeException $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $attendance = $this->attendanceService->checkOut(
                auth()->id(),
                $request->input('photo'),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            $checkIn = Carbon::parse($attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->check_out_time);
            $totalHours = $checkIn->diffInHours($checkOut) . ' jam ' . ($checkIn->diffInMinutes($checkOut) % 60) . ' menit';

            $distanceInfo = $this->getDistanceInfo(
                active_hotel_id(),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            return $this->ajaxSuccess('Check-out berhasil pada ' . $attendance->check_out_time->format('H:i') . '.', array_merge([
                'status' => $attendance->status,
                'check_in_time' => $attendance->check_in_time->format('H:i'),
                'check_out_time' => $attendance->check_out_time->format('H:i'),
                'total_hours' => $totalHours,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
            ], $distanceInfo));
        } catch (\RuntimeException $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function startBreak(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $attendance = $this->attendanceService->startBreak(
                auth()->id(),
                $request->input('photo'),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            $distanceInfo = $this->getDistanceInfo(
                active_hotel_id(),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            return $this->ajaxSuccess('Mulai istirahat berhasil pada ' . $attendance->break_start_time->format('H:i') . '.', array_merge([
                'status' => $attendance->status,
                'break_start_time' => $attendance->break_start_time->format('H:i'),
            ], $distanceInfo));
        } catch (\RuntimeException $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function endBreak(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $attendance = $this->attendanceService->endBreak(
                auth()->id(),
                $request->input('photo'),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            $distanceInfo = $this->getDistanceInfo(
                active_hotel_id(),
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            return $this->ajaxSuccess('Selesai istirahat berhasil pada ' . $attendance->break_end_time->format('H:i') . '.', array_merge([
                'status' => $attendance->status,
                'break_end_time' => $attendance->break_end_time->format('H:i'),
            ], $distanceInfo));
        } catch (\RuntimeException $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    protected function getDistanceInfo(int $hotelId, float $lat, float $lng): array
    {
        $nearest = $this->geofenceService->getNearestWithDistance($hotelId, $lat, $lng);

        if (!$nearest) {
            return [
                'location_name' => null,
                'distance' => null,
            ];
        }

        return [
            'location_name' => $nearest['location']->name,
            'distance' => $nearest['distance'],
        ];
    }

    /**
     * User resets their own checkout (only today, only if already checked out).
     */
    public function resetCheckout(Request $request)
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $user->id)
            ->where('attendance_date', $today)
            ->whereNotNull('check_out_time')
            ->first();

        if (!$attendance) {
            return $this->ajaxError('Tidak ada data checkout hari ini yang bisa di-reset.');
        }

        $attendance->update([
            'check_out_time' => null,
            'check_out_photo' => null,
            'check_out_latitude' => null,
            'check_out_longitude' => null,
            'check_out_location_id' => null,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => $attendance->late_minutes > 0 ? 'late' : 'present',
            'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'Checkout di-reset oleh user pada ' . now()->format('H:i'),
        ]);

        return $this->ajaxSuccess('Checkout berhasil di-reset. Silakan checkout ulang di waktu yang benar.');
    }

    public function history(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        $user = auth()->user();
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');

        $attendances = Attendance::where('employee_id', $user->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->attendance_date->format('Y-m-d'),
                    'status' => $item->status,
                    'check_in_time' => $item->check_in_time?->format('H:i'),
                    'check_out_time' => $item->check_out_time?->format('H:i'),
                ];
            });

        $summary = $this->getMonthlySummary($user->id, $month, $year);

        return response()->json([
            'success' => true,
            'data' => [
                'attendances' => $attendances,
                'summary' => $summary,
            ],
        ]);
    }

    protected function getMonthlySummary(int $employeeId, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $totalPresent = $attendances->whereIn('status', ['present'])->count();
        $totalLate = $attendances->whereIn('status', ['late', 'late_and_early_leave'])->count();
        $totalEarlyLeave = $attendances->whereIn('status', ['early_leave', 'late_and_early_leave'])->count();
        $totalAbsent = $attendances->where('status', 'absent')->count();
        $totalOvertimeMinutes = $attendances->sum('overtime_minutes');

        return [
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_early_leave' => $totalEarlyLeave,
            'total_absent' => $totalAbsent,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'total_overtime_hours' => $totalOvertimeMinutes > 0 ? round($totalOvertimeMinutes / 60, 1) : 0,
            'total_days' => $attendances->count(),
        ];
    }
}
