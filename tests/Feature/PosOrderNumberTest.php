<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PosOrderNumberTest extends TestCase
{
    use DatabaseTransactions; // rolls back — safe against the real DB

    private function makeOrder(array $overrides = []): PosOrder
    {
        return PosOrder::create(array_merge([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_next_order_number_ignores_a_non_numeric_suffix_left_by_a_manual_patch(): void
    {
        // Reproduces the production incident: a prior collision was patched
        // by hand with a random-suffix order_number (e.g. "POS-...-I2AY").
        // The old logic read the *latest* row's last 4 chars and (int)-cast
        // them to get the next sequence — a non-numeric suffix silently cast
        // to 0, so the "next" number reset to 0001 and collided with the
        // original 0001 all over again.
        $hotel = Hotel::where('is_active', true)->first();
        $user = User::first();
        if (!$hotel || !$user) {
            $this->markTestSkipped('Needs an active hotel and a user.');
        }
        session(['active_hotel_id' => $hotel->id]);
        $this->actingAs($user);

        $date = now()->format('Ymd');
        $this->makeOrder(['order_number' => "POS-{$date}-0001"]);
        $this->makeOrder(['order_number' => "POS-{$date}-ZZZZ"]); // simulated manual patch, non-numeric suffix

        $order = $this->makeOrder(); // order_number left null -> auto-generated

        $this->assertNotSame("POS-{$date}-0001", $order->order_number,
            'Must not regenerate a sequence that collides with an already-used order_number');
    }

    public function test_next_order_number_is_unique_across_hotels_sharing_the_same_day(): void
    {
        // order_number is a globally unique column, but PosOrder is scoped
        // per-hotel by BelongsToHotel — two different hotels placing their
        // first order of the day both computed sequence 0001 and collided
        // on the unique constraint even though neither hotel's own query
        // saw the other's row.
        $hotels = Hotel::where('is_active', true)->take(2)->get();
        $user = User::first();
        if ($hotels->count() < 2 || !$user) {
            $this->markTestSkipped('Needs at least 2 active hotels and a user.');
        }
        $this->actingAs($user);

        session(['active_hotel_id' => $hotels[0]->id]);
        $orderA = $this->makeOrder();

        session(['active_hotel_id' => $hotels[1]->id]);
        $orderB = $this->makeOrder();

        $this->assertNotSame($orderA->order_number, $orderB->order_number);
    }
}
