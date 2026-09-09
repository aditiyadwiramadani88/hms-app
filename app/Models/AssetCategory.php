<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class AssetCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
