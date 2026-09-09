<?php

namespace Tests\Unit;

use App\Models\Booking;
use Carbon\Carbon;
use Tests\TestCase;

class BookingOccupiedOnScopeTest extends TestCase
{
    public function test_occupied_on_ignores_booking_status_and_only_uses_actual_timestamps(): void
    {
        $sql = Booking::query()->occupiedOn('2026-07-15')->toSql();

        $this->assertStringNotContainsString('`status`', $sql);
        $this->assertStringContainsString('actual_check_in', $sql);
        $this->assertStringContainsString('actual_check_out', $sql);
    }

    public function test_occupied_on_uses_start_and_end_of_day_boundaries(): void
    {
        $date = Carbon::parse('2026-07-15');
        $bindings = collect(Booking::query()->occupiedOn($date)->getBindings())
            ->filter(fn ($b) => $b instanceof Carbon)
            ->map(fn (Carbon $b) => $b->format('Y-m-d H:i:s'))
            ->values();

        $this->assertContains('2026-07-15 23:59:59', $bindings);
        $this->assertContains('2026-07-15 00:00:00', $bindings);
    }
}
