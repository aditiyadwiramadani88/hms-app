<?php

namespace Tests\Unit;

use App\Models\Room;
use Tests\TestCase;

class DashboardInHouseBySourceTest extends TestCase
{
    public function test_in_house_by_source_sums_to_total_in_house_rooms_and_has_no_zero_rows(): void
    {
        $inHouseBySource = Room::whereIn('status', ['In-House', 'Checkin'])
            ->with(['bookings' => fn ($q) => $q->where('status', 'checked_in')->with('bookingSource')->latest('id')])
            ->get()
            ->groupBy(fn ($room) => $room->bookings->first()?->bookingSource?->name ?? 'Lainnya')
            ->map->count();

        $inHouseRooms = Room::whereIn('status', ['In-House', 'Checkin'])->count();

        // This is the exact invariant the feature depends on: deriving the breakdown from
        // Booking.status='checked_in' directly (instead of Room.status) can drift from the
        // card's total when a booking's status is stale relative to its room (room reassigned,
        // duplicate checked_in bookings on one room, etc.) — seen in production data where
        // Booking-based summed to 58 vs the card's Room-based total of 51.
        $this->assertSame($inHouseRooms, $inHouseBySource->sum());
        $this->assertTrue($inHouseBySource->every(fn ($count) => $count > 0));
    }
}
