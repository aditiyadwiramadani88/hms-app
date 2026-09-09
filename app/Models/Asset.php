<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Asset extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'asset_category_id',
        'name',
        'serial_number',
        'purchase_year',
        'purchase_price',
        'supplier',
        'location_type',
        'room_id',
        'area_name',
        'pic_user_id',
        'pic_role',
        'status',
        'condition_notes',
        'photo',
        'quantity',
        'acquisition_type',
        'acquisition_notes',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function picUser()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function histories()
    {
        return $this->hasMany(AssetHistory::class)->latest();
    }

    public function repairs()
    {
        return $this->hasMany(AssetRepair::class)->latest();
    }

    public function stockMutations()
    {
        return $this->hasMany(AssetStockMutation::class)->latest();
    }

    public function totalRepairCost(): float
    {
        return (float) $this->repairs()->where('status', 'completed')->sum('cost');
    }
}
