<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'transfer_date',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $date = now()->format('Ymd');
                $latest = static::whereDate('created_at', now()->toDateString())
                    ->orderBy('id', 'desc')
                    ->first();
                $sequence = $latest ? (int) substr($latest->transfer_number, -4) + 1 : 1;
                $transfer->transfer_number = 'TRF-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
