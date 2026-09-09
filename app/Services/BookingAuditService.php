<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RoomKostPricingTier;
use Carbon\Carbon;

class BookingAuditService
{
    const ANOMALY_KOST_TIER_DOUBLE_COUNT = 'kost_tier_double_count';
    const ANOMALY_BASE_PRICE_MISMATCH = 'base_price_mismatch';
    const ANOMALY_DISCOUNT_MISMATCH = 'discount_mismatch';
    const ANOMALY_TOTAL_FORMULA_WRONG = 'total_formula_wrong';
    const ANOMALY_DISCOUNT_EXCEEDS_BASE = 'discount_exceeds_base';
    const ANOMALY_NEGATIVE_PRICE = 'negative_price';
    const ANOMALY_ZERO_PRICE = 'zero_price';
    const ANOMALY_OVERPAYMENT = 'overpayment';
    const ANOMALY_DEPOSIT_MISMATCH = 'deposit_mismatch';
    const ANOMALY_KOST_MONTHS_MISMATCH = 'kost_months_mismatch';
    const ANOMALY_RATE_CHANGED = 'rate_changed';

    /**
     * All known anomaly patterns with descriptions.
     */
    public static function anomalyCatalog(): array
    {
        return [
            self::ANOMALY_KOST_TIER_DOUBLE_COUNT => [
                'label' => 'Kost Tier Double-Count',
                'description' => 'RoomKostPricingTier.fixed_price dikali kostMonths lagi. fixed_price = total untuk durasi, bukan harga per bulan.',
                'severity' => 'critical',
                'example' => 'Tier 6-bulan fixed_price=10.200.000 → salah hitung 10.200.000×6=61.200.000',
            ],
            self::ANOMALY_BASE_PRICE_MISMATCH => [
                'label' => 'Base Price Mismatch',
                'description' => 'Room Subtotal tidak sesuai dengan rate × duration. Kemungkinan salah hitung atau data korup.',
                'severity' => 'critical',
                'example' => 'Rate 1.7jt/bulan × 6 bulan = 10.2jt tapi tersimpan 61.2jt',
            ],
            self::ANOMALY_DISCOUNT_MISMATCH => [
                'label' => 'Discount Mismatch',
                'description' => 'Diskon tersimpan tidak sesuai dengan tier discount atau voucher yang seharusnya.',
                'severity' => 'high',
                'example' => 'Tier discount 850rb tapi tersimpan 0, atau sebaliknya',
            ],
            self::ANOMALY_TOTAL_FORMULA_WRONG => [
                'label' => 'Total Formula Wrong',
                'description' => 'total_price ≠ base_price - discount + deposit + breakfast. Ada perhitungan yang salah.',
                'severity' => 'critical',
                'example' => 'base=10.2jt - discount=850rb + deposit=0 = 9.35jt tapi total=60.35jt',
            ],
            self::ANOMALY_DISCOUNT_EXCEEDS_BASE => [
                'label' => 'Discount Exceeds Base',
                'description' => 'Diskon lebih besar dari base_price. Harusnya di-cap di 0.',
                'severity' => 'high',
                'example' => 'base=5jt tapi discount=7jt → total negatif',
            ],
            self::ANOMALY_NEGATIVE_PRICE => [
                'label' => 'Negative Price',
                'description' => 'Ada field harga yang negatif. Kemungkinan error input atau kalkulasi.',
                'severity' => 'critical',
                'example' => 'base_price=-1.000.000',
            ],
            self::ANOMALY_ZERO_PRICE => [
                'label' => 'Zero Price (Active Booking)',
                'description' => 'Booking aktif tapi base_price atau total_price = 0. Mungkin lupa input harga.',
                'severity' => 'medium',
                'example' => 'Booking checked_in tapi total_price=0',
            ],
            self::ANOMALY_OVERPAYMENT => [
                'label' => 'Overpayment',
                'description' => 'Total pembayaran melebihi grand total. Uang lebih yang perlu di-refund atau koreksi.',
                'severity' => 'medium',
                'example' => 'Grand total 10jt tapi sudah bayar 15jt',
            ],
            self::ANOMALY_DEPOSIT_MISMATCH => [
                'label' => 'Deposit Mismatch',
                'description' => 'deposit_amount di booking tidak cocok dengan total deposit transactions.',
                'severity' => 'medium',
                'example' => 'deposit_amount=500rb tapi transaksi deposit=1jt',
            ],
            self::ANOMALY_KOST_MONTHS_MISMATCH => [
                'label' => 'Kost Months Mismatch',
                'description' => 'Jumlah bulan yang dipakai hitung harga berbeda dari yang ditampilkan.',
                'severity' => 'high',
                'example' => 'Display "6 bulan" tapi hitung pakai 7 bulan',
            ],
            self::ANOMALY_RATE_CHANGED => [
                'label' => 'Rate Changed Since Booking',
                'description' => 'Harga kamar sekarang berbeda dari saat booking dibuat. Informasi saja, bukan error.',
                'severity' => 'info',
                'example' => 'Sewa 1.7jt/bulan tapi sekarang harga naik jadi 2jt',
            ],
        ];
    }

    /**
     * Audit a booking's pricing and return findings.
     */
    public function audit(Booking $booking): array
    {
        $anomalies = [];
        $booking->load(['room.roomType', 'room.kostPricingTiers', 'transactions']);

        $nights = $booking->check_in->diffInDays($booking->check_out);
        $stayType = $booking->stay_type ?? 'daily';

        // === CHECK 1: Negative prices ===
        if ((float) $booking->base_price < 0) {
            $anomalies[] = [
                'field' => 'base_price',
                'code' => self::ANOMALY_NEGATIVE_PRICE,
                'label' => 'Room Subtotal',
                'expected' => abs((float) $booking->base_price),
                'actual' => (float) $booking->base_price,
                'diff' => (float) $booking->base_price,
            ];
        }
        if ((float) $booking->total_price < 0) {
            $anomalies[] = [
                'field' => 'total_price',
                'code' => self::ANOMALY_NEGATIVE_PRICE,
                'label' => 'Total Price',
                'expected' => 0,
                'actual' => (float) $booking->total_price,
                'diff' => (float) $booking->total_price,
            ];
        }

        // === CHECK 2: Zero price on active booking ===
        $activeStatuses = ['confirmed', 'checked_in', 'pending'];
        if (in_array($booking->status, $activeStatuses) && (float) $booking->base_price == 0 && !$booking->is_custom) {
            $anomalies[] = [
                'field' => 'base_price',
                'code' => self::ANOMALY_ZERO_PRICE,
                'label' => 'Room Subtotal',
                'expected' => 'non-zero',
                'actual' => 0,
                'diff' => 0,
            ];
        }

        // === CHECK 3: Discount exceeds base ===
        if ((float) $booking->discount_amount > (float) $booking->base_price && (float) $booking->base_price > 0) {
            $anomalies[] = [
                'field' => 'discount_amount',
                'code' => self::ANOMALY_DISCOUNT_EXCEEDS_BASE,
                'label' => 'Discount',
                'expected' => (float) $booking->base_price,
                'actual' => (float) $booking->discount_amount,
                'diff' => (float) $booking->discount_amount - (float) $booking->base_price,
            ];
        }

        // === CHECK 4: Recalculate expected base_price ===
        $expectedBasePrice = $this->calculateExpectedBasePrice($booking, $nights, $stayType);

        if (abs($expectedBasePrice - (float) $booking->base_price) > 1) {
            // Detect specific pattern: kost tier double-count
            $code = self::ANOMALY_BASE_PRICE_MISMATCH;

            // Daily/yearly: if base_price still matches what was actually
            // charged at creation time (pricing_breakdown), the mismatch vs.
            // the room's CURRENT rate just means the rate went up since this
            // booking was made — informational, not a calculation error.
            if ($stayType !== 'monthly' && is_array($booking->pricing_breakdown)) {
                $breakdownSum = collect($booking->pricing_breakdown)
                    ->filter(fn ($v) => is_array($v) && isset($v['price']))
                    ->sum('price');
                if ($breakdownSum > 0 && abs($breakdownSum - (float) $booking->base_price) < 1) {
                    $code = self::ANOMALY_RATE_CHANGED;
                }
            }

            if ($stayType === 'monthly') {
                $room = $booking->room;
                $baseKosPrice = (float) ($room->price_kos ?? $room->price_public ?? 0);
                $kostMonths = max(1, (int) round($nights / 30));

                // Check 1: tier exists and fixed_price was double-multiplied
                $tier = $room->kostPricingTiers()->where('duration_months', $kostMonths)->first();
                if ($tier && $tier->discount_type === 'fixed') {
                    $doubleCounted = (float) $tier->fixed_price * $kostMonths;
                    if (abs($doubleCounted - (float) $booking->base_price) < 1) {
                        $code = self::ANOMALY_KOST_TIER_DOUBLE_COUNT;
                    }
                }

                // Check 2: no tier but base_price = price_kos × months² (double-count without tier)
                if ($code !== self::ANOMALY_KOST_TIER_DOUBLE_COUNT && $baseKosPrice > 0) {
                    $doubleCountedNoTier = $baseKosPrice * $kostMonths * $kostMonths;
                    if (abs($doubleCountedNoTier - (float) $booking->base_price) < 1) {
                        $code = self::ANOMALY_KOST_TIER_DOUBLE_COUNT;
                    }
                }

                // Check 3: stored breakdown has tier info
                if ($code !== self::ANOMALY_KOST_TIER_DOUBLE_COUNT && is_array($booking->pricing_breakdown)) {
                    if (!empty($booking->pricing_breakdown['kost_tier_applied'])) {
                        $code = self::ANOMALY_KOST_TIER_DOUBLE_COUNT;
                    }
                }
            }

            $anomalies[] = [
                'field' => 'base_price',
                'code' => $code,
                'label' => 'Room Subtotal',
                'expected' => $expectedBasePrice,
                'actual' => (float) $booking->base_price,
                'diff' => (float) $booking->base_price - $expectedBasePrice,
            ];
        }

        // === CHECK 5: Validate discount ===
        $expectedDiscount = $this->calculateExpectedDiscount($booking, $expectedBasePrice, $nights, $stayType);

        if (abs($expectedDiscount - (float) $booking->discount_amount) > 1) {
            $anomalies[] = [
                'field' => 'discount_amount',
                'code' => self::ANOMALY_DISCOUNT_MISMATCH,
                'label' => 'Discount',
                'expected' => $expectedDiscount,
                'actual' => (float) $booking->discount_amount,
                'diff' => (float) $booking->discount_amount - $expectedDiscount,
            ];
        }

        // === CHECK 6: Validate total_price formula ===
        $breakfastTotal = is_array($booking->pricing_breakdown)
            ? (float) ($booking->pricing_breakdown['breakfast_total'] ?? 0)
            : 0;
        $expectedTotal = max(0, (float) $booking->base_price - (float) $booking->discount_amount)
            + ($booking->deposit_amount ?? 0)
            + $breakfastTotal;

        if (abs($expectedTotal - (float) $booking->total_price) > 1) {
            $anomalies[] = [
                'field' => 'total_price',
                'code' => self::ANOMALY_TOTAL_FORMULA_WRONG,
                'label' => 'Total Price',
                'expected' => $expectedTotal,
                'actual' => (float) $booking->total_price,
                'diff' => (float) $booking->total_price - $expectedTotal,
            ];
        }

        // === CHECK 7: Overpayment ===
        $totalPaid = $booking->transactions
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');
        $grandTotal = (float) $booking->total_price + $totalPaid; // simplified
        $actualGrandTotal = BookingPriceService::grandTotal($booking);
        $actualRemaining = BookingPriceService::remainingBalance($booking);

        if ($totalPaid > $actualGrandTotal + 1 && $actualGrandTotal > 0) {
            $anomalies[] = [
                'field' => 'payment',
                'code' => self::ANOMALY_OVERPAYMENT,
                'label' => 'Payment',
                'expected' => $actualGrandTotal,
                'actual' => $totalPaid,
                'diff' => $totalPaid - $actualGrandTotal,
            ];
        }

        // === CHECK 8: Deposit consistency ===
        $depositTxTotal = $booking->transactions
            ->where('type', 'charge')
            ->where('is_deposit', true)
            ->sum('amount');
        if (abs($depositTxTotal - ($booking->deposit_amount ?? 0)) > 1 && ($booking->deposit_amount ?? 0) > 0) {
            $anomalies[] = [
                'field' => 'deposit_amount',
                'code' => self::ANOMALY_DEPOSIT_MISMATCH,
                'label' => 'Security Deposit',
                'expected' => $depositTxTotal,
                'actual' => (float) $booking->deposit_amount,
                'diff' => (float) $booking->deposit_amount - $depositTxTotal,
            ];
        }

        // === CHECK 9: Kost months display vs calculation ===
        if ($stayType === 'monthly') {
            $calculatedMonths = max(1, (int) round($nights / 30));
            $displayedMonths = $calculatedMonths; // from show blade
            // Check if pricing_breakdown stored different months
            if (is_array($booking->pricing_breakdown) && isset($booking->pricing_breakdown['kost_tier_applied'])) {
                $storedRate = $booking->pricing_breakdown['kost_effective_rate'] ?? 0;
                $storedOriginal = $booking->pricing_breakdown['kost_original_rate'] ?? 0;
                // If stored rate * months ≈ base_price, check if rate itself is wrong
                if ($storedRate > 0 && $storedOriginal > 0) {
                    $expectedFromRate = $storedOriginal * $calculatedMonths;
                    if (abs($expectedFromRate - (float) $booking->base_price) > 1) {
                        // Rate was probably already the total, not monthly
                    }
                }
            }
        }

        $catalog = self::anomalyCatalog();
        foreach ($anomalies as &$a) {
            $a['severity'] = $catalog[$a['code']]['severity'] ?? 'medium';
            $a['description'] = $catalog[$a['code']]['description'] ?? '';
        }

        return [
            'booking_id' => $booking->id,
            'nights' => $nights,
            'stay_type' => $stayType,
            'room_number' => $booking->room?->room_number,
            'room_rate' => $this->getMonthlyRate($booking),
            'anomalies' => $anomalies,
            'anomaly_count' => count($anomalies),
            'has_critical' => collect($anomalies)->contains('severity', 'critical'),
            'expected' => [
                'base_price' => $expectedBasePrice,
                'discount_amount' => $expectedDiscount,
                'total_price' => max(0, $expectedBasePrice - $expectedDiscount) + ($booking->deposit_amount ?? 0) + $breakfastTotal,
            ],
            'actual' => [
                'base_price' => (float) $booking->base_price,
                'discount_amount' => (float) $booking->discount_amount,
                'total_price' => (float) $booking->total_price,
            ],
            'has_anomaly' => count($anomalies) > 0,
        ];
    }

    /**
     * Recalculate the expected base_price using correct logic.
     */
    private function calculateExpectedBasePrice(Booking $booking, int $nights, string $stayType): float
    {
        $room = $booking->room;
        if (!$room) return (float) $booking->base_price;

        if ($stayType === 'monthly') {
            $baseKosPrice = (float) ($room->price_kos ?? $room->price_public ?? 0);
            $kostMonths = max(1, (int) round($nights / 30));

            $tier = $room->kostPricingTiers()
                ->where('duration_months', $kostMonths)
                ->first();

            if ($tier) {
                if ($tier->discount_type === 'fixed') {
                    // fixed_price = total for the entire duration, NOT monthly
                    return (float) $tier->fixed_price;
                } else {
                    // percentage discount on monthly rate, then multiply by months
                    $effectiveRate = $baseKosPrice * (1 - $tier->percentage_value / 100);
                    return $effectiveRate * $kostMonths;
                }
            }

            return $baseKosPrice * $kostMonths;
        }

        if ($stayType === 'yearly') {
            $yearlyPrice = (float) ($room->yearly_price ?? $room->roomType->yearly_price ?? 0);
            if ($yearlyPrice <= 0) {
                $yearlyPrice = (float) ($room->roomType->base_price * 360);
            }
            $years = max(1, (int) ceil($nights / 360));
            return $yearlyPrice * $years;
        }

        // Daily: expected = nightly rate for the tier that was actually applied
        // (public/sales/high_season) × nights. Using price_public unconditionally
        // falsely flagged every SALES booking (charged price_sales) as a mismatch.
        $nightlyRate = $this->nightlyRateForTier($booking, $room);
        return $nightlyRate * $nights;
    }

    /**
     * Resolve the room's nightly rate matching the pricing tier stored on the
     * booking (pricing_breakdown['tier_applied']). Falls back to public.
     */
    private function nightlyRateForTier(Booking $booking, $room): float
    {
        $tier = is_array($booking->pricing_breakdown)
            ? ($booking->pricing_breakdown['tier_applied'] ?? 'public')
            : 'public';

        $fallback = (float) ($room->price_public ?? $room->roomType->base_price ?? 0);

        return match ($tier) {
            'sales' => (float) ($room->price_sales ?: $fallback),
            'high_season' => (float) ($room->price_high_season ?: $fallback),
            default => $fallback,
        };
    }

    /**
     * Calculate expected discount.
     */
    private function calculateExpectedDiscount(Booking $booking, float $expectedBasePrice, int $nights, string $stayType): float
    {
        $room = $booking->room;
        if (!$room) return (float) $booking->discount_amount;

        if ($booking->voucher_code) {
            return (float) $booking->discount_amount;
        }

        if ($stayType === 'monthly') {
            $baseKosPrice = (float) ($room->price_kos ?? $room->price_public ?? 0);
            $kostMonths = max(1, (int) round($nights / 30));

            $tier = $room->kostPricingTiers()
                ->where('duration_months', $kostMonths)
                ->first();

            if ($tier) {
                if ($tier->discount_type === 'fixed') {
                    $normalTotal = $baseKosPrice * $kostMonths;
                    return max(0, $normalTotal - (float) $tier->fixed_price);
                } else {
                    $discount = $baseKosPrice * ($tier->percentage_value / 100) * $kostMonths;
                    return $discount;
                }
            }
        }

        return (float) $booking->discount_amount;
    }

    /**
     * Get the room's monthly rate for display.
     */
    private function getMonthlyRate(Booking $booking): float
    {
        $room = $booking->room;
        if (!$room) return 0;

        if (($booking->stay_type ?? 'daily') === 'monthly') {
            return (float) ($room->price_kos ?? $room->price_public ?? 0);
        }
        return (float) ($room->price_public ?? $room->roomType->base_price ?? 0);
    }

    /**
     * Apply the audit fix — recalculate and update the booking.
     */
    public function applyFix(Booking $booking): Booking
    {
        if ($booking->status === 'checked_out') {
            throw new \Exception('Booking sudah checked out — tidak bisa auto-fix harga, perlu koreksi manual.');
        }

        $audit = $this->audit($booking);

        if (!$audit['has_critical']) {
            return $booking;
        }

        $expected = $audit['expected'];
        $breakfastTotal = is_array($booking->pricing_breakdown)
            ? (float) ($booking->pricing_breakdown['breakfast_total'] ?? 0)
            : 0;

        $newTotal = max(0, $expected['base_price'] - $expected['discount_amount'])
            + ($booking->deposit_amount ?? 0)
            + $breakfastTotal;

        $totalPaid = \App\Models\Transaction::where('booking_id', $booking->id)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        $newPaymentStatus = $totalPaid >= $newTotal ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

        $booking->update([
            'base_price' => round($expected['base_price'], 2),
            'discount_amount' => round($expected['discount_amount'], 2),
            'total_price' => round($newTotal, 2),
            'payment_status' => $newPaymentStatus,
            'notes' => ($booking->notes ?? '') . ' [Pricing audit fix applied ' . now()->format('Y-m-d H:i') . ']',
        ]);

        \App\Models\Transaction::where('booking_id', $booking->id)
            ->where('reference_id', 'BOOK-' . $booking->id)
            ->update([
                'amount' => round($newTotal, 2),
            ]);

        $booking->refresh();
        return $booking;
    }
}
