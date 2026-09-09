<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\RoomType;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate the effective room price based on dynamic rates.
     *
     * The priority is as follows:
     * 1. Locked/Flash Sale Rate for the specific date.
     * 2. Rate for the guest's specific category on that date.
     * 3. Rate for the general public (no category) on that date.
     * 4. The base price from the room type itself.
     *
     * @param RoomType $roomType The room type being booked.
     * @param Guest|null $guest The guest making the booking.
     * @param Carbon $date The date for which to calculate the price.
     * @return float The calculated price.
     */
    public function calculateRoomPrice(RoomType $roomType, ?Guest $guest, Carbon $date): array
    {
        $dateString = $date->toDateString();

        // 1. Check for a locked "Flash Sale" rate (High Season priority)
        $flashSaleRate = RoomRate::where('room_type_id', $roomType->id)
            ->where('is_locked', true)
            ->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString)
            ->orderBy('price', 'asc') // Get the cheapest flash sale if multiple exist
            ->first();

        if ($flashSaleRate) {
            return ['price' => (float) $flashSaleRate->price, 'tier' => 'high_season'];
        }

        // 2. Check for a rate matching the guest's category (Sales tier)
        if ($guest && $guest->guest_category_id) {
            $categoryRate = RoomRate::where('room_type_id', $roomType->id)
                ->where('guest_category_id', $guest->guest_category_id)
                ->where('start_date', '<=', $dateString)
                ->where('end_date', '>=', $dateString)
                ->first();
            
            if ($categoryRate) {
                return ['price' => (float) $categoryRate->price, 'tier' => 'sales'];
            }
        }

        // 3. Check for a general public rate (no guest category) (Public tier)
        $publicRate = RoomRate::where('room_type_id', $roomType->id)
            ->whereNull('guest_category_id')
            ->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString)
            ->first();

        if ($publicRate) {
            return ['price' => (float) $publicRate->price, 'tier' => 'public'];
        }

        // 4. Fallback to the base price of the room type
        return ['price' => (float) $roomType->base_price, 'tier' => 'public'];
    }

    /**
     * Calculate monthly price for a room type.
     * Uses price_kos from room if available, otherwise falls back to base calculation.
     *
     * @param RoomType $roomType The room type.
     * @param Carbon $checkIn Check-in date.
     * @param Carbon $checkOut Check-out date.
     * @param Room|null $room Specific room (optional, for price_kos).
     * @return float The monthly price.
     */
    public function calculateMonthlyPrice(RoomType $roomType, Carbon $checkIn, Carbon $checkOut, ?Room $room = null): float
    {
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights >= 25 && $room) {
            return (float) ($room->price_kos ?? $room->price_public ?? $roomType->base_price);
        }

        return (float) ($roomType->base_price ?? 0);
    }
}
