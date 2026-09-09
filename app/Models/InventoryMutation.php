<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class InventoryMutation extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'inventory_id',
        'user_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
