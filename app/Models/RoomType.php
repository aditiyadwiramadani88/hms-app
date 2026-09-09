<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class RoomType extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'name',
        'description',
        'base_price',
        'monthly_price',
        'yearly_price',
        'image',
        'max_guests',
        'size_sqm',
        'amenities',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'amenities' => 'array',
        'is_active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
