<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
