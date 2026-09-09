<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class TenantProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'photo',
        'description',
        'category',
        'price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function addonGroups(): HasMany
    {
        return $this->hasMany(TenantProductAddonGroup::class, 'tenant_product_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function allAddonGroups(): HasMany
    {
        return $this->hasMany(TenantProductAddonGroup::class, 'tenant_product_id')
            ->orderBy('sort_order');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('stock')->orWhere('stock', '>', 0);
        });
    }

    // Helpers
    public function hasStock(int $quantity = 1): bool
    {
        if ($this->stock === null) {
            return true; // Unlimited stock
        }
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->stock !== null) {
            $this->decrement('stock', $quantity);
        }
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->stock !== null) {
            $this->increment('stock', $quantity);
        }
    }
}
