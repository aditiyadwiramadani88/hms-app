<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class VehicleRental extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'booking_id',
        'renter_name',
        'company_name',
        'nik',
        'sim_number',
        'vehicle_plate_number',
        'start_km',
        'end_km',
        'rental_days',
        'daily_price',
        'deposit_amount',
        'status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
