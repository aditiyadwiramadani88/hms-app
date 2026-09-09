<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CleaningTask;
use App\Models\User;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OBPerformanceReportController extends Controller
{
    use AjaxResponse;

    public function index(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->filled('month') ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();
            $staffId = $request->input('staff_id');

            $reportData = $this->buildReport($hotelId, $month, $staffId);
            $hotel = current_hotel();

            // Staff list for filter dropdown
            $staffList = User::whereHas('roles', function ($q) {
                    $q->where('name', 'like', '%ob%')
                      ->orWhere('name', 'like', '%housekeeping%')
                      ->orWhere('name', 'like', '%cleaner%');
                })
                ->whereHas('hotels', fn($q) => $q->where('hotel_id', $hotelId))
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('reports.ob_performance', compact('reportData', 'month', 'hotel', 'staffList', 'staffId'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function buildReport(int $hotelId, Carbon $month, ?int $staffId = null): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // Identify OB users from roles linked to this hotel
        $obUserIds = User::whereHas('roles', function ($q) {
                $q->where('name', 'like', '%ob%')
                  ->orWhere('name', 'like', '%housekeeping%')
                  ->orWhere('name', 'like', '%cleaner%');
            })
            ->whereHas('hotels', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->when($staffId, fn($q) => $q->where('id', $staffId))
            ->pluck('id')
            ->toArray();

        if (empty($obUserIds)) {
            return [
                'rows' => [],
                'totals' => ['sales' => 0, 'umum' => 0, 'online' => 0, 'kost' => 0, 'sales_cat' => 0, 'pk' => 0, 'kosong' => 0],
                'details' => [],
            ];
        }

        // Query cleaning tasks completed by OB users in this month
        $cleaningTasks = CleaningTask::with(['room', 'assignedUser'])
            ->where('hotel_id', $hotelId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->whereIn('assigned_to', $obUserIds)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->get();

        // Query bookings overlapping this month (for PK & category classification)
        $activeBookings = Booking::with(['bookingSource'])
            ->where('hotel_id', $hotelId)
            ->where('check_in', '<=', $endOfMonth)
            ->where('check_out', '>=', $startOfMonth->copy()->subMonth())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        // Build lookup: bookings indexed by room_id
        $bookingsByRoom = [];
        foreach ($activeBookings as $booking) {
            $bookingsByRoom[$booking->room_id][] = $booking;
        }

        // Group tasks by assigned_to
        $grouped = $cleaningTasks->groupBy('assigned_to');

        $obUserMap = User::whereIn('id', $obUserIds)->get()->keyBy('id');

        $rows = [];
        $totals = ['sales' => 0, 'umum' => 0, 'online' => 0, 'kost' => 0, 'sales_cat' => 0, 'pk' => 0, 'kosong' => 0];

        foreach ($obUserMap as $userId => $user) {
            $tasks = $grouped->get($userId, collect());
            $counts = ['umum' => 0, 'online' => 0, 'kost' => 0, 'sales_cat' => 0, 'pk' => 0, 'kosong' => 0];

            foreach ($tasks as $task) {
                $category = CleaningTask::classifyRoomBonusCategory($task->room_id, $task->completed_at, $task->hotel_id, $bookingsByRoom)['category'];
                $counts[$category]++;
            }

            $sales = $counts['umum'] + $counts['online'] + $counts['kost'] + $counts['sales_cat'] + $counts['pk'];

            $rows[] = [
                'nama' => $user->name,
                'sales' => $sales,
                'umum' => $counts['umum'],
                'online' => $counts['online'],
                'kost' => $counts['kost'],
                'sales_cat' => $counts['sales_cat'],
                'pk' => $counts['pk'],
                'kosong' => $counts['kosong'],
            ];

            $totals['sales'] += $sales;
            $totals['umum'] += $counts['umum'];
            $totals['online'] += $counts['online'];
            $totals['kost'] += $counts['kost'];
            $totals['sales_cat'] += $counts['sales_cat'];
            $totals['pk'] += $counts['pk'];
            $totals['kosong'] += $counts['kosong'];
        }

        // Sort alphabetically by name
        usort($rows, fn($a, $b) => strcmp($a['nama'], $b['nama']));

        // Build task detail rows
        $details = [];
        foreach ($cleaningTasks->sortBy('completed_at') as $task) {
            $classified = CleaningTask::classifyRoomBonusCategory($task->room_id, $task->completed_at, $task->hotel_id, $bookingsByRoom);
            $category = $classified['category'];
            $booking = $classified['booking'];
            $sourceName = $booking?->bookingSource?->name ?? $booking?->source ?? null;
            $details[] = [
                'tanggal' => $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-',
                'kamar' => $task->room?->room_number ?? '-',
                'staff' => $task->assignedUser?->name ?? '-',
                'sumber' => $category === 'pk'
                    ? ($sourceName ?: 'In-House')
                    : ($sourceName ?: ($category === 'kosong' ? 'Kosong' : '-')),
                'kategori' => $category === 'pk' ? 'PK' : ($category === 'sales_cat' ? 'Sales' : ucfirst($category)),
                'status_verifikasi' => $task->verification_status ?? '-',
            ];
        }

        return compact('rows', 'totals', 'details');
    }

    public function exportPdf(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->filled('month') ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();
            $staffId = $request->input('staff_id');

            $reportData = $this->buildReport($hotelId, $month, $staffId);
            $hotel = current_hotel();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.ob_performance_pdf', [
                'reportData' => $reportData,
                'month' => $month,
                'hotel' => $hotel,
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('laporan_performa_ob_' . $month->format('Y_m') . '.pdf');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
