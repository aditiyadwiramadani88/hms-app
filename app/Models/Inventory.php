<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'name',
        'image',
        'category_id',
        'stock',
        'min_stock',
        'unit',
        'price_per_unit',
        'purchase_price',
        'is_active',
        'is_refundable',
        'deposit_amount',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'price_per_unit' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_refundable' => 'boolean',
        'deposit_amount' => 'decimal:2',
    ];

    public function inventoryCategory()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'inventory_warehouse_stocks')
            ->withPivot('stock')
            ->withTimestamps();
    }

    public function warehouseStocks()
    {
        return $this->hasMany(InventoryWarehouseStock::class);
    }
}
