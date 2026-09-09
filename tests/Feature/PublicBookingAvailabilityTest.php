<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicBookingAvailabilityTest extends TestCase
{
    use DatabaseTransactions; // rolls back — safe against the real DB

    public function test_room_type_with_only_room_currently_occupied_still_lists_for_a_free_future_date_range(): void
    {
        // Reproduces the "Deluxe 1 gak muncul di list booking" report: a room
        // type with a single room that happens to be In-House right now must
        // still appear when the guest is browsing a future date range the
        // room is actually free for — the list must not be driven by the
        // room's current status snapshot.
        $hotel = Hotel::where('is_active', true)->first();
        if (!$hotel) {
            $this->markTestSkipped('Needs an active hotel.');
        }
        session(['active_hotel_id' => $hotel->id]);

        $roomType = RoomType::create([
            'name' => 'Test Solo Type ' . uniqid(),
            'base_price' => 150000,
            'max_guests' => 2,
            'is_active' => true,
            'hotel_id' => $hotel->id,
        ]);

        Room::create([
            'room_number' => 'T' . random_int(1000, 9999),
            'room_type_id' => $roomType->id,
            'floor' => 1,
            'status' => 'In-House', // occupied right now, but not for the future range below
            'price_public' => 150000,
            'hotel_id' => $hotel->id,
        ]);

        $checkIn = now()->addDays(30)->format('Y-m-d');
        $checkOut = now()->addDays(31)->format('Y-m-d');

        $response = $this->get('/booking?check_in=' . $checkIn . '&check_out=' . $checkOut);

        $response->assertOk();
        $roomTypes = $response->viewData('roomTypes');
        $this->assertTrue(
            $roomTypes->contains('id', $roomType->id),
            'Room type should appear for a future date range even though its only room is In-House today'
        );
    }
}
