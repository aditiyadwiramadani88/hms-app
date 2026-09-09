<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class BookingSource extends Model
{
    use BelongsToHotel;
    protected $fillable = ['hotel_id', 'name', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
