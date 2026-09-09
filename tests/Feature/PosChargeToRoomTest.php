<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Inventory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookingPriceService;
use App\Services\PosService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PosChargeToRoomTest extends TestCase
{
    use DatabaseTransactions; // rolls back — safe against the real DB

    public function test_charge_to_room_increases_remaining_balance_without_phantom_payment(): void
    {
        $user = User::first();
        $booking = Booking::withoutGlobalScopes()->where('status', 'checked_in')->whereNotNull('room_id')->first();
        $inventory = Inventory::first();

        if (!$user || !$booking || !$inventory) {
            $this->markTestSkipped('Needs a user, a checked_in booking, and an inventory item.');
        }

        $this->actingAs($user);
        $svc = app(PosService::class);

        $remainingBefore = BookingPriceService::remainingBalance($booking);
        $paymentsBefore = Transaction::where('booking_id', $booking->id)->where('type', 'payment')->count();

        $order = $svc->createOrder(
            ['guest_id' => $booking->guest_id, 'room_id' => $booking->room_id, 'booking_id' => $booking->id],
            [['inventory_id' => $inventory->id, 'item_name' => $inventory->name, 'quantity' => 2, 'price_per_unit' => 15000]]
        );
        $svc->chargeToRoomBooking($order->refresh(), $booking->refresh());
        $order->refresh();
        $booking->refresh();

        $remainingAfter = BookingPriceService::remainingBalance($booking);
        $paymentsAfter = Transaction::where('booking_id', $booking->id)->where('type', 'payment')->count();

        // Core bug: the charge must appear on the room bill...
        $this->assertEqualsWithDelta($order->total_amount, $remainingAfter - $remainingBefore, 0.01,
            'Charge-to-room must increase the room remaining balance by the order total');
        // ...and it is a charge, not a payment.
        $this->assertSame($paymentsBefore, $paymentsAfter, 'Charge-to-room must not create a payment transaction');
        // Order stays completed+unpaid so grandTotal/checkout keep counting it.
        $this->assertSame('completed', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('charge_to_room', $order->payment_method);
    }

    public function test_new_order_form_with_charge_to_room_payment_method_lands_on_grand_total(): void
    {
        // Mirrors the actual POS "New Order" form: staff picks the "Charge to
        // Room" option, which the controller sends as payment_method=charge_to_room
        // with bank_account_id normalized to null (it's a sentinel <select>
        // option, not a real bank account row) — no separate chargeToRoomBooking
        // call. createOrder() must charge the room itself in that case.
        $user = User::first();
        $booking = Booking::withoutGlobalScopes()->where('status', 'checked_in')->whereNotNull('room_id')->first();
        $inventory = Inventory::first();

        if (!$user || !$booking || !$inventory) {
            $this->markTestSkipped('Needs a user, a checked_in booking, and an inventory item.');
        }

        $this->actingAs($user);
        session(['active_hotel_id' => $booking->hotel_id]);
        $svc = app(PosService::class);

        $remainingBefore = BookingPriceService::remainingBalance($booking);

        $order = $svc->createOrder(
            [
                'guest_id' => $booking->guest_id,
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'bank_account_id' => null,
                'payment_method' => 'charge_to_room',
            ],
            [['inventory_id' => $inventory->id, 'item_name' => $inventory->name, 'quantity' => 2, 'price_per_unit' => 15000]]
        );
        $booking->refresh();

        $remainingAfter = BookingPriceService::remainingBalance($booking);

        $this->assertSame('completed', $order->status,
            'Order left as "pending" never gets counted in Grand Total — it only shows in the Extra Charges list, causing the remaining balance mismatch.');
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertEqualsWithDelta($order->total_amount, $remainingAfter - $remainingBefore, 0.01,
            'Charge-to-room from the New Order form must increase the room remaining balance by the order total');
    }
}
