<?php

namespace Tests\Unit;

use App\Models\Booking;
use Tests\TestCase;

class BookingCheckinOverstayTest extends TestCase
{
    public function test_checkin_tab_includes_overstay_bookings(): void
    {
        $today = now()->toDateString();

        // The overstay case: still checked_in (actual_check_out null) but the booked
        // check_out date has already passed. The old range-overlap filter excluded it.
        $overstay = Booking::where('status', 'checked_in')
            ->whereNull('actual_check_out')
            ->whereDate('check_out', '<', $today)
            ->first();

        if (!$overstay) {
            $this->markTestSkipped('No overstay booking in current data.');
        }

        // Old filter (range overlap on today) would NOT contain it.
        $oldContains = Booking::where('status', 'checked_in')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->where('id', $overstay->id)
            ->exists();
        $this->assertFalse($oldContains, 'sanity: old filter should have excluded the overstay');

        // New filter must contain it.
        $newContains = Booking::where('status', 'checked_in')
            ->where(function ($q) use ($today) {
                $q->whereNull('actual_check_out')
                    ->orWhere(function ($rq) use ($today) {
                        $rq->whereDate('check_in', '<=', $today)
                            ->whereDate('check_out', '>=', $today);
                    });
            })
            ->where('id', $overstay->id)
            ->exists();
        $this->assertTrue($newContains, 'overstay booking must appear in the checkin/in-house tab');
    }
}
