<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Purchase extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'supplier_id',
        'user_id',
        'purchase_number',
        'total_amount',
        'status',
        'purchase_date',
        'warehouse_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
