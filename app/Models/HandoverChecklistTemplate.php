<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class HandoverChecklistTemplate extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'name', 'description', 'is_required', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
