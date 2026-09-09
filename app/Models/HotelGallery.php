<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class HotelGallery extends Model
{
    use BelongsToHotel;

    protected $table = 'hotel_gallery';

    protected $fillable = [
        'hotel_id',
        'title',
        'description',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
