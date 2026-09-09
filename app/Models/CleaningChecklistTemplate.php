<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningChecklistTemplate extends Model
{
    use BelongsToHotel, HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function rooms()
    {
        return $this->belongsToMany(
            Room::class,
            'room_checklist_template',
            'checklist_template_id',
            'room_id'
        );
    }
}
