<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProductAddonItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'addon_group_id',
        'name',
        'price',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function group(): BelongsTo
    {
        return $this->belongsTo(TenantProductAddonGroup::class, 'addon_group_id');
    }
}
