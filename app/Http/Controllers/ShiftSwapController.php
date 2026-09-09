<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSchedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class ShiftSwapController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        // Riwayat (mirip Leave Request): tampilkan semua status, bukan cuma
        // pending, dengan filter opsional -- sebelumnya approved/rejected
        // langsung hilang dari daftar begitu diproses meski datanya
        // (reason/approved_by/approved_at/notes) sudah tersimpan lengkap.
        $query = ShiftSwapRequest::with(['requester', 'target', 'requesterSchedule.shift', 'targetSchedule.shift', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('shift_swaps.index', compact('requests'));
    }

    public function create()
    {
        $mySchedules = EmployeeSchedule::with('shift')
            ->where('employee_id', auth()->id())
            ->whereHas('shift', fn($q) => $q->where('is_off', false))
            ->whereDate('schedule_date', '>=', now())
            ->orderBy('schedule_date')
            ->get();

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

        return view('shift_swaps.create', compact('mySchedules', 'employees'));
    }

    public function getEmployeeSchedule($employeeId, $date)
    {
        $schedule = EmployeeSchedule::with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('schedule_date', $date)
            ->first();

        if (!$schedule || !$schedule->shift || $schedule->shift->is_off) {
            return response()->json(['error' => 'No shift found for this date.'], 404);
        }

        return response()->json($schedule->load('shift'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'requester_schedule_id' => 'required|exists:employee_schedules,id',
            'target_schedule_id' => 'required|exists:employee_schedules,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $requesterSchedule = EmployeeSchedule::findOrFail($request->requester_schedule_id);
            $targetSchedule = EmployeeSchedule::findOrFail($request->target_schedule_id);

            if ($requesterSchedule->employee_id !== auth()->id()) {
                $errorMessage = 'You can only request swaps for your own schedule.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            $exists = ShiftSwapRequest::where('requester_id', auth()->id())
                ->where('status', 'pending')
                ->where(function ($q) use ($request) {
                    $q->where('requester_schedule_id', $request->requester_schedule_id)
                      ->orWhere('target_schedule_id', $request->target_schedule_id);
                })->exists();

            if ($exists) {
                $errorMessage = 'A pending swap request already exists for this schedule.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            $swapRequest = ShiftSwapRequest::create([
                'requester_id' => auth()->id(),
                'target_id' => $targetSchedule->employee_id,
                'requester_schedule_id' => $request->requester_schedule_id,
                'target_schedule_id' => $request->target_schedule_id,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            return $this->ajaxOrRedirect('Swap request submitted successfully.', route('my-schedule.index'), $swapRequest);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function approve(ShiftSwapRequest $shiftSwap)
    {
        try {
            if ($shiftSwap->status !== 'pending') {
                $errorMessage = 'This request has already been processed.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            $requesterSched = $shiftSwap->requesterSchedule;
            $targetSched = $shiftSwap->targetSchedule;

            $requesterShiftId = $requesterSched->shift_id;
            $requesterLocation = $requesterSched->location;

            $requesterSched->update([
                'shift_id' => $targetSched->shift_id,
                'location' => $targetSched->location,
            ]);

            $targetSched->update([
                'shift_id' => $requesterShiftId,
                'location' => $requesterLocation,
            ]);

            $shiftSwap->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $this->ajaxOrRedirect('Swap request approved.', route('shift-swaps.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, ShiftSwapRequest $shiftSwap)
    {
        try {
            if ($shiftSwap->status !== 'pending') {
                $errorMessage = 'This request has already been processed.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            $shiftSwap->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $request->notes,
            ]);

            return $this->ajaxOrRedirect('Swap request rejected.', route('shift-swaps.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function cancel(ShiftSwapRequest $shiftSwap)
    {
        try {
            if ($shiftSwap->requester_id !== auth()->id()) {
                $errorMessage = 'You can only cancel your own requests.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            if ($shiftSwap->status !== 'pending') {
                $errorMessage = 'This request has already been processed.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return back()->with('error', $errorMessage);
            }

            $shiftSwap->update(['status' => 'cancelled']);

            return $this->ajaxOrRedirect('Swap request cancelled.', route('my-schedule.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
