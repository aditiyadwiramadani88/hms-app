<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class RoomRate extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'guest_category_id',
        'name',
        'start_date',
        'end_date',
        'is_locked',
        'price',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function guestCategory()
    {
        return $this->belongsTo(GuestCategory::class);
    }
}
