<?php

namespace App\Services;

use App\Models\Booking;

class BookingPriceService
{
    public static function grandTotal(Booking $booking): float
    {
        $manualCharges = $booking->transactions()
            ->where('type', 'charge')
            ->where('status', 'success')
            ->whereNull('reference_id')
            ->where('is_deposit', false)
            ->sum('amount');

        $posUnpaid = $booking->posOrders()
            ->where('status', 'completed')
            ->where('payment_status', 'unpaid')
            ->sum('total_amount');

        // total_price already includes the deposit set at booking creation
        // (DEPOSIT-{id} charge) — only add deposit charges made afterwards
        // (e.g. via Add Charge mid-stay), or this double-counts the original
        // deposit on top of total_price.
        $extraDeposit = $booking->transactions()
            ->where('type', 'charge')
            ->where('is_deposit', true)
            ->where(function ($q) use ($booking) {
                $q->whereNull('reference_id')
                    ->orWhere('reference_id', '!=', 'DEPOSIT-' . $booking->id);
            })
            ->sum('amount');

        return $booking->total_price + $manualCharges + $posUnpaid + $extraDeposit;
    }

    public static function remainingBalance(Booking $booking): float
    {
        $totalPaid = $booking->transactions()
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        return max(0, round(static::grandTotal($booking) - $totalPaid, 2));
    }
}
