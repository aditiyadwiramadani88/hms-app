<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\ScheduleLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\AjaxResponse;

class EmployeeScheduleController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();
        $location = $request->location;

        $employees = User::whereNull('tenant_id')
            ->where('show_in_schedule', true)
            ->orderBy('name')->get();

        $shifts = Shift::active()->get();
        $locations = ScheduleLocation::active()->get();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $schedules = EmployeeSchedule::whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
            ->when($location, fn($q) => $q->where('location', $location))
            ->with(['shift:id,name,code,color,start_time,end_time,break_start_time,break_end_time,start_time_2,end_time_2,is_off'])
            ->get()
            ->groupBy('employee_id');

        $dates = [];
        for ($d = $startOfMonth->copy(); $d <= $endOfMonth; $d->addDay()) {
            $dates[] = $d->copy();
        }

        $totalHoursByEmployee = [];
        foreach ($employees as $emp) {
            $totalMinutes = 0;
            $empSchedules = $schedules->get($emp->id, collect());
            foreach ($empSchedules as $sched) {
                if ($sched->shift && !$sched->shift->is_off) {
                    $start = Carbon::parse($sched->shift->start_time);
                    $end = Carbon::parse($sched->shift->end_time);
                    if ($end < $start) $end->addDay();
                    $minutes = max(0, $start->diffInMinutes($end));

                    if ($sched->shift->break_start_time && $sched->shift->break_end_time) {
                        $bStart = Carbon::parse($sched->shift->break_start_time);
                        $bEnd = Carbon::parse($sched->shift->break_end_time);
                        if ($bStart < $start) $bStart->addDay();
                        if ($bEnd < $bStart) $bEnd->addDay();
                        
                        $breakMinutes = max(0, $bStart->diffInMinutes($bEnd));
                        $minutes = max(0, $minutes - $breakMinutes);
                    }
                    if ($sched->shift->start_time_2 && $sched->shift->end_time_2) {
                        $start2 = Carbon::parse($sched->shift->start_time_2);
                        $end2 = Carbon::parse($sched->shift->end_time_2);
                        if ($start2 < $start) $start2->addDay();
                        if ($end2 < $start2) $end2->addDay();
                        
                        $minutes += max(0, $start2->diffInMinutes($end2));
                    }
                    $totalMinutes += $minutes;
                }
                if ($sched->is_overtime && $sched->overtime_hours) {
                    $totalMinutes += $sched->overtime_hours * 60;
                }
            }
            $totalHoursByEmployee[$emp->id] = round($totalMinutes / 60, 1);
        }

        return view('employee_schedules.index', compact(
            'employees', 'shifts', 'locations', 'schedules', 'dates',
            'month', 'location', 'totalHoursByEmployee'
        ));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:users,id',
                'schedule_date' => 'required|date',
                'shift_id' => 'nullable|exists:shifts,id',
                'location' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'is_overtime' => 'nullable|boolean',
                'overtime_hours' => 'nullable|numeric|min:0|max:24',
            ]);

            $schedule = EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'schedule_date' => $request->schedule_date,
                ],
                [
                    'shift_id' => $request->shift_id,
                    'location' => $request->location,
                    'notes' => $request->notes,
                    'is_overtime' => $request->boolean('is_overtime'),
                    'overtime_hours' => $request->overtime_hours,
                    'created_by' => auth()->id(),
                ]
            );

            return $this->ajaxOrRedirect('Schedule saved successfully.', route('employee-schedules.index', ['month' => $request->month]), $schedule);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function bulkAssign(Request $request)
    {
        try {
            $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'pattern' => 'required|in:same,weekly',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $dayMapping = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

            foreach ($request->employee_ids as $employeeId) {
                for ($d = $startDate->copy(); $d <= $endDate; $d->addDay()) {
                    $shiftId = null;
                    $location = $request->location;

                    if ($request->pattern === 'same') {
                        $shiftId = $request->shift_id;
                    } else {
                        $dayKey = $dayMapping[$d->dayOfWeek];
                        $shiftId = $request->{"shift_$dayKey"};
                    }

                    EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'schedule_date' => $d->format('Y-m-d'),
                        ],
                        [
                            'shift_id' => $shiftId ?: null,
                            'location' => $location,
                            'created_by' => auth()->id(),
                        ]
                    );
                }
            }

            return $this->ajaxOrRedirect('Bulk assign completed successfully.', route('employee-schedules.index', ['month' => $startDate->format('Y-m')]));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function destroy(EmployeeSchedule $employeeSchedule)
    {
        try {
            $month = $employeeSchedule->schedule_date->format('Y-m');
            $employeeSchedule->delete();

            return $this->ajaxOrRedirect('Schedule entry deleted.', route('employee-schedules.index', ['month' => $month]));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function getSchedule($employeeId, $date)
    {
        $schedule = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('schedule_date', $date)
            ->first();

        return response()->json($schedule);
    }

    /**
     * Batch update schedules (paint mode).
     */
    public function batchUpdate(Request $request)
    {
        try {
            $request->validate([
                'changes' => 'required|array',
                'changes.*.employee_id' => 'required|exists:users,id',
                'changes.*.schedule_date' => 'required|date',
                'changes.*.shift_id' => 'nullable',
            ]);

            $changes = $request->input('changes');
            $saved = 0;
            $deleted = 0;

            foreach ($changes as $change) {
                if (empty($change['shift_id']) || $change['shift_id'] === null) {
                    // Delete schedule
                    $del = EmployeeSchedule::where('employee_id', $change['employee_id'])
                        ->where('schedule_date', $change['schedule_date'])
                        ->delete();
                    if ($del) $deleted++;
                } else {
                    // Create or update
                    EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $change['employee_id'],
                            'schedule_date' => $change['schedule_date'],
                        ],
                        [
                            'shift_id' => $change['shift_id'],
                            'created_by' => auth()->id(),
                        ]
                    );
                    $saved++;
                }
            }

            return $this->ajaxOrRedirect("Berhasil: {$saved} jadwal disimpan, {$deleted} jadwal dihapus.", route('employee-schedules.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Show manage staff page — toggle which employees appear in schedule grid.
     */
    public function manageStaff()
    {
        $allStaff = User::whereNull('tenant_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'show_in_schedule']);

        return view('employee_schedules.manage-staff', compact('allStaff'));
    }

    /**
     * Toggle show_in_schedule for a user.
     */
    public function toggleVisibility(User $user)
    {
        $newValue = !((bool) $user->show_in_schedule);
        $user->show_in_schedule = $newValue;
        $user->save();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $newValue ? 'Karyawan ditampilkan di jadwal' : 'Karyawan disembunyikan dari jadwal',
                'show_in_schedule' => $newValue,
            ]);
        }

        return redirect()->route('employee-schedules.manage-staff')
            ->with('success', $newValue ? "{$user->name} ditampilkan di jadwal" : "{$user->name} disembunyikan dari jadwal");
    }
}
