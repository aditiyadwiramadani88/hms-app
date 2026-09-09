<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_transaction_id',
        'tenant_product_id',
        'product_name',
        'quantity',
        'price',
        'subtotal',
        'addons_json',
        'addons_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'addons_total' => 'decimal:2',
    ];

    // Relationships
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TenantTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(TenantProduct::class);
    }

    // Accessors
    public function getAddonsAttribute(): array
    {
        return $this->addons_json ? json_decode($this->addons_json, true) : [];
    }

    public function getLineTotalAttribute(): float
    {
        return (float) $this->subtotal + (float) $this->addons_total;
    }
}
