<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomTransferService
{
    /**
     * Preview the price calculation for a room transfer (no DB changes).
     */
    public function previewTransfer(Booking $booking, Room $newRoom, string $tier, ?Carbon $newCheckOut = null): array
    {
        $now = Carbon::now()->startOfDay();
        $checkIn = ($booking->actual_check_in ?? $booking->check_in)->copy()->startOfDay();
        $checkOut = ($newCheckOut ? $newCheckOut->copy()->startOfDay() : $booking->check_out->copy()->startOfDay());

        // Total nights must match the updated duration
        $totalNights = max(1, (int) $checkIn->diffInDays($checkOut, false));
        $nightsUsed = max(0, min($totalNights, (int) $checkIn->diffInDays($now, false)));
        $remainingNights = max(0, $totalNights - $nightsUsed);

        // Old room price per night from pricing_breakdown
        $breakdown = $booking->pricing_breakdown;
        $oldPricePerNight = 0;
        if (is_array($breakdown) && count($breakdown) > 0) {
            $oldPricePerNight = (float) ($breakdown[0]['price'] ?? 0);
        }
        if ($oldPricePerNight <= 0) {
            $origTotalNights = max(1, (int) $booking->check_in->diffInDays($booking->check_out, false));
            $oldPricePerNight = (float) ($booking->base_price / $origTotalNights);
        }

        // New room price per night based on tier
        $newPricePerNight = match ($tier) {
            'sales' => (float) ($newRoom->price_sales ?? $newRoom->price_public ?? 0),
            'high_season' => (float) ($newRoom->price_high_season ?? $newRoom->price_public ?? 0),
            default => (float) ($newRoom->price_public ?? 0),
        };

        // Calculate costs
        $costOldRoom = $nightsUsed * $oldPricePerNight;
        $costNewRoom = $remainingNights * $newPricePerNight;

        // Breakfast calculation
        $breakfastTotal = 0;
        if ($booking->include_breakfast) {
            $pax = 1; // Fixed 1 pack per room per night

            // Old breakfast for nights used
            $oldBreakfastPrice = $this->getBreakfastPrice($booking->room, $this->getOldTier($booking));
            $breakfastOld = $oldBreakfastPrice * $nightsUsed;

            // New breakfast for remaining nights
            $newBreakfastPrice = $this->getBreakfastPrice($newRoom, $tier);
            $breakfastNew = $newBreakfastPrice * $remainingNights;

            $breakfastTotal = $breakfastOld + $breakfastNew;
        }

        $deposit = (float) ($booking->deposit_amount ?? 0);
        $discount = (float) ($booking->discount_amount ?? 0);

        $newTotal = $costOldRoom + $costNewRoom + $breakfastTotal + $deposit - $discount;
        $oldTotal = (float) $booking->total_price;
        
        // Price difference reflects the true difference between the new stay total and previous total
        $priceDifference = $newTotal - $oldTotal;

        return [
            'nights_used' => $nightsUsed,
            'remaining_nights' => $remainingNights,
            'old_price_per_night' => $oldPricePerNight,
            'new_price_per_night' => $newPricePerNight,
            'cost_old_room' => $costOldRoom,
            'cost_new_room' => $costNewRoom,
            'breakfast_total' => $breakfastTotal,
            'deposit' => $deposit,
            'discount' => $discount,
            'old_total' => $oldTotal,
            'new_total' => $newTotal,
            'price_difference' => $priceDifference,
        ];
    }

    /**
     * Execute the room transfer.
     */
    public function transfer(Booking $booking, Room $newRoom, string $tier, string $reason, ?string $notes = null, bool $chargeDifference = false, ?Carbon $newCheckOut = null): RoomTransfer
    {
        // Validations
        if ($booking->status !== 'checked_in') {
            throw new \Exception('Perpindahan kamar hanya dapat dilakukan untuk booking yang sudah check-in.');
        }

        if (in_array($newRoom->status, ['In-House', 'Checkin', 'Occupied', 'Maintenance', 'maintenance', 'Out of Order', 'out_of_order', 'Closed', 'closed'])) {
            throw new \Exception('Kamar tujuan tidak tersedia atau sedang ditempati.');
        }

        if ($newRoom->id === $booking->room_id) {
            throw new \Exception('Kamar tujuan harus berbeda dari kamar saat ini.');
        }

        return DB::transaction(function () use ($booking, $newRoom, $tier, $reason, $notes, $chargeDifference, $newCheckOut) {
            $oldRoom = $booking->room;

            $preview = $this->previewTransfer($booking, $newRoom, $tier, $newCheckOut);

            // Update booking pricing
            $isDowngrade = $preview['price_difference'] < 0;
            $hasExtension = $newCheckOut !== null && $newCheckOut->gt($booking->check_out);
            $shouldUpdatePricing = $chargeDifference || $isDowngrade || $hasExtension || ($preview['price_difference'] != 0);

            $newBreakdown = $this->buildNewBreakdown($booking, $oldRoom, $newRoom, $tier, $preview, $shouldUpdatePricing);
            $newBasePrice = $shouldUpdatePricing
                ? ($preview['cost_old_room'] + $preview['cost_new_room'])
                : $booking->base_price;
            $newTotalPrice = $shouldUpdatePricing
                ? $preview['new_total']
                : $booking->total_price;
            
            $updateData = [
                'room_id' => $newRoom->id,
                'base_price' => $newBasePrice,
                'total_price' => $newTotalPrice,
                'pricing_breakdown' => $newBreakdown,
            ];

            if ($newCheckOut) {
                $updateData['check_out'] = $newCheckOut;
            }

            $booking->update($updateData);

            // Update BOOK-xxx transaction to match new total so remaining balance is updated
            Transaction::where('booking_id', $booking->id)
                ->where('reference_id', 'BOOK-' . $booking->id)
                ->update(['amount' => $newTotalPrice]);

            // Update room statuses
            $oldRoom->update(['status' => 'dirty']);
            $newRoom->update(['status' => 'In-House']);

            // Update payment status if price increased and was paid
            if ($newTotalPrice > (float) $preview['old_total'] && $booking->payment_status === 'paid') {
                $booking->update(['payment_status' => 'partial']);
            }

            // Create transfer record
            $transfer = RoomTransfer::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $booking->id,
                'from_room_id' => $oldRoom->id,
                'to_room_id' => $newRoom->id,
                'transferred_at' => now(),
                'reason' => $reason,
                'transferred_by' => Auth::id(),
                'nights_in_old_room' => $preview['nights_used'],
                'old_room_price_per_night' => $preview['old_price_per_night'],
                'new_room_price_per_night' => $preview['new_price_per_night'],
                'price_difference' => $preview['price_difference'],
                'new_tier_applied' => $tier,
                'notes' => $notes,
            ]);

            // Audit log
            AuditLog::log(
                'booking.room_transferred',
                "Room transfer: Booking #{$booking->id} moved from room {$oldRoom->room_number} to {$newRoom->room_number}. Reason: {$reason}. Price diff: Rp " . number_format($preview['price_difference'], 0, ',', '.'),
                $booking
            );

            return $transfer;
        });
    }

    private function getBreakfastPrice(Room $room, string $tier): float
    {
        return match ($tier) {
            'sales' => (float) ($room->price_breakfast_sales ?? 0),
            'high_season' => (float) ($room->price_breakfast_high_season ?? 0),
            default => (float) ($room->price_breakfast_public ?? 0),
        };
    }

    private function getOldTier(Booking $booking): string
    {
        $breakdown = $booking->pricing_breakdown;
        if (is_array($breakdown) && isset($breakdown['tier_applied'])) {
            return $breakdown['tier_applied'];
        }
        if (is_array($breakdown) && isset($breakdown[0]['tier'])) {
            return $breakdown[0]['tier'];
        }
        return 'public';
    }

    private function buildNewBreakdown(Booking $booking, Room $oldRoom, Room $newRoom, string $tier, array $preview, bool $chargeDifference = true): array
    {
        $oldBreakdown = $booking->pricing_breakdown;
        $breakdown = [];

        // Old room nights
        for ($i = 0; $i < $preview['nights_used']; $i++) {
            $breakdown[] = [
                'night' => $i + 1,
                'price' => $preview['old_price_per_night'],
                'room' => $oldRoom->id,
                'tier' => $this->getOldTier($booking),
            ];
        }

        // New room nights — always use new price for downgrades (cheaper room)
        $isDowngrade = $preview['price_difference'] < 0;
        $applyNewPrice = $chargeDifference || $isDowngrade;
        $appliedNewPrice = $applyNewPrice ? $preview['new_price_per_night'] : $preview['old_price_per_night'];

        for ($i = 0; $i < $preview['remaining_nights']; $i++) {
            $breakdown[] = [
                'night' => $preview['nights_used'] + $i + 1,
                'price' => $appliedNewPrice,
                'room' => $newRoom->id,
                'tier' => $applyNewPrice ? $tier : $this->getOldTier($booking),
            ];
        }

        // Preserve metadata from old breakdown
        if (is_array($oldBreakdown)) {
            $breakdown['tier_applied'] = $tier;
            $breakdown['breakfast_total'] = $preview['breakfast_total'];
            if (isset($oldBreakdown['periods'])) {
                $breakdown['periods'] = $oldBreakdown['periods'];
            }
            foreach (['kost_tier_applied', 'kost_original_rate', 'kost_effective_rate'] as $k) {
                if (isset($oldBreakdown[$k])) {
                    $breakdown[$k] = $oldBreakdown[$k];
                }
            }
        }

        return $breakdown;
    }
}
