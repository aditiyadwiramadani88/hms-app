<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantProductAddonGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_product_id',
        'name',
        'type',
        'is_required',
        'max_selections',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(TenantProduct::class, 'tenant_product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenantProductAddonItem::class, 'addon_group_id')->orderBy('sort_order');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(TenantProductAddonItem::class, 'addon_group_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
