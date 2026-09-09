<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Shift extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'code',
        'color',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'start_time_2',
        'end_time_2',
        'is_off',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_off' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
