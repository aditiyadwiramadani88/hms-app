<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryWarehouseStock extends Model
{
    protected $fillable = [
        'inventory_id',
        'warehouse_id',
        'stock',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
