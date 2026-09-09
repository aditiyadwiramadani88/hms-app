<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    use BelongsToHotel;

    protected $fillable = ['name', 'color', 'icon', 'is_available', 'display_order'];

    protected $casts = ['is_available' => 'boolean', 'display_order' => 'integer'];
}
