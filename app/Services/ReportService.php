<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PosOrder;
use App\Models\Room;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    /**
     * Get occupancy statistics for a date range.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     */
    public function getOccupancyReport($startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
        $hotelId = session('active_hotel_id');

        $query = Room::query();
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }
        $totalRooms = $query->count();

        $outOfOrderQuery = Room::query();
        if ($hotelId) {
            $outOfOrderQuery->where('hotel_id', $hotelId);
        }
        $outOfOrder = $outOfOrderQuery->whereIn('status', ['out_of_order', 'maintenance'])->count();
        $operationalRooms = $totalRooms - $outOfOrder;

        // Get bookings overlapping with date range
        $bookingsQuery = Booking::withoutGlobalScopes()->where(function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
                ->where('check_in', '<=', $endDate)
                ->where('check_out', '>=', $startDate);
        });
        if ($hotelId) {
            $bookingsQuery->where('hotel_id', $hotelId);
        }
        $bookings = $bookingsQuery->get();

        $roomsByType = Room::select('room_type_id', DB::raw('count(*) as total'))
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereNotIn('status', ['out_of_order', 'maintenance'])
            ->groupBy('room_type_id')
            ->get();
        $roomTypes = \App\Models\RoomType::whereIn('id', $roomsByType->pluck('room_type_id'))->get()->keyBy('id');

        // Calculate daily occupancy (overall + per room type, so a period like "this month"
        // reflects occupancy across the whole range instead of only the last day -- a type
        // fully booked earlier in the month but empty on the snapshot day was wrongly showing 0).
        $dailyOccupancy = [];
        $current = $startDate->copy();
        $totalOccupiedNights = 0;
        $totalAvailableNights = 0;
        $occupiedNightsByType = [];

        while ($current <= $endDate) {
            // Count distinct rooms, not booking rows -- a same-day guest turnover
            // (checkout AM + new check-in PM on the same room) is 2 booking rows
            // but only 1 room occupied that day.
            $occupiedOnDate = Booking::occupiedOn($current)
                ->whereNotNull('room_id')
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->distinct('room_id')
                ->count('room_id');

            $rate = $operationalRooms > 0 ? round(($occupiedOnDate / $operationalRooms) * 100, 2) : 0;

            $dailyOccupancy[] = [
                'date' => $current->toDateString(),
                'occupied' => $occupiedOnDate,
                'available' => max(0, $operationalRooms - $occupiedOnDate),
                'occupancy_rate' => $rate,
            ];

            $totalOccupiedNights += $occupiedOnDate;
            $totalAvailableNights += max(0, $operationalRooms - $occupiedOnDate);

            $occupiedByTypeOnDate = Booking::occupiedOn($current)
                ->whereNotNull('bookings.room_id')
                ->when($hotelId, fn($q) => $q->where('bookings.hotel_id', $hotelId))
                ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
                ->select('rooms.room_type_id', DB::raw('count(distinct bookings.room_id) as occupied'))
                ->groupBy('rooms.room_type_id')
                ->pluck('occupied', 'room_type_id');
            foreach ($occupiedByTypeOnDate as $typeId => $count) {
                $occupiedNightsByType[$typeId] = ($occupiedNightsByType[$typeId] ?? 0) + (int) $count;
            }

            $current->addDay();
        }

        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        $avgOccupancyRate = collect($dailyOccupancy)->avg('occupancy_rate');

        // Average occupied rooms per day across the period (not just the last day)
        $occupiedSnapshot = $daysInPeriod > 0 ? (int) round($totalOccupiedNights / $daysInPeriod) : 0;
        $availableSnapshot = max(0, $operationalRooms - $occupiedSnapshot);
        $occupancyRateSnapshot = round($avgOccupancyRate ?? 0, 1);

        $roomTypeBreakdown = [];
        foreach ($roomsByType as $row) {
            $typeId = $row->room_type_id;
            $total = (int) $row->total;
            $nightsOccupied = $occupiedNightsByType[$typeId] ?? 0;
            $occupied = $daysInPeriod > 0 ? (int) round($nightsOccupied / $daysInPeriod) : 0;
            $available = max(0, $total - $occupied);
            $rtOccupancyRate = ($total * $daysInPeriod) > 0 ? round(($nightsOccupied / ($total * $daysInPeriod)) * 100, 1) : 0;
            $roomTypeBreakdown[] = [
                'name' => $roomTypes->get($typeId)?->name ?? 'Unknown',
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $rtOccupancyRate,
            ];
        }

        // Sort by occupancy rate descending
        usort($roomTypeBreakdown, fn($a, $b) => $b['occupancy_rate'] <=> $a['occupancy_rate']);

        // Current room status breakdown
        $statusBreakdown = Room::select('status', DB::raw('count(*) as count'))
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_rooms' => $totalRooms,
            'operational_rooms' => $operationalRooms,
            'out_of_order' => $outOfOrder,
            'total_bookings' => $bookings->count(),
            'occupied_room_nights' => $totalOccupiedNights,
            'available_room_nights' => $totalAvailableNights,
            'average_occupancy_rate' => round($avgOccupancyRate ?? 0, 2),
            'daily_occupancy' => $dailyOccupancy,
            'status_breakdown' => $statusBreakdown,
            'occupied_rooms' => $occupiedSnapshot,
            'available_rooms' => $availableSnapshot,
            'occupancy_rate' => $occupancyRateSnapshot,
            'room_types' => $roomTypeBreakdown,
        ];
    }

    /**
     * Get revenue breakdown by source for a date range.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     */
    public function getRevenueReport($startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
        $hotelId = session('active_hotel_id');

        // Room booking revenue (Uses base_price as the real revenue). Grouped by
        // booking_sources.name (falls back to the legacy bookings.source column,
        // then 'Lainnya') instead of the raw source column -- same pattern as
        // ReportController::revenue's sourcePerformance -- so this breaks down
        // by actual source (Reddoorz/Traveloka/Agoda/Tunai/...) instead of the
        // old Walk_in/Online buckets.
        $roomRevenueQuery = Booking::leftJoin('booking_sources', 'bookings.booking_source_id', '=', 'booking_sources.id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->whereNotIn('bookings.status', ['cancelled'])
            ->select(
                DB::raw("COALESCE(booking_sources.name, bookings.source, 'Lainnya') as source"),
                DB::raw('COUNT(*) as booking_count'),
                DB::raw('SUM(bookings.total_price) as total_invoice_value'), // Value for external
                DB::raw('SUM(bookings.base_price) as total_revenue'), // Value for internal/tax
                DB::raw('SUM(bookings.discount_amount) as total_discount'),
                DB::raw('SUM(bookings.tax_amount) as total_tax')
            )
            // Group by the expression itself, not the "source" alias -- it collides
            // with the real bookings.source column and MySQL's ONLY_FULL_GROUP_BY
            // would silently group by that column instead of the COALESCE result.
            ->groupBy(DB::raw("COALESCE(booking_sources.name, bookings.source, 'Lainnya')"));
        if ($hotelId) {
            $roomRevenueQuery->where('bookings.hotel_id', $hotelId);
        }
        $roomRevenue = $roomRevenueQuery->get()->pluck(null, 'source');

        // POS revenue
        $posRevenueQuery = \App\Models\PosOrder::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_revenue')
            )
            ->groupBy('payment_method');
        if ($hotelId) {
            $posRevenueQuery->where('hotel_id', $hotelId);
        }
        $posRevenue = $posRevenueQuery->get()->pluck(null, 'payment_method');

        // Expenses (Purchases)
        $purchaseExpenseQuery = \App\Models\Purchase::whereBetween('purchase_date', [$startDate, $endDate])
            ->where('status', 'received');
        if ($hotelId) {
            $purchaseExpenseQuery->where('hotel_id', $hotelId);
        }
        $purchaseExpenses = $purchaseExpenseQuery->sum('total_amount');

        // Operational expenses (Record Expense / shift report kas keluar): Transaction
        // type=expense. These show in the daily report kas keluar but were missing from
        // the financial report, which only counted Purchases. Purchases do NOT create an
        // expense transaction, so summing both does not double-count.
        $expenseTransactionQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'expense')
            ->where('status', 'success');
        if ($hotelId) {
            $expenseTransactionQuery->where('hotel_id', $hotelId);
        }
        $operationalExpenses = $expenseTransactionQuery->sum('amount');

        $totalExpenses = $purchaseExpenses + $operationalExpenses;

        // Direct transaction payments (Exclude markups)
        $paymentRevenueQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'payment')
            ->where('status', 'success')
            ->where(function($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method');
        if ($hotelId) {
            $paymentRevenueQuery->where('hotel_id', $hotelId);
        }
        $paymentRevenue = $paymentRevenueQuery->get()->pluck(null, 'payment_method');

        // Detailed payments by Bank Account (excluding markups) - This is for "Uang yang Disetor"
        $paymentsByAccountQuery = Transaction::withoutGlobalScopes()
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.type', 'payment')
            ->where('transactions.status', 'success')
            ->where(function($q) {
                $q->where('transactions.is_markup', false)->orWhereNull('transactions.is_markup');
            })
            ->join('bank_accounts', 'transactions.bank_account_id', '=', 'bank_accounts.id')
            ->select(
                'bank_accounts.name as account_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(transactions.amount) as total_amount')
            )
            ->groupBy('bank_accounts.id', 'bank_accounts.name');
        
        if ($hotelId) {
            $paymentsByAccountQuery->where('transactions.hotel_id', $hotelId);
        }
        $paymentsByAccount = $paymentsByAccountQuery->get();

        // Refunds
        $refundsQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'refund')
            ->where('status', 'success');
        if ($hotelId) {
            $refundsQuery->where('hotel_id', $hotelId);
        }
        $refunds = $refundsQuery->sum('amount');

        // Daily revenue trend
        $dailyRevenueQuery = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date');
        if ($hotelId) {
            $dailyRevenueQuery->where('hotel_id', $hotelId);
        }
        $dailyRevenue = $dailyRevenueQuery->get();

        $totalRoomRevenueQuery = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled']);
        if ($hotelId) {
            $totalRoomRevenueQuery->where('hotel_id', $hotelId);
        }
        $totalRoomRevenue = $totalRoomRevenueQuery->sum('total_price');

        $totalPosRevenueQuery = \App\Models\PosOrder::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed');
        if ($hotelId) {
            $totalPosRevenueQuery->where('hotel_id', $hotelId);
        }
        $totalPosRevenue = $totalPosRevenueQuery->sum('total_amount');

        // Markup Revenue
        $markupRevenueQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'charge')
            ->where('is_markup', true)
            ->where('status', 'success');
        if ($hotelId) {
            $markupRevenueQuery->where('hotel_id', $hotelId);
        }
        $totalMarkupRevenue = $markupRevenueQuery->sum('amount');

        // Realized vs Unrealized Revenue calculation
        $realizedRevenueQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'success')
            ->where('is_realized', true)
            ->where('type', 'payment')
            ->where(function($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            });
        if ($hotelId) {
            $realizedRevenueQuery->where('hotel_id', $hotelId);
        }
        $totalRealizedRevenue = $realizedRevenueQuery->sum('amount');
        $realizedByMethod = (clone $realizedRevenueQuery)
            ->select('payment_method', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->get();

        // Actual cash received this period (Transaction payments only -- not booking/POS invoice
        // value like $grossRevenue below, which can include unpaid piutang). Manual capital deposits
        // (reference_id=MANUAL_INCOME) are excluded -- they're owner cash injections, not revenue.
        $cashReceivedQuery = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'payment')
            ->where('status', 'success')
            ->where(function ($q) {
                $q->whereNull('reference_id')->orWhere('reference_id', '!=', 'MANUAL_INCOME');
            });
        if ($hotelId) {
            $cashReceivedQuery->where('hotel_id', $hotelId);
        }
        $totalCashReceived = $cashReceivedQuery->sum('amount');
        $cashReceivedByMethod = (clone $cashReceivedQuery)
            ->select('payment_method', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->get();

        $grossRevenue = $totalRoomRevenue + $totalPosRevenue;
        $netProfit = $totalRealizedRevenue - $refunds - $totalExpenses;

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'room_revenue' => [
                'total' => round($totalRoomRevenue, 2),
                'by_source' => $roomRevenue,
            ],
            'pos_revenue' => [
                'total' => round($totalPosRevenue, 2),
                'by_method' => $posRevenue,
            ],
            'markup_revenue' => round($totalMarkupRevenue, 2),
            'expenses' => round($totalExpenses, 2),
            'payments' => [
                'total' => round($paymentRevenue->sum('total_amount'), 2),
                'by_method' => $paymentRevenue,
                'by_account' => $paymentsByAccount,
            ],
            'refunds' => round($refunds, 2),
            'gross_revenue' => round($grossRevenue, 2),
            'cash_received' => round($totalCashReceived, 2),
            'cash_received_by_method' => $cashReceivedByMethod,
            'realized_revenue' => round($totalRealizedRevenue, 2),
            'realized_by_method' => $realizedByMethod,
            'net_profit' => round($netProfit, 2),
            'daily_trend' => $dailyRevenue,
        ];
    }

    /**
     * Get revenue performance by room type.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     */
    public function getRoomTypePerformance($startDate, $endDate): Collection
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
        $hotelId = session('active_hotel_id');

        $query = Booking::withoutGlobalScopes()
            ->select(
                'room_types.name as room_type_name',
                'room_types.base_price',
                DB::raw('COUNT(bookings.id) as total_bookings'),
                DB::raw('SUM(bookings.base_price) as total_base_revenue'),
                DB::raw('SUM(bookings.discount_amount) as total_discounts'),
                DB::raw('SUM(bookings.tax_amount) as total_tax'),
                DB::raw('SUM(bookings.total_price) as total_revenue'),
                DB::raw('AVG(bookings.total_price) as avg_booking_value'),
                DB::raw('AVG(DATEDIFF(bookings.check_out, bookings.check_in)) as avg_nights')
            )
            ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->whereNotIn('bookings.status', ['cancelled']);

        if ($hotelId) {
            $query->where('bookings.hotel_id', $hotelId);
        }

        return $query->groupBy('room_types.id', 'room_types.name', 'room_types.base_price')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Generate a comprehensive daily report for a specific date.
     *
     * @param  string|Carbon  $date
     */
    public function generateDailyReport($date): array
    {
        $date = Carbon::parse($date);
        $hotelId = session('active_hotel_id');

        // Arrivals and departures
        $arrivalsQuery = Booking::withoutGlobalScopes()->whereDate('check_in', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['guest', 'room.roomType']);
        if ($hotelId) {
            $arrivalsQuery->where('hotel_id', $hotelId);
        }
        $arrivals = $arrivalsQuery->get();

        $departuresQuery = Booking::withoutGlobalScopes()->whereDate('check_out', $date)
            ->where('status', 'checked_in')
            ->with(['guest', 'room.roomType']);
        if ($hotelId) {
            $departuresQuery->where('hotel_id', $hotelId);
        }
        $departures = $departuresQuery->get();

        $occupancyQuery = Booking::withoutGlobalScopes()->where('status', 'checked_in')
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>=', $date);
        if ($hotelId) {
            $occupancyQuery->where('hotel_id', $hotelId);
        }
        $currentOccupancy = $occupancyQuery->count();

        $totalRooms = Room::count();
        $availableRooms = Room::whereIn('status', ['Available', 'available'])->count();

        // Daily revenue
        $dailyRoomRevenue = Booking::whereDate('created_at', $date)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_price');

        $dailyPosRevenue = PosOrder::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->sum('total_amount');

        // Today's POS activity
        $posOrders = PosOrder::whereDate('created_at', $date)
            ->with('items')
            ->get();

        // Cancellations today
        $cancellations = Booking::whereDate('updated_at', $date)
            ->where('status', 'cancelled')
            ->count();

        return [
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'arrivals' => [
                'count' => $arrivals->count(),
                'bookings' => $arrivals,
            ],
            'departures' => [
                'count' => $departures->count(),
                'bookings' => $departures,
            ],
            'occupancy' => [
                'current' => $currentOccupancy,
                'total_rooms' => $totalRooms,
                'available' => $availableRooms,
                'rate' => $totalRooms > 0 ? round(($currentOccupancy / $totalRooms) * 100, 2) : 0,
            ],
            'revenue' => [
                'room' => round($dailyRoomRevenue, 2),
                'pos' => round($dailyPosRevenue, 2),
                'total' => round($dailyRoomRevenue + $dailyPosRevenue, 2),
            ],
            'pos_activity' => [
                'order_count' => $posOrders->count(),
                'revenue' => round($dailyPosRevenue, 2),
            ],
            'cancellations' => $cancellations,
        ];
    }

    /**
     * Export a report to Excel.
     *
     * @param  string  $reportType  occupancy, revenue, room_type, daily
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportToExcel(string $reportType, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $filename = "{$reportType}_report_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.xlsx";

        switch ($reportType) {
            case 'occupancy':
                $data = $this->getOccupancyReport($startDate, $endDate);
                $export = new \App\Exports\OccupancyReportExport($data, $startDate, $endDate);
                break;

            case 'revenue':
                $data = $this->getRevenueReport($startDate, $endDate);
                $export = new \App\Exports\RevenueReportExport($data, $startDate, $endDate);
                break;

            case 'room_type':
                $data = $this->getRoomTypePerformance($startDate, $endDate);
                $export = new \App\Exports\RoomTypePerformanceExport($data, $startDate, $endDate);
                break;

            case 'daily':
                $data = $this->generateDailyReport($startDate);
                $export = new \App\Exports\DailyReportExport($data, $startDate);
                break;

            default:
                throw new \InvalidArgumentException("Unknown report type: {$reportType}");
        }

        return Excel::download($export, $filename);
    }

    /**
     * Export a report to PDF.
     *
     * @param  string  $reportType  occupancy, revenue, room_type, daily
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     * @return \Illuminate\Http\Response
     */
    public function exportToPDF(string $reportType, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        switch ($reportType) {
            case 'occupancy':
                $data = $this->getOccupancyReport($startDate, $endDate);
                $view = 'reports.occupancy_pdf';
                $title = 'Occupancy Report';
                break;

            case 'revenue':
                $data = $this->getRevenueReport($startDate, $endDate);
                $view = 'reports.revenue_pdf';
                $title = 'Revenue Report';
                break;

            case 'room_type':
                $data = $this->getRoomTypePerformance($startDate, $endDate);
                $view = 'reports.room_type_pdf';
                $title = 'Room Type Performance';
                break;

            case 'daily':
                $data = $this->generateDailyReport($startDate);
                $view = 'reports.daily_pdf';
                $title = 'Daily Report';
                break;

            default:
                throw new \InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $pdf = Pdf::loadView($view, [
            'data' => $data,
            'title' => $title,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'generated_at' => now(),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download(strtolower(str_replace(' ', '_', $title))."_{$startDate->format('Y-m-d')}.pdf");
    }

    /**
     * Get booking source performance.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     */
    public function getSourcePerformance($startDate, $endDate): Collection
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        return Booking::select(
            'source',
            DB::raw('COUNT(*) as booking_count'),
            DB::raw('SUM(total_price) as total_revenue'),
            DB::raw('AVG(total_price) as avg_booking_value'),
            DB::raw('SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) as cancellations'),
            DB::raw('SUM(CASE WHEN status = \'no_show\' THEN 1 ELSE 0 END) as no_shows')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('source')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get guest repeat booking statistics.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     */
    public function getGuestAnalytics($startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        $uniqueGuests = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('guest_id')
            ->count('guest_id');

        // Repeat guests (more than 1 booking in period)
        $repeatGuests = Booking::select('guest_id', DB::raw('COUNT(*) as booking_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('guest_id')
            ->having('booking_count', '>', 1)
            ->count();

        $repeatRate = $uniqueGuests > 0 ? round(($repeatGuests / $uniqueGuests) * 100, 2) : 0;

        // Average booking value
        $avgValue = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_price');

        return [
            'total_bookings' => $totalBookings,
            'unique_guests' => $uniqueGuests,
            'repeat_guests' => $repeatGuests,
            'repeat_rate' => $repeatRate,
            'average_booking_value' => round($avgValue ?? 0, 2),
        ];
    }
}
