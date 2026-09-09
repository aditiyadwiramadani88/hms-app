<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class RoomKostPricingTier extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_id',
        'duration_months',
        'discount_type',
        'fixed_price',
        'percentage_value',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'fixed_price' => 'decimal:2',
        'percentage_value' => 'decimal:2',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Calculate effective monthly rate based on room's base price.
     * For 'fixed' type, returns the total price for the entire duration (NOT monthly).
     * For 'percentage' type, returns the discounted monthly rate.
     */
    public function getEffectiveRate(float $basePrice): float
    {
        if ($this->discount_type === 'fixed') {
            return (float) $this->fixed_price; // total for duration
        }

        return $basePrice * (1 - $this->percentage_value / 100); // monthly rate
    }
}
