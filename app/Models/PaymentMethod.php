<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class PaymentMethod extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'code', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
