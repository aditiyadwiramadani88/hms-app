<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MyLeaveController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $user = auth()->user();
        $year = request('year', now()->year);

        $leaveRequests = LeaveRequest::with('leaveType:id,name', 'approver:id,name')
            ->where('employee_id', $user->id)
            ->whereYear('start_date', $year)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        $balances = LeaveBalance::with('leaveType:id,name')
            ->where('employee_id', $user->id)
            ->where('year', $year)
            ->get();

        $yearOptions = range(now()->year, now()->year - 2);

        return view('leaves.my-leaves', compact('leaveRequests', 'leaveTypes', 'balances', 'year', 'yearOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $totalDays = $startDate->diffInDays($endDate) + 1;

            $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

            if (!$leaveType->is_active) {
                return $this->ajaxError('Jenis cuti tidak aktif.');
            }

            $overlap = LeaveRequest::where('employee_id', auth()->id())
                ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })
                ->exists();

            if ($overlap) {
                return $this->ajaxError('Anda sudah memiliki pengajuan cuti yang tanggalnya bertumpang tindih.');
            }

            LeaveRequest::create([
                'hotel_id' => active_hotel_id(),
                'employee_id' => auth()->id(),
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
            ]);

            return $this->ajaxOrRedirect('Pengajuan cuti berhasil dikirim.', route('my-leaves.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
