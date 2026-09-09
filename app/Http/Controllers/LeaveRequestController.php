<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee:id,name', 'leaveType:id,name', 'approver:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $leaveTypes = \App\Models\LeaveType::active()->orderBy('name')->get();
        $employees = \App\Models\User::orderBy('name')->get();

        return view('admin.leaves.requests', compact('requests', 'leaveTypes', 'employees'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->isPending()) {
            return $this->ajaxError('Pengajuan cuti ini sudah diproses.');
        }

        try {
            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->updateBalance($leaveRequest);

            return $this->ajaxSuccess('Pengajuan cuti berhasil disetujui.');
        } catch (\Exception $e) {
            return $this->ajaxError('Gagal: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->isPending()) {
            return $this->ajaxError('Pengajuan cuti ini sudah diproses.');
        }

        $request->validate([
            'rejection_note' => 'nullable|string|max:500',
        ]);

        try {
            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_note' => $request->rejection_note,
            ]);

            return $this->ajaxSuccess('Pengajuan cuti berhasil ditolak.');
        } catch (\Exception $e) {
            return $this->ajaxError('Gagal: ' . $e->getMessage());
        }
    }

    protected function updateBalance(LeaveRequest $leaveRequest): void
    {
        $year = $leaveRequest->start_date->year;
        $balance = LeaveBalance::firstOrCreate(
            [
                'hotel_id' => $leaveRequest->hotel_id,
                'employee_id' => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year' => $year,
            ],
            [
                'allocated' => $leaveRequest->leaveType->max_days_per_year ?? 0,
                'used' => 0,
                'remaining' => $leaveRequest->leaveType->max_days_per_year ?? 0,
            ]
        );

        $balance->update([
            'used' => $balance->used + $leaveRequest->total_days,
            'remaining' => max(0, $balance->remaining - $leaveRequest->total_days),
        ]);
    }
}
