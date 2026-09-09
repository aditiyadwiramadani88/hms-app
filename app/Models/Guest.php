<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToHotel;

class Guest extends Authenticatable
{
    use BelongsToHotel, HasFactory, Notifiable;

    protected $fillable = [
        'guest_category_id',
        'customer_type_id',
        'legacy_customer_code',
        'name',
        'email',
        'phone',
        'password',
        'company_name',
        'vehicle_number',
        'legacy_stay_type_days',
        'id_number',
        'id_card_photo',
        'reference_source',
        'identity_type',
        'citizenship_code',
        'address',
        'nationality',
        'date_of_birth',
        'gender',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'legacy_stay_type_days' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    public function guestCategory()
    {
        return $this->belongsTo(GuestCategory::class);
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(GuestEmergencyContact::class);
    }
}
