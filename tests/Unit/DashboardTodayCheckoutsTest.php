<?php

namespace Tests\Unit;

use App\Models\Booking;
use Tests\TestCase;

class DashboardTodayCheckoutsTest extends TestCase
{
    public function test_today_checkouts_excludes_already_checked_out_bookings(): void
    {
        $hotelId = active_hotel_id();
        $alreadyLeft = Booking::where('hotel_id', $hotelId)
            ->where('status', 'checked_out')
            ->whereNotNull('check_out')
            ->first();

        if (!$alreadyLeft) {
            $this->markTestSkipped('No checked_out booking in current data to verify against.');
        }

        $date = $alreadyLeft->check_out->toDateString();

        $pendingIds = Booking::where('hotel_id', $hotelId)
            ->where('status', 'checked_in')
            ->whereDate('check_out', $date)
            ->pluck('id');

        $this->assertNotContains($alreadyLeft->id, $pendingIds->all());
    }
}
