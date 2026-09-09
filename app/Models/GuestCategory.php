<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class GuestCategory extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'breakfast_price',
        'code',
    ];
}
