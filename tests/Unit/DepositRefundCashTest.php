<?php

namespace Tests\Unit;

use App\Http\Controllers\BookingController;
use App\Models\BankAccount;
use App\Models\Booking;
use ReflectionMethod;
use Tests\TestCase;

class DepositRefundCashTest extends TestCase
{
    public function test_deposit_refund_defaults_to_cash_account_even_when_paid_by_bank(): void
    {
        // A booking whose most recent payment went to a NON-cash account.
        $booking = Booking::withoutGlobalScopes()->get()->first(function (Booking $b) {
            $lastPayment = $b->transactions()->where('type', 'payment')->whereNotNull('bank_account_id')->latest('id')->first();
            if (!$lastPayment) return false;
            $acc = BankAccount::withoutGlobalScopes()->find($lastPayment->bank_account_id);
            return $acc && !str_contains(strtolower($acc->name), 'tunai');
        });

        $hasCashAccount = $booking
            && BankAccount::withoutGlobalScopes()->where('hotel_id', $booking->hotel_id)->where('name', 'LIKE', '%tunai%')->exists();

        if (!$hasCashAccount) {
            $this->markTestSkipped('No booking paid by a non-cash account with a Tunai account available.');
        }

        $method = new ReflectionMethod(BookingController::class, 'resolveDepositRefundAccount');
        $method->setAccessible(true);
        $account = $method->invoke(app(BookingController::class), $booking);

        // Deposit (jaminan) refunds must go through cash, not the bank the guest paid with.
        $this->assertNotNull($account);
        $this->assertStringContainsStringIgnoringCase('tunai', $account->name);
    }
}
