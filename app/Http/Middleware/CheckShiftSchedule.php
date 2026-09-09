<?php

namespace App\Http\Middleware;

use App\Models\EmployeeSchedule;
use App\Models\Shift;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class CheckShiftSchedule
{
    /**
     * Check if the authenticated user is allowed to access the system based on their shift schedule.
     * 
     * Rules:
     * - If user has NO schedule entries at all → free to login anytime
     * - If user has a schedule for today and it's their shift time → allow
     * - If user has a schedule for today but it's NOT their shift time → block
     * - If user has schedules but none for today (day off / not scheduled) → block
     * - Admin and Manager roles are always exempt
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Allow access to the blocked page itself, logout, attendance, security-gate, and impersonate routes
        if ($request->routeIs('shift.blocked') || $request->routeIs('logout') || $request->routeIs('attendance.*') || $request->routeIs('security-gate.*') || $request->routeIs('impersonate.*')) {
            return $next($request);
        }

        // Exempt roles: Admin, Manager, General Manager, Super Admin
        $exemptRoles = ['Admin', 'Manager', 'General Manager', 'Super Admin'];
        $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->pluck('roles.name')
            ->toArray();

        if (array_intersect($exemptRoles, $userRoles)) {
            return $next($request);
        }

        // Tenant users are exempt
        if ($user->tenant_id) {
            return $next($request);
        }

        $hotelId = session('active_hotel_id');
        if (!$hotelId) {
            return $next($request);
        }

        // Check if user has ANY schedule entries (ever)
        $hasAnySchedule = EmployeeSchedule::where('employee_id', $user->id)->exists();

        // If user has no schedule at all → free to login anytime
        if (!$hasAnySchedule) {
            return $next($request);
        }

        // User has schedules → check active shift (could be today's or yesterday's overnight)
        $today = now()->format('Y-m-d');
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');
        $now = Carbon::now();

        // 1. Check yesterday's schedule for an overnight shift that is still active
        $yesterdaySchedule = EmployeeSchedule::with('shift')
            ->where('employee_id', $user->id)
            ->where('schedule_date', $yesterday)
            ->first();

        if ($yesterdaySchedule && $yesterdaySchedule->shift && !$yesterdaySchedule->shift->is_off) {
            $shift = $yesterdaySchedule->shift;
            $shiftStart = Carbon::parse($yesterday . ' ' . $shift->start_time)->subMinutes(30);
            $shiftEnd = Carbon::parse($yesterday . ' ' . $shift->end_time)->addMinutes(30);

            if ($shiftEnd <= $shiftStart) {
                $shiftEnd->addDay();
            }

            // If we are still within yesterday's overnight shift period
            if ($now->gte($shiftStart) && $now->lte($shiftEnd)) {
                if (!$this->hasClockedIn($user->id, $yesterday)) {
                    return redirect()->route('attendance.index')->with('error', 'Anda harus absen masuk (Check-In) terlebih dahulu sebelum dapat mengakses sistem.');
                }
                return $next($request);
            }
        }

        // 2. Check today's schedule
        $todaySchedule = EmployeeSchedule::with('shift')
            ->where('employee_id', $user->id)
            ->where('schedule_date', $today)
            ->first();

        if ($todaySchedule && $todaySchedule->shift && !$todaySchedule->shift->is_off) {
            $shift = $todaySchedule->shift;
            $shiftStart = Carbon::parse($today . ' ' . $shift->start_time)->subMinutes(30);
            $shiftEnd = Carbon::parse($today . ' ' . $shift->end_time)->addMinutes(30);

            if ($shiftEnd <= $shiftStart) {
                $shiftEnd->addDay();
            }

            if ($now->gte($shiftStart) && $now->lte($shiftEnd)) {
                if (!$this->hasClockedIn($user->id, $today)) {
                    return redirect()->route('attendance.index')->with('error', 'Anda harus absen masuk (Check-In) terlebih dahulu sebelum dapat mengakses sistem.');
                }
                return $next($request);
            }

            // Check second shift period if exists
            if ($shift->start_time_2 && $shift->end_time_2) {
                $shiftStart2 = Carbon::parse($today . ' ' . $shift->start_time_2)->subMinutes(30);
                $shiftEnd2 = Carbon::parse($today . ' ' . $shift->end_time_2)->addMinutes(30);

                if ($now->gte($shiftStart2) && $now->lte($shiftEnd2)) {
                    if (!$this->hasClockedIn($user->id, $today)) {
                        return redirect()->route('attendance.index')->with('error', 'Anda harus absen masuk (Check-In) terlebih dahulu sebelum dapat mengakses sistem.');
                    }
                    return $next($request);
                }
            }
        }

        // If no active shift found (neither yesterday's overnight nor today's)
        if (!$todaySchedule || !$todaySchedule->shift) {
            return redirect()->route('shift.blocked')->with('shift_message', 'Anda tidak memiliki jadwal hari ini. Silakan hubungi Admin untuk pengaturan jadwal.');
        }

        if ($todaySchedule->shift->is_off) {
            return redirect()->route('shift.blocked')->with('shift_message', 'Hari ini adalah hari libur Anda.');
        }

        // Outside shift hours → block
        $shiftName = $todaySchedule->shift->name;
        $shiftTime = Carbon::parse($todaySchedule->shift->start_time)->format('H:i') . ' - ' . Carbon::parse($todaySchedule->shift->end_time)->format('H:i');

        return redirect()->route('shift.blocked')->with([
            'shift_message' => "Saat ini bukan jam shift Anda.",
            'shift_name' => $shiftName,
            'shift_time' => $shiftTime,
        ]);
    }

    /**
     * Check if the user has clocked in for the given date.
     */
    private function hasClockedIn($userId, $date): bool
    {
        return \App\Models\Attendance::where('employee_id', $userId)
            ->where('attendance_date', $date)
            ->whereNotNull('check_in_time')
            ->exists();
    }
}
