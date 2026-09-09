<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = Attendance::where('attendances.hotel_id', $hotelId)
            ->with(['employee', 'shift', 'schedule.swapRequestsAsRequester', 'schedule.swapRequestsAsTarget']);

        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->input('employee_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('attendance_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('attendance_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('shift_id')) {
            $query->where('attendances.shift_id', $request->input('shift_id'));
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('attendances.employee_id')
            ->paginate(25)
            ->withQueryString();

        // Fetch approved leaves
        $leaveQuery = \App\Models\LeaveRequest::where('hotel_id', $hotelId)
            ->where('status', 'approved')
            ->with(['employee', 'leaveType']);
        if ($request->filled('employee_id')) {
            $leaveQuery->where('employee_id', $request->input('employee_id'));
        }
        if ($request->filled('date_from')) {
            $leaveQuery->where('end_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $leaveQuery->where('start_date', '<=', $request->input('date_to'));
        }
        $leaves = $leaveQuery->get();

        $employees = User::whereHas('schedules', function ($q) use ($hotelId) {
            $q->whereHas('shift');
        })->orderBy('name')->get();

        $shifts = Shift::where('hotel_id', $hotelId)->active()->get();

        return view('attendance.report', compact('attendances', 'employees', 'shifts', 'leaves'));
    }

    public function detail(Attendance $attendance)
    {
        if ((int) $attendance->hotel_id !== (int) active_hotel_id()) {
            abort(403);
        }

        $attendance->load(['employee', 'shift', 'schedule', 'checkInLocation', 'checkOutLocation']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attendance->id,
                'employee_name' => $attendance->employee->name,
                'attendance_date' => $attendance->attendance_date->format('d/m/Y'),
                'shift_name' => $attendance->shift?->name,
                'shift_time' => $attendance->shift ? ($attendance->shift->start_time . ' - ' . $attendance->shift->end_time) : '-',
                'check_in_time' => $attendance->check_in_time?->format('Y-m-d\TH:i'),
                'check_out_time' => $attendance->check_out_time?->format('Y-m-d\TH:i'),
                'break_start_time' => $attendance->break_start_time?->format('H:i'),
                'break_end_time' => $attendance->break_end_time?->format('H:i'),
                'status' => $attendance->status,
                'status_label' => $this->getStatusLabel($attendance->status),
                'status_color' => $this->getStatusColor($attendance->status),
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
                'notes' => $attendance->notes,
                'check_in_photo_url' => $attendance->check_in_photo ? asset('storage/' . $attendance->check_in_photo) : null,
                'check_out_photo_url' => $attendance->check_out_photo ? asset('storage/' . $attendance->check_out_photo) : null,
                'check_in_latitude' => $attendance->check_in_latitude,
                'check_in_longitude' => $attendance->check_in_longitude,
                'check_out_latitude' => $attendance->check_out_latitude,
                'check_out_longitude' => $attendance->check_out_longitude,
                'check_in_location_name' => $attendance->checkInLocation?->name,
                'check_out_location_name' => $attendance->checkOutLocation?->name,
            ],
        ]);
    }

    public function summary(Request $request)
    {
        $hotelId = active_hotel_id();
        $month = (int) $request->input('month', now()->format('m'));
        $year = (int) $request->input('year', now()->format('Y'));

        $employees = User::whereHas('schedules', function ($q) use ($hotelId) {
            $q->whereHas('shift');
        })
        ->when($request->filled('employee_id'), fn($q) => $q->where('id', $request->input('employee_id')))
        ->with(['attendances' => function ($q) use ($month, $year) {
            $q->whereYear('attendance_date', $year)
              ->whereMonth('attendance_date', $month);
        }])->orderBy('name')->get();

        $summaries = $employees->map(function ($employee) use ($month, $year) {
            $atts = $employee->attendances;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'total_present' => $atts->whereIn('status', ['present'])->count(),
                'total_late' => $atts->whereIn('status', ['late', 'late_and_early_leave'])->count(),
                'total_early_leave' => $atts->whereIn('status', ['early_leave', 'late_and_early_leave'])->count(),
                'total_absent' => $atts->where('status', 'absent')->count(),
                'total_overtime_minutes' => $atts->sum('overtime_minutes'),
                'total_days' => $atts->count(),
            ];
        })->filter(function ($s) {
            return $s['total_days'] > 0;
        })->values();

        return view('attendance.summary', compact('summaries', 'month', 'year'))
            ->with('allEmployees', User::whereHas('schedules', fn($q) => $q->whereHas('shift'))
                ->orderBy('name')->get(['id', 'name']));
    }

    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'early_leave' => 'Pulang Awal',
            'late_and_early_leave' => 'Terlambat & Pulang Awal',
            'absent' => 'Absen',
            default => ucfirst($status),
        };
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'early_leave' => 'info',
            'late_and_early_leave' => 'danger',
            'absent' => 'danger',
            default => 'secondary',
        };
    }

    public function export(Request $request)
    {
        $filename = 'laporan_absensi_' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceReportExport(
                $request->input('employee_id') ? (int) $request->input('employee_id') : null,
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status'),
                $request->input('shift_id') ? (int) $request->input('shift_id') : null,
            ),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = Attendance::where('hotel_id', $hotelId)
            ->with(['employee', 'shift', 'checkInLocation', 'checkOutLocation']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }
        if ($request->filled('date_from')) {
            $query->where('attendance_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('attendance_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', (int) $request->input('shift_id'));
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->orderBy('employee_id')->get();

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $hotel = current_hotel();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.exports.pdf', compact('attendances', 'dateFrom', 'dateTo', 'hotel'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('laporan_absensi_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Admin edit attendance record (change check-in/check-out times, status, notes).
     */
    public function edit(Request $request, Attendance $attendance)
    {
        if ((int) $attendance->hotel_id !== (int) active_hotel_id()) {
            abort(403);
        }

        $request->validate([
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',
            'status' => 'nullable|in:present,late,early_leave,late_and_early_leave,absent',
            'notes' => 'nullable|string|max:500',
        ]);

        $updates = [];

        if ($request->filled('check_in_time')) {
            $updates['check_in_time'] = Carbon::parse($request->check_in_time);
        }

        if ($request->filled('check_out_time')) {
            $updates['check_out_time'] = Carbon::parse($request->check_out_time);
        }

        if ($request->filled('status')) {
            $updates['status'] = $request->status;
        }

        if ($request->has('notes')) {
            $adminNote = '[Admin edit ' . now()->format('d/m H:i') . ' by ' . auth()->user()->name . ']';
            $updates['notes'] = $request->notes ? $request->notes . ' ' . $adminNote : $adminNote;
        }

        // Recalculate late/early if times changed
        if (isset($updates['check_in_time']) && $attendance->shift) {
            $shiftStart = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->shift->start_time);
            $lateMinutes = max(0, $shiftStart->diffInMinutes($updates['check_in_time'], false));
            $updates['late_minutes'] = $lateMinutes > 0 ? $lateMinutes : 0;
        }

        if (isset($updates['check_out_time']) && $attendance->shift && $attendance->check_in_time) {
            $shiftEnd = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->shift->end_time);
            $shiftStart = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->shift->start_time);
            
            if ($shiftEnd <= $shiftStart) {
                $shiftEnd->addDay();
            }

            $earlyMinutes = max(0, $updates['check_out_time']->diffInMinutes($shiftEnd, false));
            $updates['early_leave_minutes'] = $earlyMinutes > 0 ? $earlyMinutes : 0;

            $overtimeMinutes = max(0, $shiftEnd->diffInMinutes($updates['check_out_time'], false));
            $updates['overtime_minutes'] = $overtimeMinutes > 0 ? $overtimeMinutes : 0;
        }

        $attendance->update($updates);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Absensi berhasil diperbarui']);
        }

        return redirect()->route('attendance.report.index')->with('success', 'Absensi berhasil diperbarui');
    }

    /**
     * Admin reset checkout for an attendance record.
     */
    public function resetCheckout(Attendance $attendance)
    {
        if ((int) $attendance->hotel_id !== (int) active_hotel_id()) {
            abort(403);
        }

        if (!$attendance->check_out_time) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Karyawan belum checkout']);
            }
            return back()->with('error', 'Karyawan belum checkout');
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
            'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . '[Admin reset checkout ' . now()->format('d/m H:i') . ' by ' . auth()->user()->name . ']',
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Checkout berhasil di-reset']);
        }

        return redirect()->route('attendance.report.index')->with('success', 'Checkout berhasil di-reset');
    }

    /**
     * Admin delete attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        if ((int) $attendance->hotel_id !== (int) active_hotel_id()) {
            abort(403);
        }

        $attendance->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data absensi berhasil dihapus']);
        }

        return redirect()->route('attendance.report.index')->with('success', 'Data absensi berhasil dihapus');
    }

    /**
     * Automatically recalculate and fix attendance status/minutes.
     */
    public function fixStatus(Attendance $attendance)
    {
        if ((int) $attendance->hotel_id !== (int) active_hotel_id()) {
            abort(403);
        }

        if (!$attendance->shift || !$attendance->check_in_time) {
            return response()->json(['success' => false, 'message' => 'Data tidak lengkap (Shift atau Check-in kosong)']);
        }

        $updates = [];
        $shift = $attendance->shift;
        $attendanceDate = $attendance->attendance_date->format('Y-m-d');
        
        // Recalculate Late
        $shiftStart = Carbon::parse($attendanceDate . ' ' . $shift->start_time);
        $lateMinutes = max(0, $shiftStart->diffInMinutes($attendance->check_in_time, false));
        $updates['late_minutes'] = $lateMinutes > 5 ? $lateMinutes : 0; // 5 min tolerance

        // Recalculate Early Leave & Overtime
        if ($attendance->check_out_time) {
            $shiftEnd = Carbon::parse($attendanceDate . ' ' . $shift->end_time);
            if ($shiftEnd <= $shiftStart) {
                $shiftEnd->addDay();
            }

            $earlyMinutes = max(0, $attendance->check_out_time->diffInMinutes($shiftEnd, false));
            $updates['early_leave_minutes'] = $earlyMinutes > 5 ? $earlyMinutes : 0; // 5 min tolerance

            $overtimeMinutes = max(0, $shiftEnd->diffInMinutes($attendance->check_out_time, false));
            $updates['overtime_minutes'] = $overtimeMinutes;

            // Update Status
            $newStatus = $updates['late_minutes'] > 0 ? 'late' : 'present';
            if ($updates['early_leave_minutes'] > 0) {
                $newStatus = ($newStatus === 'late') ? 'late_and_early_leave' : 'early_leave';
            }
            $updates['status'] = $newStatus;
        } else {
            $updates['status'] = $updates['late_minutes'] > 0 ? 'late' : 'present';
            $updates['early_leave_minutes'] = 0;
            $updates['overtime_minutes'] = 0;
        }

        $attendance->update($updates);

        return response()->json([
            'success' => true, 
            'message' => 'Status berhasil diperbaiki',
            'data' => [
                'status' => $attendance->status,
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes
            ]
        ]);
    }
}
