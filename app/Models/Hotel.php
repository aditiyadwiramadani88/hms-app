<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'tax_percentage',
        'room_deposit_amount',
        'daily_report_cutoff',
        'logo_path',
        'logo_light_path',
        'favicon_path',
        'login_bg_path',
        'navbar_color',
        'sidebar_color',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'hotel_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
