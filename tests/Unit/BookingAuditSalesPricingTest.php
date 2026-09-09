<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\BookingAuditService;
use Tests\TestCase;

class BookingAuditSalesPricingTest extends TestCase
{
    public function test_sales_tier_daily_booking_is_not_flagged_base_price_mismatch(): void
    {
        // A daily booking charged the SALES tier (price_sales) whose base_price
        // equals price_sales x nights must NOT be flagged as a base_price mismatch.
        // Previously the audit expected price_public x nights and false-flagged it.
        $svc = app(BookingAuditService::class);

        $clean = Booking::withoutGlobalScopes()
            ->where('stay_type', 'daily')
            ->whereNotIn('status', ['cancelled'])
            ->with(['room.roomType', 'transactions'])
            ->get()
            ->first(function (Booking $b) {
                $tier = is_array($b->pricing_breakdown) ? ($b->pricing_breakdown['tier_applied'] ?? null) : null;
                if ($tier !== 'sales' || !$b->room) return false;
                $nights = max(1, $b->check_in->diffInDays($b->check_out));
                $salesRate = (float) ($b->room->price_sales ?: 0);
                // room whose sales price genuinely differs from public, correctly charged
                return $salesRate > 0
                    && (float) $b->room->price_public !== $salesRate
                    && abs($salesRate * $nights - (float) $b->base_price) <= 1;
            });

        if (!$clean) {
            $this->markTestSkipped('No cleanly-charged SALES daily booking in current data.');
        }

        $audit = $svc->audit($clean);
        $mismatch = collect($audit['anomalies'])->firstWhere('code', 'base_price_mismatch');

        $this->assertNull($mismatch, "SALES booking #{$clean->id} should not be flagged base_price_mismatch");
        $this->assertEquals((float) $clean->room->price_sales * max(1, $clean->check_in->diffInDays($clean->check_out)), $audit['expected']['base_price']);
    }
}
