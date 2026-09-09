<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class PosOrderItem extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'pos_order_id',
        'inventory_id',
        'item_name',
        'quantity',
        'price_per_unit',
        'cost_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
