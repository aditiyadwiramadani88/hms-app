<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;


use Illuminate\Support\Facades\View;
use App\Models\Booking;
use App\Models\EmployeeSchedule;
use App\Models\ShiftHandover;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('FORCE_HTTPS')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        Blade::directive('money', function ($amount) {
            return "<?php echo 'Rp ' . number_format($amount, 0, ',', '.'); ?>";
        });

        View::composer('public.*', function ($view) {
            $branchId = session('public_branch_id', 1);
            $hotel = \App\Models\Hotel::find($branchId) ?? \App\Models\Hotel::where('is_active', true)->first();
            $view->with('hotel', $hotel);
        });

        View::composer(['layouts.sidebar-hms', 'layouts.sidebar-tenant'], function ($view) {
            $pendingHandovers = 0;
            if (auth()->check() && active_hotel_id()) {
                $today = get_hotel_date();
                $userSchedule = EmployeeSchedule::where('employee_id', auth()->id())
                    ->where('schedule_date', $today)
                    ->first();
                if ($userSchedule && $userSchedule->shift_id) {
                    $pendingHandovers = ShiftHandover::where('hotel_id', active_hotel_id())
                        ->where('incoming_shift_id', $userSchedule->shift_id)
                        ->where('handover_date', $today)
                        ->where('status', 'submitted')
                        ->count();
                }
            }
            $view->with('pendingHandovers', $pendingHandovers);
        });

        View::composer('*', function ($view) {
            if (auth()->check()) {
                $today = now()->toDateString();
                $dismissed = session('dismissed_notifications', []);
                $dismissedBookings = $dismissed['booking'] ?? [];
                $dismissedRooms = $dismissed['room'] ?? [];
                
                // 1. Unpaid Checkouts Today
                $unpaidDepartures = Booking::with(['guest', 'room', 'transactions', 'posOrders'])
                    ->whereDate('check_out', $today)
                    ->where('status', 'checked_in')
                    ->whereNotIn('id', $dismissedBookings)
                    ->get()
                    ->filter(function ($booking) {
                        $manualExtraTotal = $booking->transactions->where('type', 'charge')->where('status', 'success')->where('reference_id', null)->where('is_deposit', false)->sum('amount');
                        $posTotal = $booking->posOrders->sum('total_amount');
                        $grandTotal = $booking->total_price + $manualExtraTotal + $posTotal;
                        $totalPaid = $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                        return ($grandTotal - $totalPaid) > 0.1;
                    });

                // 2. Check-ins Today
                $todayArrivals = Booking::with(['guest', 'room'])
                    ->whereDate('check_in', $today)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereNotIn('id', $dismissedBookings)
                    ->get();

                // 3. Dirty Rooms
                $dirtyRooms = \App\Models\Room::whereIn('status', ['Checkout', 'Room Refresh'])
                    ->whereNotIn('id', $dismissedRooms)
                    ->get();

                // 4. Attendance reminder — check if user has schedule today but hasn't checked in
                $needsCheckIn = false;
                $hotelId = session('active_hotel_id');
                if ($hotelId) {
                    $userSchedule = \App\Models\EmployeeSchedule::with('shift')
                        ->where('hotel_id', $hotelId)
                        ->where('employee_id', auth()->id())
                        ->where('schedule_date', $today)
                        ->first();

                    if ($userSchedule && $userSchedule->shift && !$userSchedule->shift->is_off) {
                        $hasCheckedIn = \App\Models\Attendance::where('hotel_id', $hotelId)
                            ->where('employee_id', auth()->id())
                            ->where('attendance_date', $today)
                            ->whereNotNull('check_in_time')
                            ->exists();

                        $needsCheckIn = !$hasCheckedIn;
                    }
                }

                $totalCount = $unpaidDepartures->count() + $todayArrivals->count() + $dirtyRooms->count() + ($needsCheckIn ? 1 : 0);

                $view->with('navbarNotifications', [
                    'unpaidDepartures' => $unpaidDepartures,
                    'todayArrivals' => $todayArrivals,
                    'dirtyRooms' => $dirtyRooms,
                    'needsCheckIn' => $needsCheckIn,
                    'totalCount' => $totalCount,
                ]);
            }
        });
    }
}
