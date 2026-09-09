<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BonusReportController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VehicleRentalController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomStatusController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\OBDashboardController;
use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GuestCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\RoomRateController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\BookingSourceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ScheduleLocationController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\MyScheduleController;
use App\Http\Controllers\ShiftSwapController;
use App\Http\Controllers\BookingEditRequestController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceReportController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceLocationController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MyLeaveController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC WEBSITE ROUTES
// ============================================
Route::get('/', [\App\Http\Controllers\PublicController::class, 'index'])->name('public.index');
Route::get('/set-branch/{hotel}', [\App\Http\Controllers\PublicController::class, 'setBranch'])->name('public.set-branch');
Route::get('/api/available-rooms', [\App\Http\Controllers\PublicBookingController::class, 'availableRooms']);
Route::get('/rooms', [\App\Http\Controllers\PublicRoomController::class, 'index'])->name('public.rooms.index');
Route::get('/rooms/{id}', [\App\Http\Controllers\PublicRoomController::class, 'show'])->name('public.rooms.show');
Route::get('/vouchers/check', [\App\Http\Controllers\VoucherController::class, 'check'])->name('public.vouchers.check');
Route::get('/about', [\App\Http\Controllers\PublicController::class, 'about'])->name('public.about');
Route::get('/contact', [\App\Http\Controllers\PublicController::class, 'contact'])->name('public.contact');
Route::get('/gallery', [\App\Http\Controllers\PublicController::class, 'gallery'])->name('public.gallery');
Route::get('/services', [\App\Http\Controllers\PublicController::class, 'services'])->name('public.services');

// Guest Authentication
Route::prefix('guest')->name('guest.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\GuestAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\GuestAuthController::class, 'login']);
    Route::get('/register', [\App\Http\Controllers\GuestAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\GuestAuthController::class, 'register']);
    Route::post('/logout', [\App\Http\Controllers\GuestAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth:guest'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GuestDashboardController::class, 'index'])->name('dashboard');
        Route::get('/bookings/{id}', [\App\Http\Controllers\GuestDashboardController::class, 'showBooking'])->name('bookings.show');
    });
});

// Online Booking
Route::get('/booking', [\App\Http\Controllers\PublicBookingController::class, 'showForm'])->name('public.booking.form');
Route::post('/booking', [\App\Http\Controllers\PublicBookingController::class, 'store'])->name('public.booking.store');
Route::get('/payment/{booking}/pay', [\App\Http\Controllers\PublicBookingController::class, 'showPayment'])->name('public.payment.pay');
Route::get('/payment/{booking}/success', [\App\Http\Controllers\PublicBookingController::class, 'paymentSuccess'])->name('public.payment.success');
Route::post('/payment/callback', [\App\Http\Controllers\PublicBookingController::class, 'paymentCallback'])->name('public.payment.callback');

// Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

// ============================================
// ADMIN BACKEND ROUTES (/admin prefix)
// ============================================
Route::post('/deploy/git-pull', [\App\Http\Controllers\DeployController::class, 'gitPull']);

Route::prefix('admin')->group(function () {
    // Auth routes for admin/staff
    Route::middleware(['guest'])->group(function () {
        Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
        Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
    });
    Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Redirect /admin to /admin/dashboard
    Route::redirect('/', '/admin/dashboard');

    // Shift blocked page (must be outside shift.schedule middleware)
    Route::get('/shift-blocked', function () {
        return view('auth.shift-blocked');
    })->name('shift.blocked')->middleware('auth');

    // Force logout reset endpoint
    Route::post('/force-logout/reset', function () {
        $user = auth()->user();
        \DB::table('users')->where('id', $user->id)->update(['is_force_logout' => false]);
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return response()->json(['status' => true, 'message' => 'OK']);
    })->name('force-logout.reset')->middleware('auth');

    // Impersonate (Force Login As)
    // NOTE: impersonate.stop MUST be defined BEFORE impersonate.start ({user} param)
    // otherwise POST /impersonate/stop gets caught by the wildcard {user} route → 404.
    Route::post('/impersonate/stop', function () {
        $originalId = session('impersonating_from');
        if (!$originalId) {
            return redirect()->route('dashboard');
        }
        session()->forget('impersonating_from');
        $admin = \App\Models\User::find($originalId);
        if ($admin) {
            Auth::login($admin);
            if ($admin->hotels()->count() === 1) {
                session(['active_hotel_id' => $admin->hotels()->first()->id]);
            }
        }
        return redirect()->route('dashboard')->with('success', 'Kembali ke akun admin.');
    })->name('impersonate.stop')->middleware('auth');

    Route::post('/impersonate/{user}', function (\App\Models\User $user) {
        // Check role via DB directly (bypass Spatie teams issue)
        $userRoles = \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', auth()->id())
            ->where('model_type', 'App\\Models\\User')
            ->pluck('roles.name')
            ->toArray();

        if (!in_array('Admin', $userRoles) && !in_array('Super Admin', $userRoles)) {
            abort(403, 'Hanya Admin yang dapat melakukan impersonate.');
        }

        // Store original admin ID in session
        session(['impersonating_from' => auth()->id()]);
        Auth::login($user);
        // Set hotel if user has one
        if ($user->hotels()->count() === 1) {
            session(['active_hotel_id' => $user->hotels()->first()->id]);
        }
        return redirect()->route('dashboard')->with('success', 'Login sebagai ' . $user->name);
    })->name('impersonate.start')->middleware('auth');

    // Update User Details
    Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
    Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

    // Employee Attendance — outside shift.schedule so employees can check-in at shift start
    Route::middleware(['auth', \App\Http\Middleware\EnsureActiveHotel::class])->group(function () {
        Route::prefix('attendance')->name('attendance.')->middleware('permission:attendance.check-in|attendance.view-own')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
            Route::post('/break-start', [AttendanceController::class, 'startBreak'])->name('break-start');
            Route::post('/break-end', [AttendanceController::class, 'endBreak'])->name('break-end');
            Route::post('/reset-checkout', [AttendanceController::class, 'resetCheckout'])->name('reset-checkout');
            Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        });

        // Admin Attendance Report
        Route::prefix('attendance/report')->name('attendance.report.')->middleware('permission:attendance.view-all')->group(function () {
            Route::get('/', [AttendanceReportController::class, 'index'])->name('index');
            Route::get('/export/excel', [AttendanceReportController::class, 'export'])->name('export');
            Route::get('/export/pdf', [AttendanceReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/summary', [AttendanceReportController::class, 'summary'])->name('summary');
            Route::get('/{attendance}', [AttendanceReportController::class, 'detail'])->name('detail');
            Route::post('/{attendance}/fix-status', [AttendanceReportController::class, 'fixStatus'])->name('fix-status');
            Route::put('/{attendance}/edit', [AttendanceReportController::class, 'edit'])->name('edit');
            Route::post('/{attendance}/reset-checkout', [AttendanceReportController::class, 'resetCheckout'])->name('reset-checkout');
            Route::delete('/{attendance}', [AttendanceReportController::class, 'destroy'])->name('destroy');
        });

        // Attendance Locations (Admin)
        Route::resource('attendance-locations', AttendanceLocationController::class)
            ->except(['create', 'edit', 'show'])
            ->middleware('permission:attendance.manage-locations');

        // My Leaves (Employee)
        Route::prefix('my-leaves')->name('my-leaves.')->middleware('permission:leaves.request')->group(function () {
            Route::get('/', [MyLeaveController::class, 'index'])->name('index');
            Route::post('/', [MyLeaveController::class, 'store'])->name('store');
        });
    });

    // Security Gate — outside shift.schedule so Security can access during shift
    Route::middleware(['auth', \App\Http\Middleware\EnsureActiveHotel::class])->group(function () {
        Route::prefix('security-gate')->name('security-gate.')->group(function () {
            Route::middleware('permission:security.vehicle-gate|security.vehicle-log')->group(function () {
                Route::get('/', [\App\Http\Controllers\SecurityGateController::class, 'dashboard'])->name('dashboard');
                Route::get('/entry', [\App\Http\Controllers\SecurityGateController::class, 'createEntry'])->name('entry');
                Route::post('/entry', [\App\Http\Controllers\SecurityGateController::class, 'storeEntry'])->name('entry.store');
                Route::get('/exit', [\App\Http\Controllers\SecurityGateController::class, 'exitList'])->name('exit');
                Route::post('/exit/{vehicleLog}', [\App\Http\Controllers\SecurityGateController::class, 'processExit'])->name('exit.process');
                Route::put('/vehicle-logs/{vehicleLog}', [\App\Http\Controllers\SecurityGateController::class, 'update'])->name('vehicle-logs.update');
                Route::delete('/vehicle-logs/{vehicleLog}', [\App\Http\Controllers\SecurityGateController::class, 'destroy'])->name('vehicle-logs.destroy');
                Route::post('/exit-photo/{vehicleLog}', [\App\Http\Controllers\SecurityGateController::class, 'attachExitPhoto'])->name('exit-photo');
                Route::get('/search-plate', [\App\Http\Controllers\SecurityGateController::class, 'searchPlate'])->name('search-plate');
            });

            Route::middleware('permission:security.vehicle-report')->group(function () {
                Route::get('/report', [\App\Http\Controllers\VehicleReportController::class, 'index'])->name('report');
                Route::get('/report/parking', [\App\Http\Controllers\VehicleReportController::class, 'parkingReport'])->name('report.parking');
                Route::get('/report/parking/pdf', [\App\Http\Controllers\VehicleReportController::class, 'exportPdf'])->name('report.parking.pdf');
                Route::get('/report/parking/excel', [\App\Http\Controllers\VehicleReportController::class, 'exportExcel'])->name('report.parking.excel');
                Route::get('/report/{vehicleLog}', [\App\Http\Controllers\VehicleReportController::class, 'detail'])->name('report.detail');
    });
});

        // Guest Vehicles (Admin)
        Route::middleware('permission:security.manage-vehicles')->group(function () {
            Route::get('/guest-vehicles', [\App\Http\Controllers\GuestVehicleController::class, 'index'])->name('guest-vehicles.index');
            Route::post('/guest-vehicles', [\App\Http\Controllers\GuestVehicleController::class, 'store'])->name('guest-vehicles.store');
            Route::get('/guest-vehicles/guest/{guest}', [\App\Http\Controllers\GuestVehicleController::class, 'byGuest'])->name('guest-vehicles.by-guest');
            Route::get('/guest-vehicles/{guestVehicle}', [\App\Http\Controllers\GuestVehicleController::class, 'show'])->name('guest-vehicles.show');
            Route::put('/guest-vehicles/{guestVehicle}', [\App\Http\Controllers\GuestVehicleController::class, 'update'])->name('guest-vehicles.update');
            Route::delete('/guest-vehicles/{guestVehicle}', [\App\Http\Controllers\GuestVehicleController::class, 'destroy'])->name('guest-vehicles.destroy');
        });
    });

    // Protected routes - require authentication + shift schedule
    Route::middleware(['auth', \App\Http\Middleware\EnsureActiveHotel::class, 'shift.schedule'])->group(function () {

    // Leave Management (Admin)
    Route::prefix('leave-types')->name('admin.leave-types.')->middleware('permission:manage leaves')->group(function () {
        Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
        Route::post('/', [LeaveTypeController::class, 'store'])->name('store');
        Route::put('/{leaveType}', [LeaveTypeController::class, 'update'])->name('update');
        Route::delete('/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('leave-requests')->name('admin.leave-requests.')->middleware('permission:manage leaves')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::patch('/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
        Route::patch('/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('reject');
    });

    // Branch Selection
    Route::get('/select-branch', [BranchController::class, 'index'])->name('branch.select');
    Route::post('/select-branch', [BranchController::class, 'switch'])->name('branch.switch');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Hotel Wallets / Bank Accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index')->middleware('permission:view reports');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store')->middleware('permission:manage system');
    Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('bank-accounts.show')->middleware('permission:view reports');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update')->middleware('permission:manage system');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy')->middleware('permission:manage system');
    Route::post('/bank-accounts/{bankAccount}/withdraw', [BankAccountController::class, 'withdraw'])->name('bank-accounts.withdraw')->middleware('permission:manage system|finance.bank-accounts|finance.expense');
    Route::post('/bank-accounts/{bankAccount}/deposit', [BankAccountController::class, 'deposit'])->name('bank-accounts.deposit')->middleware('permission:manage system|finance.bank-accounts|finance.income');
    Route::post('/bank-accounts/{bankAccount}/adjust-final-cash', [BankAccountController::class, 'adjustFinalCash'])->name('bank-accounts.adjust-final-cash')->middleware('permission:manage system|finance.bank-accounts');
    Route::get('/bank-accounts/{bankAccount}/export/pdf', [BankAccountController::class, 'exportPdf'])->name('bank-accounts.export.pdf')->middleware('permission:view reports');
    Route::get('/bank-accounts/{bankAccount}/export/excel', [BankAccountController::class, 'exportExcel'])->name('bank-accounts.export.excel')->middleware('permission:view reports');
    Route::get('/bank-accounts/{bankAccount}/export/final-cash/pdf', [BankAccountController::class, 'exportFinalCashPdf'])->name('bank-accounts.export.final-cash.pdf')->middleware('permission:view reports');
    Route::get('/bank-accounts/{bankAccount}/export/final-cash/excel', [BankAccountController::class, 'exportFinalCashExcel'])->name('bank-accounts.export.final-cash.excel')->middleware('permission:view reports');

    // Finance Categories
    Route::get('/finance/categories', [TransactionCategoryController::class, 'index'])->name('finance.categories.index')->middleware('permission:manage system');
    Route::get('/finance/categories/{transactionCategory}', [TransactionCategoryController::class, 'show'])->name('finance.categories.show')->middleware('permission:manage system');
    Route::post('/finance/categories', [TransactionCategoryController::class, 'store'])->name('finance.categories.store')->middleware('permission:manage system');
    Route::put('/finance/categories/{transactionCategory}', [TransactionCategoryController::class, 'update'])->name('finance.categories.update')->middleware('permission:manage system');
    Route::delete('/finance/categories/{transactionCategory}', [TransactionCategoryController::class, 'destroy'])->name('finance.categories.destroy')->middleware('permission:manage system');

    // Admin / Hotels
    Route::resource('hotels', HotelController::class)->middleware('permission:manage system');

    // Rooms Management
    Route::resource('rooms', RoomController::class)->middleware('permission:manage system');
    Route::post('/rooms/{room}/update-status', [RoomController::class, 'updateStatus'])->name('rooms.update-status')->middleware('permission:manage system');

    // Bookings Management — Granular Permissions
    Route::get('/bookings/search', [BookingController::class, 'globalSearch'])->name('bookings.global-search')->middleware('permission:bookings.search|manage reservations');
    Route::get('/bookings/create-custom', [BookingController::class, 'createCustom'])->name('bookings.create-custom')->middleware('permission:bookings.create|manage reservations');
    Route::post('/bookings/store-custom', [BookingController::class, 'storeCustom'])->name('bookings.store-custom')->middleware('permission:bookings.create|manage reservations');
    Route::get('/bookings/available-rooms', [BookingController::class, 'getAvailableRooms'])->name('bookings.available-rooms')->middleware('permission:bookings.create|manage reservations');
    Route::get('/bookings/kost-price', [BookingController::class, 'getKostPrice'])->name('bookings.kost-price')->middleware('permission:bookings.create|manage reservations');
    Route::get('/bookings-calendar', [BookingController::class, 'calendar'])->name('bookings.calendar')->middleware('permission:bookings.list|manage reservations');
    Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in')->middleware('permission:bookings.checkin|manage reservations');
    Route::post('/bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out')->middleware('permission:bookings.checkout|manage reservations');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel')->middleware('permission:bookings.cancel|manage reservations');
    Route::post('/bookings/{booking}/send-wa', [BookingController::class, 'sendInvoiceToWA'])->name('bookings.send-wa')->middleware('permission:bookings.send-wa|manage reservations');

    // Room Transfer
    Route::get('/bookings/{booking}/transfer/preview', [\App\Http\Controllers\RoomTransferController::class, 'preview'])->name('bookings.transfer.preview')->middleware('permission:bookings.transfer|manage reservations');
    Route::post('/bookings/{booking}/transfer', [\App\Http\Controllers\RoomTransferController::class, 'store'])->name('bookings.transfer.store')->middleware('permission:bookings.transfer|manage reservations');

    // Notifications
    Route::post('/bookings/{booking}/notify/booking', [NotificationController::class, 'sendBookingNotification'])->name('notifications.booking');
    Route::post('/bookings/{booking}/notify/payment', [NotificationController::class, 'sendPaymentNotification'])->name('notifications.payment');
    Route::post('/bookings/{booking}/notify/cancel', [NotificationController::class, 'sendCancelNotification'])->name('notifications.cancel');
    Route::get('/bookings/{booking}/print/booking', [NotificationController::class, 'printBooking'])->name('notifications.print.booking');
    Route::get('/bookings/{booking}/print/payment/{transaction}', [NotificationController::class, 'printPayment'])->name('notifications.print.payment');
    Route::get('/bookings/{booking}/print/cancel', [NotificationController::class, 'printCancel'])->name('notifications.print.cancel');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');

    Route::post('/bookings/{booking}/apply-discount', [BookingController::class, 'applyDiscount'])->name('bookings.apply-discount')->middleware('permission:bookings.discount|manage reservations');
    Route::post('/bookings/{booking}/add-payment', [BookingController::class, 'addPayment'])->name('bookings.add-payment')->middleware('permission:bookings.payment|manage reservations');
    Route::put('/bookings/{booking}/payments/{transaction}', [BookingController::class, 'editPayment'])->name('bookings.edit-payment')->middleware('permission:bookings.payment.edit|bookings.payment.edit.unlimited|edit payments');
    Route::delete('/bookings/{booking}/payments/{transaction}', [BookingController::class, 'deletePayment'])->name('bookings.delete-payment')->middleware('permission:bookings.payment.delete|bookings.payment.delete.unlimited|delete transactions');
    Route::post('/bookings/{booking}/add-item', [BookingController::class, 'addItemCharge'])->name('bookings.add-item')->middleware('permission:bookings.charge|manage reservations');
    
    // Vehicle Rentals
    Route::post('/bookings/{booking}/vehicle-rentals', [VehicleRentalController::class, 'store'])->name('bookings.vehicle-rentals.store')->middleware('permission:bookings.charge|manage reservations');
    Route::get('/vehicle-rentals/{vehicleRental}/print', [VehicleRentalController::class, 'print'])->name('vehicle-rentals.print')->middleware('permission:view bookings|manage reservations');
    Route::post('/bookings/{booking}/add-custom-charge', [BookingController::class, 'addCustomCharge'])->name('bookings.add-custom-charge')->middleware('permission:bookings.charge|manage reservations');
    Route::post('/bookings/{booking}/refund-deposit/{transaction}', [BookingController::class, 'refundDeposit'])->name('bookings.refund-deposit')->middleware('permission:bookings.charge|manage reservations');
    Route::post('/bookings/{booking}/refund-all-deposits', [BookingController::class, 'refundAllDeposits'])->name('bookings.refund-all-deposits')->middleware('permission:bookings.charge|manage reservations');
    Route::delete('/bookings/{booking}/charges/{transaction}', [BookingController::class, 'deleteCharge'])->name('bookings.delete-charge')->middleware('permission:bookings.delete.unlimited|delete transactions');
    Route::get('/bookings/{booking}/invoice', [BookingController::class, 'invoice'])->name('bookings.invoice')->middleware('permission:bookings.invoice|manage reservations');
    Route::get('/bookings/{booking}/extend', [BookingController::class, 'extend'])->name('bookings.extend')->middleware('permission:bookings.extend|edit bookings');
    Route::post('/bookings/{booking}/extend', [BookingController::class, 'processExtend'])->name('bookings.extend.process')->middleware('permission:bookings.extend|edit bookings');

    Route::put('/bookings/{booking}/edit-deposit', [BookingController::class, 'editDeposit'])->name('bookings.edit-deposit')->middleware('permission:bookings.payment.edit|bookings.payment.edit.unlimited|edit payments');
    Route::delete('/bookings/{booking}/delete-deposit', [BookingController::class, 'deleteDeposit'])->name('bookings.delete-deposit')->middleware('permission:bookings.payment.delete|bookings.payment.delete.unlimited|delete transactions');

    // Pricing Audit
    Route::get('/bookings/{booking}/audit-pricing', [BookingController::class, 'auditPricing'])->name('bookings.audit-pricing')->middleware('permission:bookings.edit|manage reservations');
    Route::post('/bookings/{booking}/apply-audit-fix', [BookingController::class, 'applyAuditFix'])->name('bookings.apply-audit-fix')->middleware('permission:bookings.edit|manage reservations');
    Route::get('/bookings-audit-all', [BookingController::class, 'auditAllBookings'])->name('bookings.audit-all')->middleware('permission:bookings.list|manage reservations');
    Route::get('/bookings-anomaly-catalog', [BookingController::class, 'anomalyCatalog'])->name('bookings.anomaly-catalog')->middleware('permission:bookings.list|manage reservations');

    // Booking Resource — granular permissions with time-limit for edit/delete
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index')->middleware('permission:bookings.list|manage reservations');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create')->middleware('permission:bookings.create|manage reservations');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store')->middleware('permission:bookings.create|manage reservations');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show')->middleware('permission:bookings.detail|manage reservations');
    Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit')->middleware('booking.time_limit:edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update')->middleware('booking.time_limit:edit');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy')->middleware('booking.time_limit:delete');

    // Booking Edit Requests
    Route::post('/bookings/{booking}/edit-request', [BookingEditRequestController::class, 'store'])->name('bookings.edit-request.store')->middleware('permission:bookings.edit.request');
    Route::post('/bookings/edit-requests/{editRequest}/approve', [BookingEditRequestController::class, 'approve'])->name('bookings.edit-request.approve')->middleware('permission:bookings.edit.approve');
    Route::post('/bookings/edit-requests/{editRequest}/reject', [BookingEditRequestController::class, 'reject'])->name('bookings.edit-request.reject')->middleware('permission:bookings.edit.approve');

    // Custom Invoices
    Route::get('/custom-invoices/{customInvoice}/print', [\App\Http\Controllers\CustomInvoiceController::class, 'print'])->name('custom-invoices.print')->middleware('permission:custom-invoices.print|manage reservations');
    Route::post('/custom-invoices/{customInvoice}/mark-paid', [\App\Http\Controllers\CustomInvoiceController::class, 'markPaid'])->name('custom-invoices.mark-paid')->middleware('permission:custom-invoices.mark-paid|manage reservations');
    Route::post('/custom-invoices/{customInvoice}/mark-sent', [\App\Http\Controllers\CustomInvoiceController::class, 'markSent'])->name('custom-invoices.mark-sent')->middleware('permission:custom-invoices.edit|manage reservations');
    Route::post('/custom-invoices/{customInvoice}/mark-cancelled', [\App\Http\Controllers\CustomInvoiceController::class, 'markCancelled'])->name('custom-invoices.mark-cancelled')->middleware('permission:custom-invoices.edit|manage reservations');
    Route::resource('custom-invoices', \App\Http\Controllers\CustomInvoiceController::class)->except(['print', 'markPaid', 'markSent', 'markCancelled'])->middleware('permission:custom-invoices.list|custom-invoices.create|custom-invoices.edit|custom-invoices.delete|manage reservations');

    // Guests Management
    Route::get('guests/search', [GuestController::class, 'search'])->name('guests.search');
    Route::resource('guests', GuestController::class)->middleware('permission:manage reservations');

    // POS (Point of Sale)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index')->middleware('permission:manage pos');
    Route::get('/pos/export/pdf', [PosController::class, 'exportPdf'])->name('pos.export.pdf')->middleware('permission:manage pos');
    Route::get('/pos/create', [PosController::class, 'create'])->name('pos.create')->middleware('permission:manage pos');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store')->middleware('permission:manage pos');
    Route::get('/pos/{order}', [PosController::class, 'show'])->name('pos.show')->middleware('permission:manage pos');
    Route::post('/pos/{order}/add-item', [PosController::class, 'addItem'])->name('pos.add-item')->middleware('permission:manage pos');
    Route::delete('/pos/{order}', [PosController::class, 'destroy'])->name('pos.destroy')->middleware('permission:delete transactions');
    Route::delete('/pos/{order}/items/{item}', [PosController::class, 'deleteItem'])->name('pos.delete-item')->middleware('permission:delete transactions');
    Route::get('/pos/{order}/print', [PosController::class, 'printOrder'])->name('pos.print')->middleware('permission:manage pos');
    Route::post('/pos/{order}/payment', [PosController::class, 'processPayment'])->name('pos.payment')->middleware('permission:manage pos');
    Route::post('/pos/{order}/charge-to-room', [PosController::class, 'chargeToRoom'])->name('pos.charge-to-room')->middleware('permission:manage pos');

    // Room Types
    Route::get('/room-types', [RoomTypeController::class, 'index'])->name('room-types.index')->middleware('permission:manage system');
    Route::get('/room-types/create', [RoomTypeController::class, 'create'])->name('room-types.create')->middleware('permission:manage system');
    Route::post('/room-types', [RoomTypeController::class, 'store'])->name('room-types.store')->middleware('permission:manage system');
    Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show'])->name('room-types.show')->middleware('permission:manage system');
    Route::get('/room-types/{roomType}/edit', [RoomTypeController::class, 'edit'])->name('room-types.edit')->middleware('permission:manage system');
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('room-types.update')->middleware('permission:manage system');
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy')->middleware('permission:manage system');

    // Room Statuses
    Route::get('/room-statuses', [RoomStatusController::class, 'index'])->name('room-statuses.index')->middleware('permission:manage system');
    Route::post('/room-statuses', [RoomStatusController::class, 'store'])->name('room-statuses.store')->middleware('permission:manage system');
    Route::put('/room-statuses/{roomStatus}', [RoomStatusController::class, 'update'])->name('room-statuses.update')->middleware('permission:manage system');
    Route::delete('/room-statuses/{roomStatus}', [RoomStatusController::class, 'destroy'])->name('room-statuses.destroy')->middleware('permission:manage system');

    // Inventory Categories
    Route::get('/inventory/categories', [InventoryController::class, 'categoriesIndex'])->name('inventory.categories.index')->middleware('permission:manage pos');
    Route::post('/inventory/categories', [InventoryController::class, 'categoriesStore'])->name('inventory.categories.store')->middleware('permission:manage pos');
    Route::put('/inventory/categories/{category}', [InventoryController::class, 'categoriesUpdate'])->name('inventory.categories.update')->middleware('permission:manage pos');
    Route::delete('/inventory/categories/{category}', [InventoryController::class, 'categoriesDestroy'])->name('inventory.categories.destroy')->middleware('permission:manage pos');

    // Inventory / POS Stock
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:manage pos');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create')->middleware('permission:manage pos');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store')->middleware('permission:manage pos');
    Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show')->middleware('permission:manage pos');
    Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventory.edit')->middleware('permission:manage pos');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update')->middleware('permission:manage pos');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy')->middleware('permission:manage pos');
    Route::post('/inventory/{inventory}/mutation', [InventoryController::class, 'mutation'])->name('inventory.mutation')->middleware('permission:manage pos');
    
    // Multi-Warehouse & Transfer
    Route::get('/warehouses', [\App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouse.index')->middleware('permission:manage warehouse');
    Route::post('/warehouses', [\App\Http\Controllers\WarehouseController::class, 'store'])->name('warehouse.store')->middleware('permission:manage warehouse');
    Route::put('/warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouse.update')->middleware('permission:manage warehouse');
    Route::delete('/warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'destroy'])->name('warehouse.destroy')->middleware('permission:manage warehouse');
    
    Route::get('/warehouse-transfers', [\App\Http\Controllers\WarehouseTransferController::class, 'index'])->name('warehouse.transfer.index')->middleware('permission:manage warehouse');
    Route::post('/warehouse-transfers', [\App\Http\Controllers\WarehouseTransferController::class, 'store'])->name('warehouse.transfer.store')->middleware('permission:manage warehouse');

    // Housekeeping
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index')->middleware('permission:manage housekeeping');
    Route::post('/housekeeping/{room}/mark-clean', [HousekeepingController::class, 'markClean'])->name('housekeeping.mark-clean')->middleware('permission:manage housekeeping');
    Route::post('/housekeeping/{room}/mark-dirty', [HousekeepingController::class, 'markDirty'])->name('housekeeping.mark-dirty')->middleware('permission:manage housekeeping');
    Route::post('/housekeeping/{room}/update-status', [HousekeepingController::class, 'updateStatus'])->name('housekeeping.update-status')->middleware('permission:manage system|rooms.status.change');
    Route::get('/housekeeping/search-staff', [HousekeepingController::class, 'searchStaff'])->name('housekeeping.search-staff')->middleware('permission:manage housekeeping');
    Route::get('/housekeeping/rooms/{room}/task-details', [HousekeepingController::class, 'getTaskDetails'])->name('housekeeping.task-details')->middleware('permission:manage housekeeping');
    Route::post('/housekeeping/quick-assign', [HousekeepingController::class, 'quickAssign'])->name('housekeeping.quick-assign')->middleware('permission:manage housekeeping');
    Route::post('/housekeeping/auto-assign', [HousekeepingController::class, 'autoAssign'])->name('housekeeping.auto-assign')->middleware('permission:manage housekeeping');

    // Work Orders
    Route::resource('work-orders', WorkOrderController::class)->middleware('permission:manage housekeeping');
    Route::post('/work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start')->middleware('permission:manage housekeeping');
    Route::post('/work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete')->middleware('permission:manage housekeeping');

    // Maintenance
    Route::prefix('maintenance')->name('maintenance.')->middleware('permission:manage system|manage maintenance')->group(function () {
        Route::get('/records', [MaintenanceController::class, 'index'])->name('records.index');
        Route::get('/records/create', [MaintenanceController::class, 'create'])->name('records.create');
        Route::post('/records', [MaintenanceController::class, 'store'])->name('records.store');
        Route::get('/records/{record}/edit', [MaintenanceController::class, 'edit'])->name('records.edit');
        Route::put('/records/{record}', [MaintenanceController::class, 'update'])->name('records.update');
        Route::delete('/records/{record}', [MaintenanceController::class, 'destroy'])->name('records.destroy');
        Route::get('/categories', [MaintenanceCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [MaintenanceCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{maintenanceCategory}', [MaintenanceCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{maintenanceCategory}', [MaintenanceCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/report', [MaintenanceReportController::class, 'index'])->name('report');
        Route::get('/report/pdf', [MaintenanceReportController::class, 'exportPdf'])->name('report.pdf');
    });

    // Housekeeping Personal Dashboard
    Route::middleware(['auth', 'housekeeping.access'])->prefix('housekeeping')->name('housekeeping.')->group(function () {
        Route::get('/my-tasks', [\App\Http\Controllers\HousekeepingDashboardController::class, 'index'])->name('my-tasks');
        Route::get('/my-tasks/badge-count', [\App\Http\Controllers\HousekeepingDashboardController::class, 'badgeCount'])->name('my-tasks.badge-count');
        Route::get('/my-tasks/{task}', [\App\Http\Controllers\HousekeepingDashboardController::class, 'show'])->name('my-tasks.show');
        Route::patch('/my-tasks/checklist/{item}', [\App\Http\Controllers\HousekeepingDashboardController::class, 'updateChecklist'])->name('my-tasks.checklist.update');
        Route::post('/my-tasks/checklist/{item}/evidence', [\App\Http\Controllers\HousekeepingDashboardController::class, 'uploadChecklistEvidence'])->name('my-tasks.checklist.evidence');
        Route::post('/my-tasks/{task}/photos', [\App\Http\Controllers\HousekeepingDashboardController::class, 'uploadPhoto'])->name('my-tasks.photos.store');
        Route::delete('/my-tasks/photos/{photo}', [\App\Http\Controllers\HousekeepingDashboardController::class, 'deletePhoto'])->name('my-tasks.photos.destroy');
        Route::post('/my-tasks/{task}/complete', [\App\Http\Controllers\HousekeepingDashboardController::class, 'complete'])->name('my-tasks.complete');
    });

    // Checker Dashboard
    Route::middleware(['auth', 'permission:manage housekeeping'])->prefix('housekeeping/checker')->name('housekeeping.checker.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HousekeepingCheckerController::class, 'index'])->name('dashboard');
        Route::get('/tasks/{task}', [\App\Http\Controllers\HousekeepingCheckerController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}/approve', [\App\Http\Controllers\HousekeepingCheckerController::class, 'approve'])->name('tasks.approve');
        Route::patch('/tasks/{task}/reject', [\App\Http\Controllers\HousekeepingCheckerController::class, 'reject'])->name('tasks.reject');
        Route::patch('/checklist-items/{item}/verify', [\App\Http\Controllers\HousekeepingCheckerController::class, 'verifyItem'])->name('items.verify');
        Route::post('/checklist-items/{item}/evidence', [\App\Http\Controllers\HousekeepingCheckerController::class, 'uploadCheckerEvidence'])->name('items.evidence');
    });

    // OB Dashboard (OB role only)
    Route::middleware(['auth', 'ob.access'])->prefix('ob')->name('ob.')->group(function () {
        Route::get('/dashboard', [OBDashboardController::class, 'index'])->name('dashboard');
        Route::get('/tasks/{task}', [OBDashboardController::class, 'show'])->name('tasks.show');
        Route::patch('/checklist/{item}', [OBDashboardController::class, 'updateChecklist'])->name('checklist.update');
        Route::post('/tasks/{task}/photos', [OBDashboardController::class, 'uploadPhoto'])->name('tasks.photos.store');
        Route::post('/tasks/{task}/complete', [OBDashboardController::class, 'complete'])->name('tasks.complete');
        Route::get('/badge-count', [OBDashboardController::class, 'badgeCount'])->name('badge-count');
    });

    // Checklist Template Management (Admin)
    Route::middleware(['auth', 'permission:manage housekeeping'])->prefix('housekeeping')->name('housekeeping.')->group(function () {
        Route::get('/checklist-templates', [ChecklistTemplateController::class, 'index'])->name('checklist-templates.index');
        Route::post('/checklist-templates', [ChecklistTemplateController::class, 'store'])->name('checklist-templates.store');
        Route::put('/checklist-templates/{template}', [ChecklistTemplateController::class, 'update'])->name('checklist-templates.update');
        Route::delete('/checklist-templates/{template}', [ChecklistTemplateController::class, 'destroy'])->name('checklist-templates.destroy');
    });

    // Bonus Reports
    Route::get('/bonus-reports', [BonusReportController::class, 'index'])->name('bonus-reports.index')->middleware('permission:view reports|reports.bonus');
    Route::get('/bonus-reports/{ob}', [BonusReportController::class, 'show'])->name('bonus-reports.show')->middleware('permission:view reports|reports.bonus');
    Route::get('/bonus-reports/export/excel', [BonusReportController::class, 'exportExcel'])->name('bonus-reports.export.excel')->middleware('permission:view reports|reports.bonus');
    Route::get('/bonus-reports/export/pdf', [BonusReportController::class, 'exportPdf'])->name('bonus-reports.export.pdf')->middleware('permission:view reports|reports.bonus');

    // Employee Schedule
    Route::middleware(['auth'])->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index')->middleware('permission:schedules.manage-shifts');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store')->middleware('permission:schedules.manage-shifts');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update')->middleware('permission:schedules.manage-shifts');
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy')->middleware('permission:schedules.manage-shifts');

        Route::get('/schedule-locations', [ScheduleLocationController::class, 'index'])->name('schedule-locations.index')->middleware('permission:schedules.manage-locations');
        Route::post('/schedule-locations', [ScheduleLocationController::class, 'store'])->name('schedule-locations.store')->middleware('permission:schedules.manage-locations');
        Route::put('/schedule-locations/{scheduleLocation}', [ScheduleLocationController::class, 'update'])->name('schedule-locations.update')->middleware('permission:schedules.manage-locations');
        Route::delete('/schedule-locations/{scheduleLocation}', [ScheduleLocationController::class, 'destroy'])->name('schedule-locations.destroy')->middleware('permission:schedules.manage-locations');

        Route::get('/employee-schedules', [EmployeeScheduleController::class, 'index'])->name('employee-schedules.index')->middleware('permission:schedules.view|schedules.create');
        Route::post('/employee-schedules', [EmployeeScheduleController::class, 'store'])->name('employee-schedules.store')->middleware('permission:schedules.create');
        Route::post('/employee-schedules/bulk-assign', [EmployeeScheduleController::class, 'bulkAssign'])->name('employee-schedules.bulk-assign')->middleware('permission:schedules.create');
        Route::post('/employee-schedules/batch-update', [EmployeeScheduleController::class, 'batchUpdate'])->name('employee-schedules.batch-update')->middleware('permission:schedules.create');
        Route::post('/employee-schedules/toggle-visibility/{user}', [EmployeeScheduleController::class, 'toggleVisibility'])->name('employee-schedules.toggle-visibility')->middleware('permission:schedules.create');
        Route::get('/employee-schedules/manage-staff', [EmployeeScheduleController::class, 'manageStaff'])->name('employee-schedules.manage-staff')->middleware('permission:schedules.create');
        Route::delete('/employee-schedules/{employeeSchedule}', [EmployeeScheduleController::class, 'destroy'])->name('employee-schedules.destroy')->middleware('permission:schedules.create');
        Route::get('/employee-schedules/{employeeId}/{date}', [EmployeeScheduleController::class, 'getSchedule'])->name('employee-schedules.get')->middleware('permission:schedules.view|schedules.create');

        Route::get('/my-schedule', [MyScheduleController::class, 'index'])->name('my-schedule.index')->middleware('permission:schedules.my-schedule');

        Route::get('/shift-swaps', [ShiftSwapController::class, 'index'])->name('shift-swaps.index')->middleware('permission:schedules.approve-swap');
        Route::get('/shift-swaps/create', [ShiftSwapController::class, 'create'])->name('shift-swaps.create')->middleware('permission:schedules.request-swap');
        Route::get('/shift-swaps/employee-schedules/{employeeId}/{date}', [ShiftSwapController::class, 'getEmployeeSchedule'])->name('shift-swaps.employee-schedule');
        Route::post('/shift-swaps', [ShiftSwapController::class, 'store'])->name('shift-swaps.store')->middleware('permission:schedules.request-swap');
        Route::post('/shift-swaps/{shiftSwap}/approve', [ShiftSwapController::class, 'approve'])->name('shift-swaps.approve')->middleware('permission:schedules.approve-swap');
        Route::post('/shift-swaps/{shiftSwap}/reject', [ShiftSwapController::class, 'reject'])->name('shift-swaps.reject')->middleware('permission:schedules.approve-swap');
        Route::post('/shift-swaps/{shiftSwap}/cancel', [ShiftSwapController::class, 'cancel'])->name('shift-swaps.cancel')->middleware('permission:schedules.request-swap');
    });

    // Shift Handover
    Route::middleware(['auth'])->group(function () {
        Route::get('/shift-handovers', [\App\Http\Controllers\ShiftHandoverController::class, 'index'])
            ->name('shift-handovers.index')
            ->middleware('permission:handover.create|handover.confirm|handover.view-own');

        Route::post('/shift-handovers', [\App\Http\Controllers\ShiftHandoverController::class, 'create'])
            ->name('shift-handovers.create')
            ->middleware('permission:handover.create');

        Route::get('/shift-handovers/history', [\App\Http\Controllers\ShiftHandoverController::class, 'history'])
            ->name('shift-handovers.history')
            ->middleware('permission:handover.view-own|handover.view-all');

        Route::get('/shift-handovers/{shiftHandover}', [\App\Http\Controllers\ShiftHandoverController::class, 'show'])
            ->name('shift-handovers.show')
            ->middleware('permission:handover.view-own|handover.view-all');

        Route::put('/shift-handovers/{shiftHandover}', [\App\Http\Controllers\ShiftHandoverController::class, 'update'])
            ->name('shift-handovers.update')
            ->middleware('permission:handover.create');

        Route::post('/shift-handovers/{shiftHandover}/submit', [\App\Http\Controllers\ShiftHandoverController::class, 'submit'])
            ->name('shift-handovers.submit')
            ->middleware('permission:handover.create');

        Route::post('/shift-handovers/{shiftHandover}/confirm', [\App\Http\Controllers\ShiftHandoverController::class, 'confirm'])
            ->name('shift-handovers.confirm')
            ->middleware('permission:handover.confirm');

        Route::post('/shift-handovers/{shiftHandover}/dispute', [\App\Http\Controllers\ShiftHandoverController::class, 'dispute'])
            ->name('shift-handovers.dispute')
            ->middleware('permission:handover.confirm');

        Route::get('/shift-handovers/{shiftHandover}/pdf', [\App\Http\Controllers\ShiftHandoverController::class, 'exportPdf'])
            ->name('shift-handovers.export.pdf')
            ->middleware('permission:handover.export');

        Route::get('/handover-templates', [\App\Http\Controllers\HandoverChecklistTemplateController::class, 'index'])
            ->name('handover-templates.index')
            ->middleware('permission:handover.manage-templates');

        Route::post('/handover-templates', [\App\Http\Controllers\HandoverChecklistTemplateController::class, 'store'])
            ->name('handover-templates.store')
            ->middleware('permission:handover.manage-templates');

        Route::put('/handover-templates/{template}', [\App\Http\Controllers\HandoverChecklistTemplateController::class, 'update'])
            ->name('handover-templates.update')
            ->middleware('permission:handover.manage-templates');

        Route::delete('/handover-templates/{template}', [\App\Http\Controllers\HandoverChecklistTemplateController::class, 'destroy'])
            ->name('handover-templates.destroy')
            ->middleware('permission:handover.manage-templates');

        Route::post('/handover-templates/reorder', [\App\Http\Controllers\HandoverChecklistTemplateController::class, 'reorder'])
            ->name('handover-templates.reorder')
            ->middleware('permission:handover.manage-templates');
    });

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:view reports|reports.revenue|reports.occupancy|reports.transactions|reports.analytics');
    Route::get('/reports/kost', [\App\Http\Controllers\KostReportController::class, 'index'])->name('reports.kost')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/tenant-list', [\App\Http\Controllers\KostReportController::class, 'tenantList'])->name('reports.kost.tenant-list')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/tenant-list/export/pdf', [\App\Http\Controllers\KostReportController::class, 'exportTenantPdf'])->name('reports.kost.tenant-list.export.pdf')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/tenant-list/export/excel', [\App\Http\Controllers\KostReportController::class, 'exportTenantExcel'])->name('reports.kost.tenant-list.export.excel')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/export/excel', [\App\Http\Controllers\KostReportController::class, 'exportExcel'])->name('reports.kost.export.excel')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/export/pdf', [\App\Http\Controllers\KostReportController::class, 'exportPdf'])->name('reports.kost.export.pdf')->middleware('permission:view reports|reports.kost');

    // Kost Payment Detail (per room/per tenant)
    Route::get('/reports/kost/{booking}/detail', [\App\Http\Controllers\KostPaymentDetailController::class, 'show'])->name('reports.kost.detail')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/kost/{booking}/detail/pdf', [\App\Http\Controllers\KostPaymentDetailController::class, 'exportPdf'])->name('reports.kost.detail.pdf')->middleware('permission:view reports|reports.kost');
    Route::post('/reports/kost/approval/{approval}/approve', [\App\Http\Controllers\KostPaymentDetailController::class, 'approve'])->name('reports.kost.approval.approve')->middleware('permission:manage system');
    Route::post('/reports/kost/approval/{approval}/reject', [\App\Http\Controllers\KostPaymentDetailController::class, 'reject'])->name('reports.kost.approval.reject')->middleware('permission:manage system');

    // Monthly Report (Laporan Bulanan - Keuangan Online & Transfer)
    Route::get('/reports/monthly', [\App\Http\Controllers\MonthlyReportController::class, 'index'])->name('reports.monthly')->middleware('permission:view reports|reports.revenue');
    Route::get('/reports/monthly/pdf', [\App\Http\Controllers\MonthlyReportController::class, 'exportPdf'])->name('reports.monthly.pdf')->middleware('permission:view reports|reports.revenue');

    // OB Performance Report (Laporan Kebersihan / Performa OB)
    Route::get('/reports/ob-performance', [\App\Http\Controllers\OBPerformanceReportController::class, 'index'])->name('reports.ob-performance')->middleware('permission:view reports|reports.revenue');
    Route::get('/reports/ob-performance/pdf', [\App\Http\Controllers\OBPerformanceReportController::class, 'exportPdf'])->name('reports.ob-performance.pdf')->middleware('permission:view reports|reports.revenue');

    // Housekeeping Report (Laporan Tugas Housekeeping)
    Route::get('/housekeeping/report', [\App\Http\Controllers\HousekeepingReportController::class, 'index'])->name('reports.housekeeping')->middleware('permission:manage housekeeping');
    Route::get('/housekeeping/report/pdf', [\App\Http\Controllers\HousekeepingReportController::class, 'exportPdf'])->name('reports.housekeeping.pdf')->middleware('permission:manage housekeeping');
    Route::get('/housekeeping/report/excel', [\App\Http\Controllers\HousekeepingReportController::class, 'exportExcel'])->name('reports.housekeeping.excel')->middleware('permission:manage housekeeping');

    // Shift Report (Laporan Per Shift)
    Route::get('/reports/shift', [\App\Http\Controllers\ShiftReportController::class, 'index'])->name('reports.shift')->middleware('permission:view reports|reports.kost');
    Route::post('/reports/shift/expense', [\App\Http\Controllers\ShiftReportController::class, 'storeExpense'])->name('reports.shift.expense')->middleware('permission:view reports|reports.kost');
    Route::get('/reports/shift/export/pdf', [\App\Http\Controllers\ShiftReportController::class, 'exportPdf'])->name('reports.shift.export.pdf')->middleware('permission:view reports|reports.kost');

    // Daily Report (Laporan Harian Pendapatan & Pengeluaran)
    Route::get('/reports/daily', [\App\Http\Controllers\DailyReportController::class, 'index'])->name('reports.daily')->middleware('permission:view reports|reports.transactions');
    Route::get('/reports/daily/export/pdf', [\App\Http\Controllers\DailyReportController::class, 'exportPdf'])->name('reports.daily.export.pdf')->middleware('permission:view reports|reports.transactions');
    Route::post('/reports/daily/approve', [\App\Http\Controllers\DailyReportController::class, 'approve'])->name('reports.daily.approve')->middleware('permission:reports.daily.approve');
    Route::post('/reports/daily/reject', [\App\Http\Controllers\DailyReportController::class, 'reject'])->name('reports.daily.reject')->middleware('permission:reports.daily.approve');
    Route::match(['get', 'post'], '/reports/generate', [ReportController::class, 'generate'])->name('reports.generate')->middleware('permission:view reports|reports.revenue|reports.occupancy|reports.transactions');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy')->middleware('permission:view reports|reports.occupancy');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue')->middleware('permission:view reports|reports.revenue');
    Route::get('/reports/transactions', [ReportController::class, 'transactions'])->name('reports.transactions')->middleware('permission:view reports|reports.transactions');
    Route::get('/reports/export/transactions', [ReportController::class, 'exportTransactions'])->name('reports.export.transactions')->middleware('permission:view reports|reports.transactions');
    Route::get('/reports/room-activity', [ReportController::class, 'roomActivity'])->name('reports.room_activity')->middleware('permission:view reports|reports.occupancy');
    Route::get('/reports/export/room-activity', [ReportController::class, 'exportRoomActivity'])->name('reports.export.room_activity')->middleware('permission:view reports|reports.occupancy');
    Route::get('/reports/transfer-online', [ReportController::class, 'transferOnline'])->name('reports.transfer-online')->middleware('permission:view reports|reports.transactions');
    Route::get('/reports/bonus-karyawan', [ReportController::class, 'bonusKaryawan'])->name('reports.bonus-karyawan')->middleware('permission:view reports');
    Route::get('/reports/export/{type}/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel')->middleware('permission:view reports|reports.revenue|reports.occupancy|reports.transactions');
    Route::get('/reports/export/{type}/pdf', [ReportController::class, 'exportPDF'])->name('reports.export.pdf')->middleware('permission:view reports|reports.revenue|reports.occupancy|reports.transactions');

    // Admin
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users')->middleware('permission:manage users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store')->middleware('permission:manage users');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update')->middleware('permission:manage users');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy')->middleware('permission:manage users');
    Route::post('/users/{user}/assign-hotel', [AdminController::class, 'assignHotel'])->name('admin.users.assign-hotel')->middleware('permission:manage users');
    Route::delete('/users/{user}/hotels/{hotel}', [AdminController::class, 'removeHotel'])->name('admin.users.remove-hotel')->middleware('permission:manage users');
    Route::get('/roles', [AdminController::class, 'roles'])->name('admin.roles')->middleware('permission:manage roles');
    Route::post('/roles', [AdminController::class, 'storeRole'])->name('admin.roles.store')->middleware('permission:manage roles');
    Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('admin.roles.update')->middleware('permission:manage roles');
    Route::delete('/roles/{role}', [AdminController::class, 'destroyRole'])->name('admin.roles.destroy')->middleware('permission:manage roles');
    Route::post('/users/{user}/assign-role', [AdminController::class, 'assignRole'])->name('admin.assign-role')->middleware('permission:manage roles');
    Route::get('/settings', [AdminController::class, 'systemSettings'])->name('admin.settings')->middleware('permission:manage system');
    Route::post('/settings', [AdminController::class, 'updateSystemSettings'])->name('admin.settings.update')->middleware('permission:manage system');
    Route::get('/settings/delete-logo', [AdminController::class, 'deleteLogo'])->name('admin.settings.delete-logo')->middleware('permission:manage system');
    Route::get('/settings/delete-favicon', [AdminController::class, 'deleteFavicon'])->name('admin.settings.delete-favicon')->middleware('permission:manage system');
    Route::get('/settings/delete-login-bg', [AdminController::class, 'deleteLoginBg'])->name('admin.settings.delete-login-bg')->middleware('permission:manage system');

    // Payment Methods
    Route::prefix('payment-methods')->name('admin.payment-methods.')->middleware('permission:manage system|manage maintenance')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
        Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('update');
        Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
    });

    // Website Content Management
    Route::prefix('website')->name('admin.website.')->middleware('permission:manage system|manage maintenance')->group(function () {
        Route::get('/about', [\App\Http\Controllers\WebsiteContentController::class, 'aboutEdit'])->name('about.edit');
        Route::post('/about', [\App\Http\Controllers\WebsiteContentController::class, 'aboutUpdate'])->name('about.update');
        Route::get('/services', [\App\Http\Controllers\WebsiteContentController::class, 'servicesIndex'])->name('services.index');
        Route::post('/services', [\App\Http\Controllers\WebsiteContentController::class, 'servicesStore'])->name('services.store');
        Route::put('/services/{service}', [\App\Http\Controllers\WebsiteContentController::class, 'servicesUpdate'])->name('services.update');
        Route::delete('/services/{service}', [\App\Http\Controllers\WebsiteContentController::class, 'servicesDestroy'])->name('services.destroy');
        Route::get('/gallery', [\App\Http\Controllers\WebsiteContentController::class, 'galleryIndex'])->name('gallery.index');
        Route::post('/gallery/upload', [\App\Http\Controllers\WebsiteContentController::class, 'galleryUpload'])->name('gallery.upload');
        Route::put('/gallery/{photo}', [\App\Http\Controllers\WebsiteContentController::class, 'galleryUpdate'])->name('gallery.update');
        Route::delete('/gallery/{photo}', [\App\Http\Controllers\WebsiteContentController::class, 'galleryDestroy'])->name('gallery.destroy');
        Route::post('/gallery/reorder', [\App\Http\Controllers\WebsiteContentController::class, 'galleryReorder'])->name('gallery.reorder');
    });

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Master Data & Procurement
    Route::resource('employees', EmployeeController::class)->middleware('permission:manage system');
    Route::resource('guest-categories', GuestCategoryController::class)->middleware('permission:manage system');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:manage system');
    Route::resource('room-rates', RoomRateController::class)->middleware('permission:manage system');
    Route::get('/vouchers/check', [VoucherController::class, 'check'])->name('vouchers.check')->middleware('permission:manage reservations');
    Route::resource('vouchers', VoucherController::class)->middleware('permission:manage system');
    Route::get('/booking-sources/api', [BookingSourceController::class, 'apiList'])->name('booking-sources.api');
    Route::resource('booking-sources', BookingSourceController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:manage system');
    Route::resource('purchases', PurchaseController::class)->middleware('permission:manage procurement')->except(['destroy']);
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy')->middleware('permission:delete transactions');
    Route::get('/api/inventory/search', [PurchaseController::class, 'searchInventory'])->name('api.inventory.search')->middleware('permission:manage procurement');

    // Assets Management
    Route::resource('asset-categories', AssetCategoryController::class)->middleware('permission:manage system');
    Route::resource('assets', AssetController::class)->middleware('permission:manage system');
    Route::post('assets/{asset}/update-status', [AssetController::class, 'updateStatus'])->name('assets.update-status')->middleware('permission:manage system');
    Route::post('assets/{asset}/repair', [AssetController::class, 'addRepair'])->name('assets.add-repair')->middleware('permission:manage system');
    Route::post('assets/{asset}/stock-mutation', [AssetController::class, 'addStockMutation'])->name('assets.add-stock-mutation')->middleware('permission:manage system');

    // Tenant Management (Admin)
    Route::prefix('tenants')->name('admin.tenants.')->middleware('permission:manage system|manage maintenance|manage tenants')->group(function () {
        Route::get('/', [App\Http\Controllers\TenantController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\TenantController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}', [App\Http\Controllers\TenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [App\Http\Controllers\TenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}', [App\Http\Controllers\TenantController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [App\Http\Controllers\TenantController::class, 'destroy'])->name('destroy');
        Route::get('/{tenant}/users/create', [App\Http\Controllers\TenantController::class, 'createUser'])->name('users.create');
        Route::post('/{tenant}/users', [App\Http\Controllers\TenantController::class, 'storeUser'])->name('users.store');
    });

    // Tenant Billing Management (Admin)
    Route::prefix('tenant-billings')->name('admin.tenant-billings.')->middleware('permission:manage system|manage maintenance|manage tenants')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminTenantBillingController::class, 'index'])->name('index');
        Route::get('/generate', [App\Http\Controllers\AdminTenantBillingController::class, 'generateForm'])->name('generate');
        Route::post('/generate', [App\Http\Controllers\AdminTenantBillingController::class, 'generate'])->name('generate.store');
        Route::get('/{billing}', [App\Http\Controllers\AdminTenantBillingController::class, 'show'])->name('show');
        Route::get('/{billing}/mark-paid', [App\Http\Controllers\AdminTenantBillingController::class, 'markAsPaidForm'])->name('mark-paid');
        Route::post('/{billing}/mark-paid', [App\Http\Controllers\AdminTenantBillingController::class, 'markAsPaid'])->name('mark-paid.store');
        Route::post('/check-overdue', [App\Http\Controllers\AdminTenantBillingController::class, 'checkOverdue'])->name('check-overdue');
    });

    // Tenant Monitoring (Admin)
    Route::prefix('tenant-monitoring')->name('admin.tenant-monitoring.')->middleware('permission:manage system|manage maintenance|manage tenants')->group(function () {
        Route::get('/', [App\Http\Controllers\TenantMonitoringController::class, 'index'])->name('index');
        Route::get('/comparison', [App\Http\Controllers\TenantMonitoringController::class, 'comparison'])->name('comparison');
        Route::get('/{tenant}', [App\Http\Controllers\TenantMonitoringController::class, 'show'])->name('show');
        Route::get('/{tenant}/transactions', [App\Http\Controllers\TenantMonitoringController::class, 'transactions'])->name('transactions');
    });

    // Tenant POS Routes (Tenant User)
    Route::prefix('tenant')->name('tenant.')->middleware(['auth', 'tenant.access'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\TenantDashboardController::class, 'index'])->name('dashboard');

        // Products
        Route::get('/products', [App\Http\Controllers\TenantProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [App\Http\Controllers\TenantProductController::class, 'create'])->name('products.create');
        Route::post('/products', [App\Http\Controllers\TenantProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [App\Http\Controllers\TenantProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [App\Http\Controllers\TenantProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [App\Http\Controllers\TenantProductController::class, 'destroy'])->name('products.destroy');

        // Product Add-ons
        Route::get('/products/{product}/addons', [App\Http\Controllers\TenantAddonController::class, 'index'])->name('products.addons.index');
        Route::post('/products/{product}/addon-groups', [App\Http\Controllers\TenantAddonController::class, 'storeGroup'])->name('products.addon-groups.store');
        Route::put('/addon-groups/{group}', [App\Http\Controllers\TenantAddonController::class, 'updateGroup'])->name('addon-groups.update');
        Route::delete('/addon-groups/{group}', [App\Http\Controllers\TenantAddonController::class, 'destroyGroup'])->name('addon-groups.destroy');
        Route::post('/addon-groups/{group}/items', [App\Http\Controllers\TenantAddonController::class, 'storeItem'])->name('addon-groups.items.store');
        Route::put('/addon-items/{item}', [App\Http\Controllers\TenantAddonController::class, 'updateItem'])->name('addon-items.update');
        Route::delete('/addon-items/{item}', [App\Http\Controllers\TenantAddonController::class, 'destroyItem'])->name('addon-items.destroy');

        // Transactions
        Route::get('/transactions', [App\Http\Controllers\TenantTransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/create', [App\Http\Controllers\TenantTransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [App\Http\Controllers\TenantTransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}', [App\Http\Controllers\TenantTransactionController::class, 'show'])->name('transactions.show');
        Route::delete('/transactions/{transaction}', [App\Http\Controllers\TenantTransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::get('/transactions/{transaction}/print', [App\Http\Controllers\TenantTransactionController::class, 'print'])->name('transactions.print');
        Route::get('/transactions/daily-summary', [App\Http\Controllers\TenantTransactionController::class, 'dailySummary'])->name('transactions.daily-summary');

        // Billings (tenant view own billings)
        Route::get('/billings', [App\Http\Controllers\TenantBillingController::class, 'index'])->name('billings.index');
        Route::get('/billings/{billing}', [App\Http\Controllers\TenantBillingController::class, 'show'])->name('billings.show');

        // Inactive tenant redirect
        Route::get('/inactive', function () {
            return view('tenant.inactive');
        })->name('inactive');
    });

        // Payment Callbacks
        Route::post('/payment/midtrans-callback', [PaymentCallbackController::class, 'midtransCallback'])->name('payment.midtrans-callback');
    });
});
