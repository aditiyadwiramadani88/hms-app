<?php

namespace App\Http\Controllers;

use App\Models\CleaningTask;
use App\Models\Room;
use App\Models\User;
use App\Exports\HousekeepingReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\AjaxResponse;

class HousekeepingReportController extends Controller
{
    use AjaxResponse;

    public function index(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->input('month', Carbon::now()->format('Y-m'));
            $staffId = $request->input('staff_id');

            // Optional single-day view (e.g. "Today Report" link from the HK dashboard)
            // instead of the whole month.
            if ($request->filled('date')) {
                $date = Carbon::parse($request->input('date'));
                $periodStart = $date->copy()->startOfDay();
                $periodEnd = $date->copy()->endOfDay();
            } else {
                $periodStart = Carbon::parse($month)->startOfMonth();
                $periodEnd = Carbon::parse($month)->endOfMonth();
            }

            $reportData = $this->buildReport($hotelId, $periodStart, $periodEnd, $staffId);

            // Role names for housekeeping/OB staff vary per hotel (e.g. "Housekeeping
            // leader", "Housekeeping team") — use the is_housekeeping_staff flag
            // instead of an exact role-name match, same pattern as HousekeepingController.
            $housekeepingRoleIds = \Spatie\Permission\Models\Role::where('is_housekeeping_staff', true)
                ->orWhere('name', 'like', '%Housekeeping%')
                ->orWhere('name', 'like', '%OB%')
                ->pluck('id');
            $teamColumn = config('permission.column_names.team_foreign_key', 'hotel_id');
            $staffUserIds = \DB::table('model_has_roles')
                ->whereIn('role_id', $housekeepingRoleIds)
                ->where($teamColumn, $hotelId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');
            $staffList = User::whereIn('id', $staffUserIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('reports.housekeeping_report', compact('reportData', 'month', 'staffId', 'staffList'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->input('month', Carbon::now()->format('Y-m'));
            $staffId = $request->input('staff_id');
            $monthStart = Carbon::parse($month)->startOfMonth();
            $monthEnd = Carbon::parse($month)->endOfMonth();

            $reportData = $this->buildReport($hotelId, $monthStart, $monthEnd, $staffId);
            $hotel = \App\Models\Hotel::find($hotelId);
            $monthObj = Carbon::parse($month);

            $pdf = Pdf::loadView('reports.housekeeping_report_pdf', compact('reportData', 'monthObj', 'hotel'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan_housekeeping_' . $month . '.pdf');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $month = $request->input('month', Carbon::now()->format('Y-m'));
            $staffId = $request->input('staff_id');
            return Excel::download(new HousekeepingReportExport($month, $staffId), 'laporan_housekeeping_' . $month . '.xlsx');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private const BONUS_CATEGORY_LABELS = [
        'pk' => 'PK (Perintah Khusus)',
        'sales' => 'Sales',
        'umum' => 'Umum',
        'online' => 'Online',
        'kos' => 'Kost',
        'kosong' => 'Kosong',
    ];

    private function buildReport(int $hotelId, Carbon $monthStart, Carbon $monthEnd, ?int $staffId = null): array
    {
        $tasks = CleaningTask::where('hotel_id', $hotelId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->whereBetween('completed_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
            ->when($staffId, fn($q) => $q->where('assigned_to', $staffId))
            ->with(['room.roomType', 'assignedUser'])
            ->orderBy('completed_at', 'desc')
            ->get();

        // Preload last checkout booking per room for booking source
        $roomIds = $tasks->pluck('room_id')->filter()->unique();
        $lastBookings = \App\Models\Booking::whereIn('room_id', $roomIds)
            ->where('status', 'checked_out')
            ->with('bookingSource')
            ->orderBy('check_out', 'desc')
            ->get()
            ->keyBy('room_id');

        // Bonus category (dasar bonus: pk/sales/umum/online/kos/kosong) is decided and stored
        // on the WorkOrder created alongside each CleaningTask (see CleaningTask::boot()), but
        // there's no FK -- link back via the "Task ID: X" marker WorkOrder::notes always has.
        $workOrders = \App\Models\WorkOrder::where('hotel_id', $hotelId)
            ->where('type', 'cleaning')
            ->whereBetween('completed_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
            ->get();
        $bonusCategoryByTaskId = [];
        foreach ($workOrders as $wo) {
            if ($wo->notes && preg_match('/Task ID: (\d+)/', $wo->notes, $m)) {
                $bonusCategoryByTaskId[(int) $m[1]] = $wo->bonus_category;
            }
        }

        // Build detail rows + nested summary per staff (source, room type, bonus category)
        $details = [];
        $summary = [];
        foreach ($tasks as $task) {
            $room = $task->room;
            $booking = $lastBookings->get($task->room_id);
            $source = $booking && $booking->bookingSource ? $booking->bookingSource->name : '-';
            $roomType = $room && $room->roomType ? $room->roomType->name : '-';
            $bonusCategoryKey = $bonusCategoryByTaskId[$task->id] ?? null;
            $bonusCategoryLabel = self::BONUS_CATEGORY_LABELS[$bonusCategoryKey] ?? 'Belum terklasifikasi';
            $staffName = $task->assignedUser ? $task->assignedUser->name : 'Unknown';

            $details[] = [
                'tanggal' => $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-',
                'kamar' => $room ? ($room->room_number . ($room->roomType ? ' (' . $roomType . ')' : '')) : '-',
                'staff' => $staffName,
                'sumber' => $source,
                'kategori' => $roomType,
                'kategori_bonus' => $bonusCategoryLabel,
                'status_verifikasi' => ucfirst(str_replace('_', ' ', $task->status)),
            ];

            if (!isset($summary[$staffName])) {
                $summary[$staffName] = ['total' => 0, 'by_source' => [], 'by_room_type' => [], 'by_bonus_category' => []];
            }
            $summary[$staffName]['total']++;
            $summary[$staffName]['by_source'][$source] = ($summary[$staffName]['by_source'][$source] ?? 0) + 1;
            $summary[$staffName]['by_room_type'][$roomType] = ($summary[$staffName]['by_room_type'][$roomType] ?? 0) + 1;
            $summary[$staffName]['by_bonus_category'][$bonusCategoryLabel] = ($summary[$staffName]['by_bonus_category'][$bonusCategoryLabel] ?? 0) + 1;
        }

        $summaryRows = [];
        foreach ($summary as $nama => $data) {
            $summaryRows[] = [
                'nama' => $nama,
                'jumlah' => $data['total'],
                'by_source' => $data['by_source'],
                'by_room_type' => $data['by_room_type'],
                'by_bonus_category' => $data['by_bonus_category'],
            ];
        }
        usort($summaryRows, fn($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return [
            'details' => $details,
            'summary' => $summaryRows,
            'total_tasks' => count($details),
        ];
    }
}
