<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class VehicleLog extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'guest_vehicle_id',
        'booking_id',
        'driver_name',
        'plate_number',
        'vehicle_type',
        'vehicle_color',
        'photo_in',
        'photo_out',
        'purpose',
        'destination_room',
        'time_in',
        'time_out',
        'status',
        'security_in_id',
        'security_out_id',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    protected function photoIn(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (empty($value)) return null;
                if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [$value];
                }
                return [$value];
            },
            set: fn ($value) => is_array($value) ? json_encode(array_values($value)) : $value,
        );
    }

    protected function photoOut(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (empty($value)) return null;
                if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [$value];
                }
                return [$value];
            },
            set: fn ($value) => is_array($value) ? json_encode(array_values($value)) : $value,
        );
    }

    public function guestVehicle()
    {
        return $this->belongsTo(GuestVehicle::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function securityIn()
    {
        return $this->belongsTo(User::class, 'security_in_id');
    }

    public function securityOut()
    {
        return $this->belongsTo(User::class, 'security_out_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeStillInside($query)
    {
        return $query->where('status', 'in');
    }
}
