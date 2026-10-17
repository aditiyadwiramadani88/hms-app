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
        'returned_quantity',
        'returned_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'returned_at' => 'datetime',
        'price_per_unit' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
