<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class RoomTransfer extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'booking_id',
        'from_room_id',
        'to_room_id',
        'transferred_at',
        'reason',
        'transferred_by',
        'nights_in_old_room',
        'old_room_price_per_night',
        'new_room_price_per_night',
        'price_difference',
        'new_tier_applied',
        'notes',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'old_room_price_per_night' => 'decimal:2',
        'new_room_price_per_night' => 'decimal:2',
        'price_difference' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function fromRoom()
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
