<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransferItem extends Model
{
    protected $fillable = [
        'warehouse_transfer_id',
        'inventory_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
