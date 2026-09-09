<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class GuestVehicle extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'guest_id',
        'plate_number',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_color',
        'owner_name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function vehicleLogs()
    {
        return $this->hasMany(VehicleLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
