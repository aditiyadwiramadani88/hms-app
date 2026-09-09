<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the report selection page.
     */
    public function index()
    {
        return view('reports.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        return match ($request->report_type) {
            'occupancy' => redirect()->route('reports.occupancy', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]),
            'revenue' => redirect()->route('reports.revenue', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]),
            'transactions' => redirect()->route('reports.transactions', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]),
            'room_activity' => redirect()->route('reports.room_activity', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'start_time' => $request->start_time, 'end_time' => $request->end_time, 'activity_type' => $request->activity_type]),
            default => redirect()->route('reports.index')->with('error', 'Invalid report type'),
        };
    }

    /**
     * Display Room Check-in / Check-out activity report
     */
    public function roomActivity(Request $request)
    {
        $hotelDate = function_exists('get_hotel_date') ? get_hotel_date() : now()->format('Y-m-d');
        $startDate = $request->filled('start_date') ? $request->start_date : Carbon::parse($hotelDate)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : Carbon::parse($hotelDate)->format('Y-m-d');
        
        $startTime = $request->filled('start_time') ? $request->start_time : '00:00';
        $endTime = $request->filled('end_time') ? $request->end_time : '23:59';
        
        $activityType = $request->filled('activity_type') ? $request->activity_type : 'all'; // all, checkin, checkout
        $search = $request->query('search');

        $startDateTime = Carbon::parse("$startDate $startTime");
        $endDateTime = Carbon::parse("$endDate $endTime")->endOfMinute();

        $query = \App\Models\Booking::with(['guest', 'room', 'user'])
            ->where('hotel_id', active_hotel_id())
            ->where(function($q) use ($startDateTime, $endDateTime, $activityType) {
                if ($activityType === 'checkin' || $activityType === 'all') {
                    $q->orWhereBetween('actual_check_in', [$startDateTime, $endDateTime]);
                }
                if ($activityType === 'checkout' || $activityType === 'all') {
                    $q->orWhereBetween('actual_check_out', [$startDateTime, $endDateTime]);
                }
            })
            ->when($search, function($q) use ($search) {
                $q->where(function($subQ) use ($search) {
                    $subQ->whereHas('guest', function($guestQ) use ($search) {
                        $guestQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('room', function($roomQ) use ($search) {
                        $roomQ->where('name', 'like', "%{$search}%")
                              ->orWhere('room_number', 'like', "%{$search}%");
                    })
                    ->orWhere('custom_room_name', 'like', "%{$search}%");
                });
            });

        $activities = $query->orderBy('updated_at', 'desc')->paginate(50);

        if ($request->ajax()) {
            return view('reports.partials.room_activity_table', compact('activities', 'startDate', 'endDate', 'startTime', 'endTime', 'activityType', 'search'));
        }

        return view('reports.room_activity', compact('activities', 'startDate', 'endDate', 'startTime', 'endTime', 'activityType', 'search'));
    }

    /**
     * Export room activity report to Excel.
     */
    public function exportRoomActivity(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $startTime = $request->query('start_time', '00:00');
        $endTime = $request->query('end_time', '23:59');
        $activityType = $request->query('activity_type', 'all');
        $search = $request->query('search');

        $filename = 'room_activity_report_' . ($startDate ?? 'all') . '_to_' . ($endDate ?? 'all') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RoomActivityExport($startDate, $endDate, $startTime, $endTime, $activityType, $search),
            $filename
        );
    }

    /**
     * Display the occupancy report.
     */
    public function occupancy(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $report = $this->reportService->getOccupancyReport($startDate, $endDate);

        if ($request->ajax()) {
            return view('reports.partials.occupancy_table', compact('report', 'startDate', 'endDate'));
        }

        return view('reports.occupancy', compact('report', 'startDate', 'endDate'));
    }

    /**
     * Display the revenue report.
     */
    public function revenue(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $report = $this->reportService->getRevenueReport($startDate, $endDate);
        $roomTypePerformance = $this->reportService->getRoomTypePerformance($startDate, $endDate);
        $sourcePerformance = \App\Models\Booking::query()
            ->leftJoin('booking_sources', 'bookings.booking_source_id', '=', 'booking_sources.id')
            ->whereBetween('bookings.check_in', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('bookings.status', ['cancelled', 'no_show'])
            ->selectRaw("COALESCE(booking_sources.name, bookings.source, 'Tidak Ada') as source_name")
            ->selectRaw("COALESCE(booking_sources.color, 'secondary') as source_color")
            ->selectRaw('COUNT(bookings.id) as total_bookings')
            ->selectRaw('SUM(bookings.total_price) as total_revenue')
            ->groupBy('source_name', 'source_color')
            ->orderByDesc('total_revenue')
            ->get();

        if ($request->ajax()) {
            return view('reports.partials.revenue_table', compact('report', 'roomTypePerformance', 'sourcePerformance', 'startDate', 'endDate'));
        }

        return view('reports.revenue', compact('report', 'roomTypePerformance', 'sourcePerformance', 'startDate', 'endDate'));
    }

    /**
     * Display the comprehensive transactions report.
     */
    public function transactions(Request $request)
    {
        // Default to start of current month and today based on the transactions in DB
        // or use get_hotel_date() if available
        $hotelDate = function_exists('get_hotel_date') ? get_hotel_date() : now()->format('Y-m-d');
        
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::parse($hotelDate)->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::parse($hotelDate)->endOfDay();

        $type = $request->query('type');

        $query = \App\Models\Transaction::with(['guest', 'booking.room', 'booking.bookingSource', 'user', 'bankAccount'])
            ->where('hotel_id', active_hotel_id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->when($type === 'deposit', fn($q) => $q->where('is_deposit', true))
            ->when($type && $type !== 'deposit', fn($q) => $q->where('type', $type));

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);
        
        $totals = [
            'income' => \App\Models\Transaction::where('hotel_id', active_hotel_id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'payment')
                ->where(function($q) {
                    $q->where('is_markup', false)->orWhereNull('is_markup');
                })
                ->sum('amount'),
            'charge' => \App\Models\Transaction::where('hotel_id', active_hotel_id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'charge')
                ->where(function($q) {
                    $q->where('is_markup', false)->orWhereNull('is_markup');
                })
                ->sum('amount'),
            'refund' => \App\Models\Transaction::where('hotel_id', active_hotel_id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'refund')->sum('amount'),
            'expense' => \App\Models\Transaction::where('hotel_id', active_hotel_id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'expense')
                ->where('status', 'success')
                ->sum('amount'),
        ];

        if ($request->ajax()) {
            return view('reports.partials.transactions_table', compact('transactions', 'startDate', 'endDate', 'totals', 'type'));
        }

        return view('reports.transactions', compact('transactions', 'startDate', 'endDate', 'totals', 'type'));
    }

    /**
     * Export transactions report to Excel.
     */
    public function exportTransactions(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $type = $request->query('type');

        $filename = 'transactions_report_' . ($startDate ?? 'all') . '_to_' . ($endDate ?? 'all') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TransactionsReportExport($startDate, $endDate, $type),
            $filename
        );
    }

    /**
     * Export a report to Excel.
     */
    public function exportExcel(string $type, Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        try {
            return $this->reportService->exportToExcel($type, $startDate, $endDate);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('reports.index')
                ->with('error', 'Invalid report type: '.$type);
        }
    }

    /**
     * Export a report to PDF.
     */
    public function exportPDF(string $type, Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        try {
            return $this->reportService->exportToPDF($type, $startDate, $endDate);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('reports.index')
                ->with('error', 'Invalid report type: '.$type);
        }
    }

    /**
     * Laporan Transfer Online — transaksi bank transfer per bulan.
     */
    public function transferOnline(Request $request)
    {
        $month = $request->filled('month') ? Carbon::parse($request->month) : now();

        $transactions = \App\Models\Transaction::with(['booking.room', 'booking.guest', 'guest', 'bankAccount'])
            ->where('hotel_id', active_hotel_id())
            ->where('type', 'payment')
            ->where('payment_method', 'bank_transfer')
            ->where('status', 'success')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->where(function ($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Summary per bank account
        $summaryPerBank = $transactions->groupBy('bank_account_id')->map(function ($items, $bankId) {
            $bank = $items->first()->bankAccount;
            return [
                'bank_name' => $bank->name ?? 'Tanpa Akun',
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        });

        $grandTotal = $transactions->sum('amount');

        return view('reports.transfer-online', compact('transactions', 'summaryPerBank', 'grandTotal', 'month'));
    }

    /**
     * Laporan Bonus Karyawan — Laporan Harian Grid (1-31).
     */
    public function bonusKaryawan(Request $request)
    {
        $month = $request->filled('month') ? Carbon::parse($request->month) : now()->startOfMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        $daysInMonth = $month->daysInMonth;

        $bookings = \App\Models\Booking::with(['bookingSource', 'room.roomType'])
            ->where('hotel_id', active_hotel_id())
            ->whereBetween('check_in', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        $transactions = \App\Models\Transaction::where('hotel_id', active_hotel_id())
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('type', ['charge', 'payment'])
            ->get();
            
        $posOrders = \App\Models\PosOrder::with(['items'])
            ->where('hotel_id', active_hotel_id())
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->get();

        // Initialize grid
        $grid = [];
        $details = []; // per-day per-category breakdown for modal
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $grid[$i] = [
                'sales' => 0, 'kost' => 0, 'agoda' => 0, 'reddoors' => 0, 'traveloka' => 0, 'umum' => 0,
                'total_kamar' => 0,
                'kantin' => 0, 'overtime' => 0, 'pijat' => 0, 'motor' => 0, 'laundry' => 0, 
                'bed_hs_1' => 0, 'bed_hs_2' => 0, 'handuk_kipas' => 0,
                'tamu' => 0,
            ];
            $details[$i] = [
                'sales' => [], 'kost' => [], 'agoda' => [], 'reddoors' => [], 'traveloka' => [], 'umum' => [],
                'kantin' => [], 'overtime' => [], 'pijat' => [], 'motor' => [], 'laundry' => [],
                'bed_hs_1' => [], 'bed_hs_2' => [], 'handuk_kipas' => [],
            ];
        }

        $otaMapping = [
            'agoda' => 'agoda', 'reddoorz' => 'reddoors', 'reddoors' => 'reddoors',
            'traveloka' => 'traveloka', 'tiket.com' => 'umum', 'booking.com' => 'umum', 'airbnb' => 'umum',
        ];

        foreach ($bookings as $booking) {
            $day = Carbon::parse($booking->check_in)->day;
            $sourceName = strtolower($booking->bookingSource?->name ?? '');
            $stayType = $booking->stay_type;
            $price = $booking->total_price;

            // Kamar dibersihkan tetap harus keluar nominal bonus meski Grand Total
            // Rp0 (voucher gratis / diskon penuh) -- pakai tarif dasar tipe kamar,
            // bukan nominal yang dibayar tamu.
            if ((float) $price <= 0 && $booking->room?->roomType) {
                $roomType = $booking->room->roomType;
                $price = ($stayType === 'monthly' && (float) $roomType->monthly_price > 0)
                    ? $roomType->monthly_price
                    : $roomType->base_price;
            }

            $grid[$day]['tamu'] += ($booking->adults + $booking->children);

            if ($stayType === 'monthly') {
                $grid[$day]['kost'] += $price;
                $details[$day]['kost'][] = [
                    'id' => $booking->id,
                    'room' => $booking->room?->room_number ?? '-',
                    'guest' => $booking->guest?->name ?? '-',
                    'amount' => (int) $price,
                ];
                continue;
            }

            // Check booking source name for OTA mapping
            $mapped = false;
            foreach ($otaMapping as $key => $col) {
                if (str_contains($sourceName, $key)) {
                    $grid[$day][$col] += $price;
                    $details[$day][$col][] = [
                        'id' => $booking->id,
                        'room' => $booking->room?->room_number ?? '-',
                        'guest' => $booking->guest?->name ?? '-',
                        'amount' => (int) $price,
                    ];
                    $mapped = true;
                    break;
                }
            }
            if ($mapped) continue;

            // Fallback: sales vs umum based on source type
            $col = ($booking->source === 'walk_in' && str_contains($sourceName, 'sales')) ? 'sales' : 'umum';
            $grid[$day][$col] += $price;
            $details[$day][$col][] = [
                'id' => $booking->id,
                'room' => $booking->room?->room_number ?? '-',
                'guest' => $booking->guest?->name ?? '-',
                'amount' => (int) $price,
            ];
        }
        
        // Sum total kamar per day
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $grid[$i]['total_kamar'] = $grid[$i]['sales'] + $grid[$i]['kost'] + $grid[$i]['agoda'] + $grid[$i]['reddoors'] + $grid[$i]['traveloka'] + $grid[$i]['umum'];
        }

        // Process Transactions for extra services
        foreach ($transactions as $tx) {
            $day = Carbon::parse($tx->created_at)->day;
            $desc = strtolower($tx->description ?? '');
            $amount = $tx->amount;

            if (str_contains($desc, 'overtime') || str_contains($desc, 'lewat') || str_contains($desc, 'telat')) { $grid[$day]['overtime'] += $amount; $details[$day]['overtime'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'pijat') || str_contains($desc, 'massage')) { $grid[$day]['pijat'] += $amount; $details[$day]['pijat'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'motor') || str_contains($desc, 'sewa')) { $grid[$day]['motor'] += $amount; $details[$day]['motor'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'laundry') || str_contains($desc, 'cuci')) { $grid[$day]['laundry'] += $amount; $details[$day]['laundry'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'bed hs 1') || str_contains($desc, 'ekstra bed 1')) { $grid[$day]['bed_hs_1'] += $amount; $details[$day]['bed_hs_1'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'bed hs 2') || str_contains($desc, 'ekstra bed 2')) { $grid[$day]['bed_hs_2'] += $amount; $details[$day]['bed_hs_2'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'bed')) { $grid[$day]['bed_hs_1'] += $amount; $details[$day]['bed_hs_1'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'handuk') || str_contains($desc, 'kipas') || str_contains($desc, 'towel') || str_contains($desc, 'fan')) { $grid[$day]['handuk_kipas'] += $amount; $details[$day]['handuk_kipas'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
            elseif (str_contains($desc, 'kantin') || str_contains($desc, 'makan') || str_contains($desc, 'minum') || str_contains($desc, 'mie') || str_contains($desc, 'nasi') || str_contains($desc, 'kopi') || str_contains($desc, 'teh') || str_contains($desc, 'susu') || str_contains($desc, 'goreng') || str_contains($desc, 'ayam') || str_contains($desc, 'bakso'))
            { $grid[$day]['kantin'] += $amount; $details[$day]['kantin'][] = ['desc' => $tx->description, 'amount' => (int) $amount]; }
        }
        
        // Process POS Orders for Kantin & Services
        $foodKeywords = ['makan', 'minum', 'mie', 'nasi', 'kopi', 'teh', 'susu', 'goreng', 'ayam', 'bakso', 'es', 'juice', 'soto', 'sop', 'ikan', 'telur', 'roti', 'snack', 'camilan', 'air', 'mineral', 'soda', 'pop', 'ice', 'sari', 'buah', 'bubur', 'sate', 'rendang', 'tahu', 'tempe', 'sayur', 'lalapan'];
        foreach ($posOrders as $order) {
            $day = Carbon::parse($order->created_at)->day;
            
            foreach ($order->items as $item) {
                $itemName = strtolower($item->item_name);
                $isFood = false;
                foreach ($foodKeywords as $kw) {
                    if (str_contains($itemName, $kw)) { $isFood = true; break; }
                }
                if ($isFood) {
                    $grid[$day]['kantin'] += $item->subtotal;
                    $details[$day]['kantin'][] = ['desc' => $item->item_name . ' x' . $item->quantity, 'amount' => (int) $item->subtotal];
                } elseif (str_contains($itemName, 'pijat') || str_contains($itemName, 'massage')) {
                    $grid[$day]['pijat'] += $item->subtotal;
                    $details[$day]['pijat'][] = ['desc' => $item->item_name . ' x' . $item->quantity, 'amount' => (int) $item->subtotal];
                } elseif (str_contains($itemName, 'laundry') || str_contains($itemName, 'cuci')) {
                    $grid[$day]['laundry'] += $item->subtotal;
                    $details[$day]['laundry'][] = ['desc' => $item->item_name . ' x' . $item->quantity, 'amount' => (int) $item->subtotal];
                } elseif (str_contains($itemName, 'sewa') || str_contains($itemName, 'motor')) {
                    $grid[$day]['motor'] += $item->subtotal;
                    $details[$day]['motor'][] = ['desc' => $item->item_name . ' x' . $item->quantity, 'amount' => (int) $item->subtotal];
                }
            }
        }

        // Calculate Grand Totals
        $totals = [
            'sales' => array_sum(array_column($grid, 'sales')),
            'kost' => array_sum(array_column($grid, 'kost')),
            'agoda' => array_sum(array_column($grid, 'agoda')),
            'reddoors' => array_sum(array_column($grid, 'reddoors')),
            'traveloka' => array_sum(array_column($grid, 'traveloka')),
            'umum' => array_sum(array_column($grid, 'umum')),
            'total_kamar' => array_sum(array_column($grid, 'total_kamar')),
            'kantin' => array_sum(array_column($grid, 'kantin')),
            'overtime' => array_sum(array_column($grid, 'overtime')),
            'pijat' => array_sum(array_column($grid, 'pijat')),
            'motor' => array_sum(array_column($grid, 'motor')),
            'laundry' => array_sum(array_column($grid, 'laundry')),
            'bed_hs_1' => array_sum(array_column($grid, 'bed_hs_1')),
            'bed_hs_2' => array_sum(array_column($grid, 'bed_hs_2')),
            'handuk_kipas' => array_sum(array_column($grid, 'handuk_kipas')),
            'tamu' => array_sum(array_column($grid, 'tamu')),
        ];

        return view('reports.bonus-karyawan', compact(
            'grid', 'totals', 'month', 'daysInMonth', 'details'
        ));
    }
}
