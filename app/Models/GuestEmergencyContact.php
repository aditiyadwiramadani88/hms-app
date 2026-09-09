<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class GuestEmergencyContact extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'guest_id',
        'contact_name',
        'phone_number',
        'relationship',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
