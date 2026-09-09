<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MyScheduleController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();
        $employeeId = auth()->id();

        $shifts = Shift::active()->get();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $schedules = EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
            ->orderBy('schedule_date')
            ->get()
            ->keyBy(function ($item) {
                return $item->schedule_date->format('Y-m-d');
            });

        $today = now()->format('Y-m-d');
        $todaySchedule = $schedules->get($today);

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weekSchedules = EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('schedule_date', [$weekStart, $weekEnd])
            ->orderBy('schedule_date')
            ->get()
            ->keyBy(function ($item) {
                return $item->schedule_date->format('Y-m-d');
            });

        $dates = [];
        for ($d = $startOfMonth->copy(); $d <= $endOfMonth; $d->addDay()) {
            $dates[] = $d->copy();
        }

        $roleIds = \DB::table('roles')
            ->whereIn('name', [
                'Admin',
                'Front Office 1 (main)',
                'Front office 2 (branch)',
                'Housekeeping leader',
                'Housekeeping team',
                'General Manager'
            ])
            ->pluck('id');

        $employees = User::where('id', '!=', auth()->id())
            ->whereIn('id', \DB::table('model_has_roles')
                ->whereIn('role_id', $roleIds)
                ->pluck('model_id')
            )->orderBy('name')->get();

        $mySchedulesForSwap = EmployeeSchedule::with('shift')
            ->where('employee_id', auth()->id())
            ->whereHas('shift', fn($q) => $q->where('is_off', false))
            ->whereDate('schedule_date', '>=', now())
            ->orderBy('schedule_date')
            ->get();

        return view('my_schedule.index', compact(
            'schedules', 'month', 'today', 'todaySchedule',
            'weekStart', 'weekEnd', 'weekSchedules', 'shifts', 'dates',
            'employees', 'mySchedulesForSwap'
        ));
    }
}
