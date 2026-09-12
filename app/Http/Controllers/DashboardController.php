<?php

namespace App\Http\Controllers;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Transaction;
use App\Services\BookingPriceService;
use App\Services\RoomService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected RoomService $roomService;
    protected ReportService $reportService;

    public function __construct(RoomService $roomService, ReportService $reportService)
    {
        $this->roomService = $roomService;
        $this->reportService = $reportService;
    }

    /**
     * Display the dashboard with statistics.
     */
    public function index()
    {
        if (auth()->user()->hasRole('Front Page Only')) {
            return redirect()->route('bookings.calendar');
        }

        if (!auth()->user()->can('view reports')) {
            // Fallback for non-admin users
            if (auth()->user()->can('manage housekeeping')) {
                return redirect()->route('housekeeping.index');
            } elseif (auth()->user()->can('manage pos')) {
                return redirect()->route('inventory.index');
            } elseif (auth()->user()->can('manage reservations')) {
                return redirect()->route('bookings.calendar');
            } elseif (auth()->user()->can('security.vehicle-gate')) {
                return redirect()->route('security-gate.dashboard');
            } elseif (auth()->user()->hasRole('OB')) {
                return redirect()->route('ob.dashboard');
            } elseif (auth()->user()->hasRole('Tenant')) {
                return redirect()->route('tenant.dashboard');
            }
            abort(403, 'Unauthorized access to dashboard.');
        }

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Room statistics
        $totalRooms = Room::count();
        $roomStatuses = $this->roomService->getRoomStatuses();

        // Occupancy rate
        $occupancyRate = $roomStatuses['occupancy_rate'];

        // Today's check-ins (arrivals: check_in = today, not yet checked_in/out)
        $todayArrivals = Booking::where('hotel_id', active_hotel_id())
            ->whereDate('check_in', $today)
            ->whereNotIn('status', ['checked_in', 'checked_out', 'cancelled', 'no_show'])
            ->count();

        // Today's in-house (checked_in, overlapping today)
        $todayCheckIns = Booking::where('hotel_id', active_hotel_id())
            ->where('status', 'checked_in')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->count();

        // Today's check-outs (yang AKAN checkout: masih checked_in, belum benar-benar checkout)
        $todayCheckOuts = Booking::where('hotel_id', active_hotel_id())
            ->where('status', 'checked_in')
            ->whereDate('check_out', $today)
            ->count();

        // Unpaid departures today
        $unpaidDepartures = Booking::with(['guest', 'room', 'transactions', 'posOrders'])
            ->where('hotel_id', active_hotel_id())
            ->whereDate('check_out', $today)
            ->where('status', 'checked_in')
            ->get()
            ->filter(function ($booking) {
                $grandTotal = BookingPriceService::grandTotal($booking);
                $totalPaid = $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                return ($grandTotal - $totalPaid) > 0.1;
            });

        // Today's revenue
        $todayRevenue = Transaction::whereDate('created_at', $today)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        // Monthly revenue report using service for consistency
        $monthlyReport = $this->reportService->getRevenueReport($startOfMonth, $endOfMonth);
        // "Total Gross (Kas)" card: actual cash received, not booking/POS invoice value
        // (gross_revenue is invoice-based and stays as-is for the Revenue Report page).
        $monthlyRevenue = $monthlyReport['cash_received'] ?? 0;
        $realizedRevenue = $monthlyReport['realized_revenue'] ?? 0;

        // Wallet Balance for Dashboard
        $availableBalance = \App\Models\BankAccount::where('hotel_id', active_hotel_id())->sum('available_balance');
        $totalBalance = \App\Models\BankAccount::where('hotel_id', active_hotel_id())->sum('balance');

        // Room stats — using Room.status consistently (matching RoomService::getRoomStatuses)
        $dirtyRoomsCount = Room::whereIn('status', ['Checkout', 'dirty'])->count();
        $maintenanceCount = Room::whereIn('status', ['Out of Order', 'maintenance'])->count();
        $availableRoomsCount = Room::where('status', 'Available')->count();

        // In-House Rooms — aligned with BookingController check-in count
        $inHouseBookingsQuery = Booking::where('hotel_id', active_hotel_id())
            ->where('status', 'checked_in')
            ->where(function ($q) use ($today) {
                $q->whereNull('actual_check_out')
                    ->orWhere(function ($rq) use ($today) {
                        $rq->whereDate('check_in', '<=', $today)
                            ->whereDate('check_out', '>=', $today);
                    });
            });

        $inHouseRooms = $inHouseBookingsQuery->count();

        // In-House Rooms breakdown by booking source — normalized, walk-in mapped to UMUM
        $inHouseBySource = (clone $inHouseBookingsQuery)
            ->with('bookingSource')
            ->get()
            ->groupBy(function ($booking) {
                $source = $booking->bookingSource?->name ?? $booking->source;
                if (!$source || in_array(strtolower(trim($source)), ['walk_in', 'walk in', 'langsung / walk-in', ''])) {
                    return 'UMUM';
                }
                return strtoupper(trim($source));
            })
            ->map->count()
            ->sortDesc();
        $totalActiveGuests = Booking::where('hotel_id', active_hotel_id())->where('status', 'checked_in')->sum(DB::raw('adults + children'));

        // Recent bookings
        $recentBookings = Booking::with(['guest', 'room.roomType'])
            ->latest()
            ->take(8)
            ->get();

        $inHouseGuests = Booking::where('hotel_id', active_hotel_id())
            ->where('status', 'checked_in')
            ->sum(DB::raw('COALESCE(adults, 1) + COALESCE(children, 0)'));

        $recentActivities = \App\Models\AuditLog::where('hotel_id', active_hotel_id())
            ->latest()
            ->take(6)
            ->get();

        // Occupancy Chart Data (Last 7 Days) — actual check-in/out, not booking status
        $occupancyChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->toDateString();

            $occupiedCount = Booking::where('hotel_id', active_hotel_id())
                ->occupiedOn($dateStr)
                ->whereNotNull('room_id')
                ->distinct('room_id')
                ->count('room_id');

            $occupancyChartData[] = [
                'date' => $date->format('d M'),
                'count' => $occupiedCount
            ];
        }

        return view('dashboard.index', compact(
            'totalRooms',
            'roomStatuses',
            'occupancyRate',
            'todayArrivals',
            'todayCheckIns',
            'todayCheckOuts',
            'todayRevenue',
            'monthlyRevenue',
            'realizedRevenue',
            'availableBalance',
            'totalBalance',
            'recentBookings',
            'occupancyChartData',
            'recentActivities',
            'availableRoomsCount',
            'dirtyRoomsCount',
            'maintenanceCount',
            'totalActiveGuests',
            'unpaidDepartures',
            'inHouseRooms',
            'inHouseBySource',
            'inHouseGuests'
        ));
    }
}
