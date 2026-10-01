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
            $roomId = $request->input('room_id');
            $date = $request->input('date');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $sortBy = $request->input('sort_by', 'date_desc');

            // Support date range (dari tanggal - sampai tanggal), single-day view, or full month view
            if ($startDate && $endDate) {
                $periodStart = Carbon::parse($startDate)->startOfDay();
                $periodEnd = Carbon::parse($endDate)->endOfDay();
            } elseif ($request->filled('date')) {
                $parsedDate = Carbon::parse($date);
                $periodStart = $parsedDate->copy()->startOfDay();
                $periodEnd = $parsedDate->copy()->endOfDay();
            } else {
                $periodStart = Carbon::parse($month)->startOfMonth();
                $periodEnd = Carbon::parse($month)->endOfMonth();
            }

            $reportData = $this->buildReport($hotelId, $periodStart, $periodEnd, $staffId, $sortBy, $roomId);

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

            $roomList = \App\Models\Room::where('hotel_id', $hotelId)
                ->orderBy('room_number')
                ->get(['id', 'room_number']);

            return view('reports.housekeeping_report', compact(
                'reportData', 'month', 'date', 'startDate', 'endDate', 'sortBy', 'staffId', 'staffList', 'roomId', 'roomList', 'periodStart', 'periodEnd'
            ));
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
            $roomId = $request->input('room_id');
            $date = $request->input('date');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if ($startDate && $endDate) {
                $periodStart = Carbon::parse($startDate)->startOfDay();
                $periodEnd = Carbon::parse($endDate)->endOfDay();
                $periodLabel = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
            } elseif ($request->filled('date')) {
                $parsedDate = Carbon::parse($date);
                $periodStart = $parsedDate->copy()->startOfDay();
                $periodEnd = $parsedDate->copy()->endOfDay();
                $periodLabel = $parsedDate->format('d/m/Y');
            } else {
                $periodStart = Carbon::parse($month)->startOfMonth();
                $periodEnd = Carbon::parse($month)->endOfMonth();
                $periodLabel = Carbon::parse($month)->translatedFormat('F Y');
            }

            $reportData = $this->buildReport($hotelId, $periodStart, $periodEnd, $staffId, 'date_desc', $roomId);
            $hotel = \App\Models\Hotel::find($hotelId);
            $monthObj = Carbon::parse($month);

            $pdf = Pdf::loadView('reports.housekeeping_report_pdf', compact('reportData', 'monthObj', 'hotel', 'periodLabel'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan_housekeeping_' . ($startDate ? $startDate . '_to_' . $endDate : ($date ?: $month)) . '.pdf');
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
            $roomId = $request->input('room_id');
            $date = $request->input('date');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if ($startDate && $endDate) {
                $periodStart = Carbon::parse($startDate)->startOfDay();
                $periodEnd = Carbon::parse($endDate)->endOfDay();
            } elseif ($request->filled('date')) {
                $parsedDate = Carbon::parse($date);
                $periodStart = $parsedDate->copy()->startOfDay();
                $periodEnd = $parsedDate->copy()->endOfDay();
            } else {
                $periodStart = Carbon::parse($month)->startOfMonth();
                $periodEnd = Carbon::parse($month)->endOfMonth();
            }

            return Excel::download(
                new HousekeepingReportExport($periodStart, $periodEnd, $staffId, $roomId),
                'laporan_housekeeping_' . ($startDate ? $startDate . '_to_' . $endDate : ($date ?: $month)) . '.xlsx'
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private const BONUS_CATEGORY_LABELS = [
        'pk' => 'PK (Perintah Khusus)',
        'sales' => 'Sales',
        'sales_cat' => 'Sales',
        'umum' => 'Umum',
        'online' => 'Online',
        'kos' => 'Kost',
        'kost' => 'Kost',
        'kosong' => 'Kosong',
    ];

    private function buildReport(int $hotelId, Carbon $monthStart, Carbon $monthEnd, ?int $staffId = null, string $sortBy = 'date_desc', ?int $roomId = null): array
    {
        $orderDir = ($sortBy === 'date_asc') ? 'asc' : 'desc';

        $tasks = CleaningTask::where('hotel_id', $hotelId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('completed_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                  ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                      $q2->whereNull('completed_at')
                         ->whereBetween('updated_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()]);
                  });
            })
            ->when($staffId, fn($q) => $q->where('assigned_to', $staffId))
            ->when($roomId, fn($q) => $q->where('room_id', $roomId))
            ->with(['room.roomType', 'assignedUser'])
            ->orderByRaw('COALESCE(completed_at, updated_at) ' . $orderDir)
            ->get();

        if ($sortBy === 'staff') {
            $tasks = $tasks->sortBy(fn($t) => strtolower($t->assignedUser?->name ?? ''))->values();
        }

        // Preload bookings per room for booking source & category detection
        $roomIds = $tasks->pluck('room_id')->filter()->unique();
        $bookingsByRoom = \App\Models\Booking::whereIn('room_id', $roomIds)
            ->with(['bookingSource', 'guest.guestCategory'])
            ->orderBy('check_out', 'desc')
            ->get()
            ->groupBy('room_id');

        // Bonus category (dasar bonus: pk/sales/umum/online/kos/kosong)
        $workOrders = \App\Models\WorkOrder::where('hotel_id', $hotelId)
            ->where('type', 'cleaning')
            ->whereBetween('completed_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
            ->get();
        $bonusCategoryByTaskId = [];
        foreach ($workOrders as $wo) {
            if ($wo->notes && preg_match('/Task ID: (\d+)/', $wo->notes, $m)) {
                $bonusCategoryByTaskId[(int) $m[1]] = $wo->bonus_category;
            }
        }

        // Build detail rows + nested summary per staff
        $details = [];
        $summary = [];
        foreach ($tasks as $task) {
            $room = $task->room;
            $roomBookings = $bookingsByRoom->get($task->room_id, collect());
            $booking = $roomBookings->first(function ($b) use ($task) {
                if (!$task->completed_at) return true;
                return $b->check_out <= $task->completed_at || ($b->check_in <= $task->completed_at && $b->check_out >= $task->completed_at);
            }) ?? $roomBookings->first();

            $rawSource = null;
            if ($booking) {
                if ($booking->bookingSource) {
                    $rawSource = $booking->bookingSource->name;
                } elseif ($booking->source) {
                    $rawSource = $booking->source;
                }

                $guestCategoryName = $booking->guest?->guestCategory?->name;
                if ($guestCategoryName && (str_contains(strtolower($guestCategoryName), 'sales') || !empty($booking->guest?->company_name))) {
                    if (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', 'umum', ''])) {
                        $rawSource = 'Sales';
                    }
                }

                if ($booking->guest_type === 'sales' && (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', 'umum', '']))) {
                    $rawSource = 'Sales';
                }
            }

            if (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', ''])) {
                $source = 'UMUM';
            } else {
                $source = strtoupper(trim($rawSource));
            }

            $roomType = $room && $room->roomType ? $room->roomType->name : '-';
            
            // Determine bonus category
            $bonusCategoryKey = $bonusCategoryByTaskId[$task->id] ?? null;
            if (!$bonusCategoryKey || $bonusCategoryKey === 'umum') {
                if ($source === 'SALES' || ($booking && $booking->guest_type === 'sales')) {
                    $bonusCategoryKey = 'sales';
                } elseif ($source === 'KOST' || ($booking && $booking->guest_type === 'kos') || ($booking && in_array($booking->stay_type, ['monthly', 'yearly']))) {
                    $bonusCategoryKey = 'kos';
                } elseif (in_array($source, ['TRAVELOKA', 'TIKET.COM', 'AGODA', 'BOOKING.COM', 'AIRBNB', 'ONLINE']) || ($booking && $booking->guest_type === 'online')) {
                    $bonusCategoryKey = 'online';
                }
            }
            $bonusCategoryLabel = self::BONUS_CATEGORY_LABELS[$bonusCategoryKey] ?? 'Belum terklasifikasi';
            $staffName = $task->assignedUser ? $task->assignedUser->name : 'Unknown';

            $details[] = [
                'tanggal' => $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-',
                'kamar' => $room ? ('Kamar ' . $room->room_number . ($roomType !== '-' ? ' (' . $roomType . ')' : '')) : '-',
                'nama_tamu' => $booking?->guest?->name ?? '-',
                'staff' => $staffName,
                'sumber' => $source,
                'kategori' => $roomType,
                'kategori_bonus' => $bonusCategoryLabel,
                'status_verifikasi' => 'Selesai / Approved',
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
        $groupedByStaff = [];
        foreach ($details as $d) {
            $groupedByStaff[$d['staff']][] = $d;
        }

        return [
            'details' => $details,
            'grouped_by_staff' => $groupedByStaff,
            'summary' => $summaryRows,
            'total_tasks' => count($details),
        ];
    }
}
