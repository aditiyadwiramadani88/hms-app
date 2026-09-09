<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Employee extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'name',
        'code',
        'position',
        'phone',
        'address',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
