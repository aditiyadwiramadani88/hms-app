<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\VehicleLog;
use App\Models\Voucher;
use App\Services\FinalCashService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingService
{
    protected RoomService $roomService;
    protected PricingService $pricingService;
    protected FinalCashService $finalCashService;

    public function __construct(RoomService $roomService, PricingService $pricingService, FinalCashService $finalCashService)
    {
        $this->roomService = $roomService;
        $this->pricingService = $pricingService;
        $this->finalCashService = $finalCashService;
    }

    /**
     * Create a new booking with transaction.
     *
     * @param array $data [
     *   guest_id, room_id, check_in, check_out, adults, children,
     *   voucher_code (optional), notes (optional), source (optional)
     * ]
     * @return Booking
     * @throws \Exception
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);

            $room = Room::findOrFail($data['room_id']);

            // Validate room availability
            $availableRooms = $this->getAvailableRooms(
                $data['check_in'],
                $data['check_out'],
                $room->room_type_id,
                $data['stay_type'] ?? 'daily',
                false,
                $data['check_in_time'] ?? null,
                $data['check_out_time'] ?? null
            );

            if (!$availableRooms->contains($room->id)) {
                throw new \Exception('Room is not available for the selected dates.');
            }

            // Calculate price
            $priceData = $this->calculatePrice(
                $room, 
                $checkIn, 
                $checkOut, 
                $data['voucher_code'] ?? null, 
                $data['exclude_tax'] ?? false, 
                $data['stay_type'] ?? 'daily',
                $data['manual_price'] ?? null,
                $data['check_in_time'] ?? null
            );

            $voucher = isset($data['voucher_code'])
                ? Voucher::where('code', $data['voucher_code'])->first()
                : null;

            // Breakfast Calculation
            $includeBreakfast = $data['include_breakfast'] ?? false;
            $breakfastTotal = 0;
            $appliedTier = 'public'; // Default tier
            if ($includeBreakfast) {
                $pax = 1; // Fixed 1 pack per room per night
                $nights = max(1, $checkIn->diffInDays($checkOut));
                
                // Determine which breakfast tier to use:
                // 1. Frontend-sent tier_applied takes priority
                // 2. Fall back to auto-detected tier from pricing service
                // 3. Default to 'public'
                if (!empty($data['tier_applied']) && in_array($data['tier_applied'], ['public', 'sales', 'high_season'])) {
                    $appliedTier = $data['tier_applied'];
                } else {
                    $appliedTier = $priceData['breakdown']['tier_applied'] ?? 'public';
                }
                $breakfastPrice = 0;
                
                if ($appliedTier === 'high_season') {
                    $breakfastPrice = (float) ($room->price_breakfast_high_season ?? 0);
                } elseif ($appliedTier === 'sales') {
                    $breakfastPrice = (float) ($room->price_breakfast_sales ?? 0);
                } else {
                    $breakfastPrice = (float) ($room->price_breakfast_public ?? 0);
                }
                
                $breakfastTotal = $breakfastPrice * $nights;
            }
            
            // Store tier_applied in pricing_breakdown for future reference
            $priceData['breakdown']['tier_applied'] = $appliedTier;
            $priceData['breakdown']['breakfast_total'] = round($breakfastTotal, 2);

            // Determine acting user (for public bookings, pass via data)
            $userId = $data['user_id'] ?? Auth::id();

            // Create booking
            $booking = Booking::create([
                'hotel_id' => active_hotel_id(),
                'guest_id' => $data['guest_id'],
                'room_id' => $room->id,
                'user_id' => $userId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'check_in_time' => $data['check_in_time'] ?? '14:00',
                'check_out_time' => $data['check_out_time'] ?? '12:00',
                'adults' => $data['adults'] ?? 1,
                'children' => $data['children'] ?? 0,
                'base_price' => $priceData['base_price'],
                'discount_amount' => $priceData['discount_amount'],
                'tax_amount' => $priceData['tax_amount'],
                'total_price' => $priceData['total_price'] + ($data['deposit_amount'] ?? 0) + $breakfastTotal,
                'include_breakfast' => $includeBreakfast,
                'pricing_breakdown' => $priceData['breakdown'],
                'stay_type' => $data['stay_type'] ?? 'daily',
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'tax_exempt' => $data['exclude_tax'] ?? false,
                'status' => 'confirmed',
                'payment_status' => ($priceData['total_price'] + ($data['deposit_amount'] ?? 0) + $breakfastTotal) <= 0 ? 'paid' : 'unpaid',
                'notes' => $data['notes'] ?? null,
                'voucher_code' => $voucher?->code,
                'source' => $data['source'] ?? 'walk_in',
                'booking_source_id' => $data['booking_source_id'] ?? null,
            ]);

            // Update voucher usage count
            if ($voucher) {
                $voucher->increment('usage_count');
            }

            // Create initial transaction record (excluding deposit)
            $roomCharge = $booking->total_price - ($booking->deposit_amount ?? 0);
            Transaction::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $booking->id,
                'guest_id' => $data['guest_id'],
                'user_id' => $userId,
                'type' => 'charge',
                'amount' => $roomCharge,
                'payment_method' => null,
                'reference_id' => 'BOOK-' . $booking->id,
                'description' => 'Booking charge for room ' . $room->room_number . ($includeBreakfast ? ' (including breakfast)' : ''),
                'status' => $roomCharge <= 0 ? 'success' : 'pending',
            ]);

            // Separate deposit transaction
            if (($booking->deposit_amount ?? 0) > 0) {
                Transaction::create([
                    'hotel_id' => active_hotel_id(),
                    'booking_id' => $booking->id,
                    'guest_id' => $data['guest_id'],
                    'user_id' => $userId,
                    'type' => 'charge',
                    'amount' => $booking->deposit_amount,
                    'payment_method' => null,
                    'reference_id' => 'DEPOSIT-' . $booking->id,
                    'description' => 'Security Deposit (Jaminan) - Room ' . $room->room_number,
                    'status' => 'pending',
                    'is_deposit' => true,
                ]);
            }

            // Audit log
            \App\Models\AuditLog::log(
                'booking.created',
                "Booking #{$booking->id} created for guest {$booking->guest_id}, room {$room->room_number}",
                $booking
            );

            // Handle Down Payment / Initial Payment
            if (isset($data['down_payment']) && $data['down_payment'] > 0) {
                $account = \App\Models\BankAccount::find($data['bank_account_id']);
                if ($account) {
                    $paymentMethod = str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer';
                    
                    Transaction::create([
                        'hotel_id' => active_hotel_id(),
                        'booking_id' => $booking->id,
                        'guest_id' => $data['guest_id'],
                        'user_id' => Auth::id(),
                        'bank_account_id' => $account->id,
                        'type' => 'payment',
                        'amount' => $data['down_payment'],
                        'payment_method' => $paymentMethod,
                        'description' => 'Initial payment for booking #' . $booking->id,
                        'status' => 'success',
                        'is_realized' => false, // Only realized at checkout
                    ]);

                    $account->increment('balance', $data['down_payment']);

                    // Update payment status based on amount paid
                    if ($data['down_payment'] >= $booking->total_price) {
                        $booking->update(['payment_status' => 'paid']);
                    } else {
                        $booking->update(['payment_status' => 'partial']);
                    }
                }
            }

            return $booking;
        });
    }

    /**
     * Check in a guest.
     *
     * @param Booking $booking
     * @return Booking
     * @throws \Exception
     */
    public function checkIn(Booking $booking, float $securityDeposit = 0, bool $forceEarly = false): Booking
    {
        return DB::transaction(function () use ($booking, $securityDeposit, $forceEarly) {
            if ($booking->status === 'checked_in') {
                throw new \Exception('Booking is already checked in.');
            }

            if (in_array($booking->status, ['cancelled', 'checked_out', 'no_show'])) {
                throw new \Exception('Cannot check in: booking status is ' . $booking->status);
            }

            // Prevent check-in before the scheduled date (Skip for custom bookings)
            $hotelDate = get_hotel_date(); // e.g. "2023-10-27"
            $isEarlyCheckIn = !$booking->is_custom && $booking->check_in->format('Y-m-d') > $hotelDate;
            if ($isEarlyCheckIn && !$forceEarly) {
                throw new \Exception('Belum waktunya Check-In. Jadwal check-in adalah tanggal ' . $booking->check_in->format('d M Y'));
            }

            $booking->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
                'payment_status' => $booking->payment_status === 'unpaid' ? 'partial' : $booking->payment_status,
            ]);

            // Update room status to In-House (Only for real rooms)
            if (!$booking->is_custom && $booking->room) {
                $booking->room->update(['status' => 'In-House']);
            }

            $roomIdentifier = $booking->is_custom ? $booking->custom_room_name : ($booking->room ? $booking->room->room_number : 'N/A');

            // Add security deposit if requested
            if ($securityDeposit > 0) {
                $roomNum = $booking->room ? $booking->room->room_number : ($booking->custom_room_name ?? 'N/A');
                // Keep the app-wide invariant: total_price includes deposit_amount
                // (createBooking, update, editDeposit, deleteDeposit all maintain it).
                $booking->update([
                    'deposit_amount' => $booking->deposit_amount + $securityDeposit,
                    'total_price' => $booking->total_price + $securityDeposit,
                ]);
                \App\Models\Transaction::create([
                    'booking_id' => $booking->id,
                    'hotel_id' => $booking->hotel_id,
                    'user_id' => Auth::id(),
                    'type' => 'charge',
                    'amount' => $securityDeposit,
                    'reference_id' => 'DEPOSIT-' . $booking->id,
                    'description' => 'Security Deposit (Jaminan) - Room ' . $roomNum,
                    'status' => 'success',
                    'is_deposit' => true,
                    'payment_method' => 'cash',
                ]);
            }

            \App\Models\AuditLog::log(
                'booking.checked_in',
                "Guest checked in to room {$roomIdentifier} (Booking #{$booking->id})"
                    . ($securityDeposit > 0 ? " + Security Deposit Rp " . number_format($securityDeposit, 0, ',', '.') : '')
                    . ($isEarlyCheckIn ? " [CHECK-IN PAKSA sebelum jadwal " . $booking->check_in->format('d M Y') . "]" : ''),
                $booking
            );

            return $booking->refresh();
        });
    }

    /**
     * Check out a guest.
     *
     * @param Booking $booking
     * @return Booking
     * @throws \Exception
     */
    public function checkOut(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'checked_in') {
                throw new \Exception('Can only check out from checked_in status.');
            }

            if (!$booking->check_in || !$booking->check_out) {
                throw new \Exception('Booking has missing check-in or check-out date.');
            }

            $now = now();
            
            // Default values (used in refund description if applicable)
            $checkInTime = $booking->actual_check_in ?? $booking->check_in;
            $actualNights = max(1, $checkInTime->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));
            $plannedNights = $booking->check_in->copy()->startOfDay()->diffInDays($booking->check_out->copy()->startOfDay());

            // --- LOGIKA EARLY CHECK-OUT & RECALCULATION ---
            // Skip recalculation for monthly (kost) bookings — billed full month
            if (in_array($booking->stay_type, ['monthly', 'yearly'])) {
                // No recalculation, keep original price
            } else {
            // Hitung durasi menginap aktual (minimal 1 malam)
            $checkInTime = $booking->actual_check_in ?? $booking->check_in;
            $actualNights = max(1, $checkInTime->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));
            
            // Jika durasi menginap lebih singkat dari rencana awal
            $plannedNights = $booking->check_in->copy()->startOfDay()->diffInDays($booking->check_out->copy()->startOfDay());
            
            if ($actualNights < $plannedNights) {
                $breakdown = $booking->pricing_breakdown;
                if (is_array($breakdown) && count($breakdown) > 0) {
                    $newBasePrice = 0;
                    $newBreakdown = [];
                    
                    // Ambil harga hanya untuk malam yang benar-benar dijalani
                    for ($i = 0; $i < $actualNights && $i < count($breakdown); $i++) {
                        $newBasePrice += $breakdown[$i]['price'];
                        $newBreakdown[] = $breakdown[$i];
                    }
                    
                    // Hitung ulang diskon secara proporsional (jika ada diskon harian)
                    $newDiscountAmount = 0;
                    if ($booking->discount_amount > 0) {
                        $discountPerNight = $booking->discount_amount / $plannedNights;
                        $newDiscountAmount = round($discountPerNight * $actualNights, 2);
                    }
                    
                    $newTotalPrice = $newBasePrice - $newDiscountAmount + ($booking->deposit_amount ?? 0);
                    
                    // Update data booking
                    $booking->update([
                        'base_price' => $newBasePrice,
                        'discount_amount' => $newDiscountAmount,
                        'total_price' => $newTotalPrice,
                        'check_out' => $now, // Sesuaikan tgl check-out terencana menjadi tgl saat ini
                        'pricing_breakdown' => $newBreakdown,
                    ]);
                    
                    // Update transaksi tagihan awal agar sesuai dengan harga baru
                    Transaction::where('booking_id', $booking->id)
                        ->where('reference_id', 'BOOK-' . $booking->id)
                        ->update(['amount' => $newTotalPrice]);
                        
                    \App\Models\AuditLog::log(
                        'booking.recalculated',
                        "Booking #{$booking->id} recalculated for early checkout. Nights: {$plannedNights} -> {$actualNights}. Price: Rp" . number_format($newTotalPrice),
                        $booking
                    );
                }
            }
            }
            // --- SELESAI LOGIKA RECALCULATION ---

            // Calculate final bill including any POS charges
            $posCharges = $booking->posOrders()
                ->where('payment_status', 'unpaid')
                ->where('status', 'completed')
                ->sum('total_amount');

            $additionalCharges = Transaction::where('booking_id', $booking->id)
                ->where('type', 'charge')
                ->where('status', 'success')
                ->where('reference_id', null) // Only manual charges, exclude BOOK-xxx
                ->where('is_deposit', false) // Exclude deposit items
                ->sum('amount');

            // --- LOGIKA REFUND JAMINAN ---
            $refundDeposit = request('refund_deposit') === '1';
            if ($refundDeposit && $booking->deposit_amount > 0) {
                $depositToRefund = $booking->deposit_amount;
                
                // Kurangi total price karena jaminan dikembalikan (tidak jadi pendapatan)
                $booking->update([
                    'total_price' => $booking->total_price - $depositToRefund,
                    'deposit_amount' => 0 // Mark as refunded/cleared
                ]);

                // Update transaksi tagihan awal (BOOK-xxx)
                Transaction::where('booking_id', $booking->id)
                    ->where('reference_id', 'BOOK-' . $booking->id)
                    ->decrement('amount', $depositToRefund);
                
                \App\Models\AuditLog::log(
                    'booking.deposit_refunded',
                    "Jaminan sebesar Rp " . number_format($depositToRefund) . " dikembalikan saat checkout.",
                    $booking
                );
            }
            // --- SELESAI LOGIKA REFUND JAMINAN ---

            // Calculate final bill including any POS charges
            $posCharges = $booking->posOrders()
                ->where('payment_status', 'unpaid')
                ->where('status', 'completed')
                ->sum('total_amount');

            $additionalCharges = Transaction::where('booking_id', $booking->id)
                ->where('type', 'charge')
                ->where('status', 'success')
                ->where('reference_id', null) // Only manual charges, exclude BOOK-xxx
                ->where('is_deposit', false) // Exclude deposit items
                ->sum('amount');

            $totalInvoice = $booking->total_price + $posCharges + $additionalCharges;
            $alreadyPaid = Transaction::where('booking_id', $booking->id)
                ->where('type', 'payment')
                ->where('status', 'success')
                ->sum('amount');
            
            $outstandingBalance = $totalInvoice - $alreadyPaid;

            // Handle Refund if Overpaid (Early Checkout or Deposit Refund)
            if ($outstandingBalance < 0) {
                $refundAmount = abs($outstandingBalance);
                
                // Allow manual override of refund amount
                $manualRefund = request('manual_refund_amount');
                if ($manualRefund !== null && is_numeric($manualRefund) && (float) $manualRefund >= 0) {
                    $refundAmount = (float) $manualRefund;
                }
                
                $bankAccountId = request('bank_account_id');

                if ($refundAmount > 0 && !$bankAccountId) {
                    throw new \Exception('Refund detected (Rp ' . number_format($refundAmount) . '). Please select a Bank Account for refund source.');
                }

                if ($refundAmount > 0) {
                    $account = \App\Models\BankAccount::find($bankAccountId);
                    if (!$account || $account->balance < $refundAmount) {
                        throw new \Exception('Insufficient funds in selected bank account for refund.');
                    }

                    $paymentMethod = str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer';

                    Transaction::create([
                        'hotel_id' => active_hotel_id(),
                        'booking_id' => $booking->id,
                        'guest_id' => $booking->guest_id,
                        'user_id' => Auth::id(),
                        'bank_account_id' => $account->id,
                        'type' => 'refund',
                        'amount' => $refundAmount,
                        'payment_method' => $paymentMethod,
                        'description' => 'Refund for early checkout (Recalculated from ' . $plannedNights . ' to ' . $actualNights . ' nights)',
                        'status' => 'success',
                        'is_realized' => true,
                    ]);

                    $account->decrement('balance', $refundAmount);
                    $account->decrement('available_balance', $refundAmount);
                }
                
                $outstandingBalance = 0; // Settled
            }

            if ($outstandingBalance > 0) {
                throw new \Exception(
                    'Cannot checkout: unpaid balance of Rp ' . number_format($outstandingBalance, 0, ',', '.') .
                    ' (Room: Rp ' . number_format($booking->total_price, 0, ',', '.') .
                    ' + POS: Rp ' . number_format($posCharges, 0, ',', '.') .
                    ' + Other Charges: Rp ' . number_format($additionalCharges, 0, ',', '.') .
                    ' - Paid: Rp ' . number_format($alreadyPaid, 0, ',', '.') . ')'
                );
            }

            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => $now,
                'payment_status' => 'paid',
            ]);

            // Settle charge-to-room POS orders: their amount was included in the
            // checkout bill above and has now been paid via the room, so mark them
            // paid (otherwise they keep showing 'unpaid' in the POS list forever).
            $booking->posOrders()
                ->where('status', 'completed')
                ->where('payment_status', 'unpaid')
                ->update(['payment_status' => 'paid']);

            // Mark room as Checkout after checkout (Only for real rooms)
            if (!$booking->is_custom && $booking->room) {
                $booking->room->update(['status' => 'Checkout']);
            }

            // Auto-checkout linked vehicles (Requirement 13)
            try {
                $updated = VehicleLog::where('booking_id', $booking->id)
                    ->where('status', 'in')
                    ->update([
                        'status' => 'out',
                        'time_out' => $now,
                        'notes' => DB::raw("COALESCE(notes, '') || '\nAuto-checkout dari booking #{$booking->id}'"),
                    ]);
                if ($updated > 0) {
                    \App\Models\AuditLog::log(
                        'vehicle.auto-checkout',
                        "Auto-checkout {$updated} kendaraan dari booking #{$booking->id}",
                        $booking
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-checkout vehicle gagal untuk booking #' . $booking->id . ': ' . $e->getMessage());
            }

            // Create outstanding charge if applicable
            if ($outstandingBalance > 0) {
                Transaction::create([
                    'hotel_id' => active_hotel_id(),
                    'booking_id' => $booking->id,
                    'guest_id' => $booking->guest_id,
                    'user_id' => Auth::id(),
                    'type' => 'charge',
                    'amount' => $outstandingBalance,
                    'payment_method' => null,
                    'reference_id' => 'FINAL-' . $booking->id,
                    'description' => 'Final outstanding balance at checkout',
                    'status' => 'pending',
                ]);
            }

            // Mark all related unrealized payments as realized
            $unrealizedPayments = Transaction::where('booking_id', $booking->id)
                ->where('type', 'payment')
                ->where('is_realized', false)
                ->where('status', 'success')
                ->get();

            $cashTotals = [];
            foreach ($unrealizedPayments as $trx) {
                $trx->update(['is_realized' => true]);
                if ($trx->bank_account_id) {
                    $account = \App\Models\BankAccount::find($trx->bank_account_id);
                    if ($account) {
                        $account->increment('available_balance', $trx->amount);

                        if ($account->isCashAccount()) {
                            $cashTotals[$account->id] = ($cashTotals[$account->id] ?? 0) + (float) $trx->amount;
                        }
                    }
                }
            }

            $roomIdentifier = $booking->is_custom ? $booking->custom_room_name : ($booking->room ? $booking->room->room_number : 'N/A');
            $guestName = $booking->guest->name ?? 'N/A';

            foreach ($cashTotals as $accountId => $total) {
                $cashAccount = \App\Models\BankAccount::find($accountId);
                if ($cashAccount) {
                    $this->finalCashService->recordIn(
                        $cashAccount,
                        $total,
                        'booking_checkout',
                        $booking->id,
                        "Checkout Booking #{$booking->id} - Room {$roomIdentifier}, {$guestName}"
                    );
                }
            }

            $roomIdentifier = $booking->is_custom ? $booking->custom_room_name : ($booking->room ? $booking->room->room_number : 'N/A');
            \App\Models\AuditLog::log(
                'booking.checked_out',
                "Guest checked out from room {$roomIdentifier} (Booking #{$booking->id}). Total: {$totalInvoice}, Paid: {$alreadyPaid}.",
                $booking
            );

            return $booking->refresh();
        });
    }

    /**
     * Cancel a booking.
     *
     * @param Booking $booking
     * @return Booking
     * @throws \Exception
     */
    public function cancelBooking(Booking $booking, ?string $reason = null, $photo = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason, $photo) {
            if ($booking->status === 'checked_in') {
                throw new \Exception('Cannot cancel a booking that is already checked in.');
            }

            if (in_array($booking->status, ['checked_out', 'no_show'])) {
                throw new \Exception('Cannot cancel: booking is already ' . $booking->status);
            }

            if ($booking->status === 'cancelled') {
                throw new \Exception('Booking is already cancelled.');
            }

            $previousStatus = $booking->status;

            $photoPath = null;
            if ($photo && $photo->isValid()) {
                $photoPath = $photo->store('cancellations', 'public');
            }

            $totalPaid = Transaction::where('booking_id', $booking->id)
                ->where('type', 'payment')
                ->where('status', 'success')
                ->sum('amount');

            if ($totalPaid > 0) {
                $refundAccount = $this->resolveRefundAccount($booking);
                Transaction::create([
                    'booking_id' => $booking->id,
                    'guest_id' => $booking->guest_id,
                    'user_id' => Auth::id(),
                    'bank_account_id' => $refundAccount?->id,
                    'type' => 'refund',
                    'amount' => $totalPaid,
                    'payment_method' => $refundAccount
                        ? (str_contains(strtolower($refundAccount->name), 'tunai') ? 'cash' : 'bank_transfer')
                        : $booking->transactions()->latest()->first()?->payment_method,
                    'reference_id' => 'REFUND-' . $booking->id,
                    'description' => 'Refund for cancelled booking #' . $booking->id,
                    'status' => 'success',
                ]);
                if ($refundAccount) {
                    $refundAccount->decrement('balance', $totalPaid);
                    $refundAccount->decrement('available_balance', $totalPaid);
                }

                $booking->update(['payment_status' => 'refunded']);
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
                'cancelled_photo' => $photoPath,
            ]);

            if ($booking->room && $booking->room->status === 'occupied') {
                $booking->room->update(['status' => 'dirty']);
            }

            \App\Models\AuditLog::log(
                'booking.cancelled',
                "Booking #{$booking->id} cancelled (was: {$previousStatus}). Reason: {$reason}. Refund: {$totalPaid}",
                $booking
            );

            return $booking->refresh();
        });
    }

    /**
     * Get rooms that are not booked during the specified date range.
     *
     * @param string|Carbon $checkIn
     * @param string|Carbon $checkOut
     * @param int|null $roomTypeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    /**
     * Determine which bank account refund money leaves from, so the refund
     * shows in the right wallet column of the daily report and the account
     * balance stays accurate. Order: the account the guest's latest payment
     * went into (refund normally comes out of the same drawer), then the
     * cash (Tunai) account, then any account. Scoped to the booking's hotel
     * explicitly so it also works from console commands where the
     * active-hotel global scope is inactive.
     */
    public function resolveRefundAccount(Booking $booking): ?BankAccount
    {
        $lastPayment = $booking
            ->transactions()
            ->where('type', 'payment')
            ->where('status', 'success')
            ->whereNotNull('bank_account_id')
            ->latest('id')
            ->first();
        if ($lastPayment) {
            $account = BankAccount::withoutGlobalScopes()
                ->where('hotel_id', $booking->hotel_id)
                ->find($lastPayment->bank_account_id);
            if ($account) {
                return $account;
            }
        }

        return BankAccount::withoutGlobalScopes()
            ->where('hotel_id', $booking->hotel_id)
            ->where('name', 'LIKE', '%tunai%')
            ->first()
            ?? BankAccount::withoutGlobalScopes()
                ->where('hotel_id', $booking->hotel_id)
                ->first();
    }

    /**
     * Combine a booking's date (check_in/check_out column, always midnight)
     * with its separately-stored time-of-day string (check_in_time/check_out_time)
     * into the actual arrival/departure instant. The bookings.check_in/check_out
     * columns never carry a real time component -- only the *_time string
     * columns do -- so any conflict check that skips this ends up comparing
     * whole calendar days and can't tell a legitimate same-day turnover
     * (checkout 12:00, new check-in 14:00) apart from a genuine same-day
     * double-booking (new check-in 00:30 while the old guest is still there
     * until checkout at 12:00).
     */
    public function combineDateAndTime($date, ?string $time, string $default): Carbon
    {
        $d = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();
        $t = ($time && preg_match('/^\d{2}:\d{2}$/', $time)) ? $time : $default;
        [$h, $m] = explode(':', $t);
        return $d->setTime((int) $h, (int) $m);
    }

    public function getAvailableRooms($checkIn, $checkOut, ?int $roomTypeId = null, ?string $stayType = 'daily', bool $includeUnavailable = false, ?string $checkInTime = null, ?string $checkOutTime = null)
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);
        $newCheckInDT = $this->combineDateAndTime($checkIn, $checkInTime, '14:00');
        $newCheckOutDT = $this->combineDateAndTime($checkOut, $checkOutTime, '12:00');

        // Rooms that are physically out of service/maintenance cannot be booked.
        // Rooms that are Checkout, Dirty, Clean, or In-House are allowed because booking schedule conflict check below handles date availability.
        $unavailableStatuses = ['Maintenance', 'maintenance', 'Out of Order', 'out_of_order', 'Closed', 'closed'];

        $query = Room::query()
            ->when(!$includeUnavailable, function ($q) use ($unavailableStatuses) {
                return $q->whereNotIn('status', $unavailableStatuses);
            })
            ->when($stayType === 'monthly', function ($q) {
                return $q->where('is_kos', true);
            })
            ->with('roomType');

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $rooms = $query->orderByRaw('CAST(room_number AS UNSIGNED) ASC')->get();

        if ($includeUnavailable) {
            return $rooms;
        }

        // Broad date-only pre-filter (padded 1 day either side so we never miss a
        // real conflict), then precise time-aware overlap check in PHP -- the
        // date columns alone can't distinguish a same-day turnover from an
        // actual same-day double-booking.
        $candidateBookings = Booking::whereIn('room_id', $rooms->pluck('id'))
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', $checkOut->copy()->addDay())
            ->whereDate('check_out', '>=', $checkIn->copy()->subDay())
            ->get(['room_id', 'check_in', 'check_out', 'check_in_time', 'check_out_time']);

        $conflictingRoomIds = [];
        foreach ($candidateBookings as $b) {
            $existingIn = $this->combineDateAndTime($b->check_in, $b->check_in_time, '14:00');
            $existingOut = $this->combineDateAndTime($b->check_out, $b->check_out_time, '12:00');
            if ($existingIn->lt($newCheckOutDT) && $existingOut->gt($newCheckInDT)) {
                $conflictingRoomIds[$b->room_id] = true;
            }
        }

        return $rooms->reject(fn($room) => isset($conflictingRoomIds[$room->id]))->values();
    }

    /**
     * Calculate total price for a booking with weekend/holiday pricing and voucher discount.
     *
     * @param Room $room
     * @param string|Carbon $checkIn
     * @param string|Carbon $checkOut
     * @param string|Voucher|null $voucher
     * @return array {base_price, discount_amount, tax_amount, total_price, breakdown}
     */
    public function calculatePrice(Room $room, $checkIn, $checkOut, $voucher = null, bool $isTaxExempt = false, string $stayType = 'daily', $manualPrice = null, ?string $checkInTime = null): array
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);

        // Treat manual_price = 0 as null (no override)
        if ($manualPrice !== null && (float) $manualPrice <= 0) {
            $manualPrice = null;
        }
        
        $guest = null;
        if (request()->has('guest_id')) {
            $guest = Guest::find(request('guest_id'));
        }

        $nights = $checkIn->diffInDays($checkOut);

        $isDiniHari = $checkInTime && $checkInTime >= '00:00' && $checkInTime < '06:00';
        if ($nights < 0 || ($nights === 0 && !$isDiniHari)) {
            throw new \Exception('Check-out date must be after check-in date.');
        }
        if ($isDiniHari && $nights === 0) {
            $nights = 1;
        }

        $totalBasePrice = 0;
        $breakdown = [];

        // Calculate per-day pricing
        $current = $checkIn->copy();
        $overallTier = 'public'; // Default tier
        
        for ($i = 0; $i < $nights; $i++) {
            if ($stayType === 'monthly' && $manualPrice !== null) {
                $dynamicPrice = (float) $manualPrice / max(1, $nights);
            } elseif ($manualPrice !== null) {
                $dynamicPrice = (float) $manualPrice;
            } elseif ($stayType === 'monthly') {
                // For monthly, assume price_kos is the rate for 30 nights
                $dailyRate = (float) ($room->price_kos ?? $room->price_public ?? 0) / 30;
                $dynamicPrice = $dailyRate;
            } elseif ($stayType === 'yearly') {
                $yearlyRate = (float) ($room->yearly_price ?? $room->roomType->yearly_price ?? 0);
                $dailyRate = $yearlyRate > 0 ? $yearlyRate / 360 : (float) ($room->roomType->base_price);
                $dynamicPrice = $dailyRate;
            } else {
                $pricingResult = $this->pricingService->calculateRoomPrice($room->roomType, $guest, $current);
                $dynamicPrice = $pricingResult['price'];
                $overallTier = $pricingResult['tier'];
                
                // Override with room->price_public if it falls back to roomType base
                if ($dynamicPrice == (float)$room->roomType->base_price) {
                    $dynamicPrice = (float) ($room->price_public ?? $room->roomType->base_price);
                }
            }

            $totalBasePrice += $dynamicPrice;

            $breakdown[] = [
                'date' => $current->toDateString(),
                'day_of_week' => $current->format('l'),
                'price' => round($dynamicPrice, 2),
            ];

            $current->addDay();
        }

        // Add overall tier to breakdown so it can be retrieved for breakfast calculation
        $breakdown['tier_applied'] = $overallTier;

        // Special handling for monthly/manual: monthly selected price is total monthly price, not daily price
        if ($stayType === 'monthly' && $manualPrice !== null) {
            // manualPrice = price per month. Multiply by number of months.
            $kostMonths = max(1, (int) round($nights / 30));
            $totalBasePrice = (float) $manualPrice * $kostMonths;
        } elseif ($stayType === 'monthly' && $nights >= 25 && $manualPrice === null) {
            $kostMonths = max(1, (int) round($nights / 30));
            $baseKosPrice = (float) ($room->price_kos ?? $room->price_public ?? 0);
            // Look up pricing tier for (room, duration_months)
            $tier = $room->kostPricingTiers()
                ->where('duration_months', $kostMonths)
                ->first();
            if ($tier) {
                if ($tier->discount_type === 'fixed') {
                    // fixed_price = total for the entire duration
                    $totalBasePrice = (float) $tier->fixed_price;
                    $breakdown['kost_effective_rate'] = (float) $tier->fixed_price / $kostMonths;
                } else {
                    $effectiveRate = $tier->getEffectiveRate($baseKosPrice);
                    $totalBasePrice = $effectiveRate * $kostMonths;
                    $breakdown['kost_effective_rate'] = $effectiveRate;
                }
                $breakdown['kost_tier_applied'] = true;
                $breakdown['kost_original_rate'] = $baseKosPrice;
            } else {
                $totalBasePrice = $baseKosPrice * $kostMonths;
            }
        } elseif ($stayType === 'yearly') {
            $years = max(1, (int) ceil($nights / 360));
            $yearlyPrice = (float) ($manualPrice ?? $room->yearly_price ?? $room->roomType->yearly_price ?? 0);
            if ($yearlyPrice <= 0) {
                $yearlyPrice = (float) ($room->roomType->base_price * 360);
            }
            $totalBasePrice = $yearlyPrice * $years;
        } elseif ($manualPrice !== null) {
            $totalBasePrice = (float) $manualPrice * $nights;
        }

        // Calculate discount from voucher
        $discountAmount = 0;

        if ($voucher === null && request()->has('voucher_code')) {
            $voucher = Voucher::where('code', request('voucher_code'))->first();
        } elseif (is_string($voucher)) {
            $voucher = Voucher::where('code', $voucher)->first();
        }

        if ($voucher instanceof Voucher && $voucher->isValid()) {
            // Check minimum booking amount
            if ($voucher->min_booking_amount && $totalBasePrice < $voucher->min_booking_amount) {
                // Voucher not applicable
            } else {
                if ($voucher->type === 'percentage') {
                    $discountAmount = $totalBasePrice * ($voucher->value / 100);
                } else {
                    $discountAmount = $voucher->value;
                }

                // Cap at max_discount
                if ($voucher->max_discount && $discountAmount > $voucher->max_discount) {
                    $discountAmount = $voucher->max_discount;
                }
            }
        }

        $subtotal = max(0, $totalBasePrice - $discountAmount);

        // Tax calculation - FORCED TO 0 (No tax as per user request)
        $taxAmount = 0;
        $totalPrice = $subtotal + $taxAmount;

        return [
            'base_price' => round($totalBasePrice, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => $taxAmount,
            'total_price' => round($totalPrice, 2),
            'nights' => $nights,
            'breakdown' => $breakdown,
        ];
    }
}
