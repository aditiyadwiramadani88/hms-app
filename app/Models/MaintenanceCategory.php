<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class MaintenanceCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'description', 'is_active', 'sort_order'];

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

    public function records()
    {
        return $this->hasMany(MaintenanceRecord::class, 'category_id');
    }
}
