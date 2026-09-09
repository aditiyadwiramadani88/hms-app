<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class HotelPage extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'slug',
        'title',
        'content',
        'meta_description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
