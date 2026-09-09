<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'owner_name',
        'phone',
        'email',
        'location_description',
        'rent_amount',
        'rent_due_day',
        'is_active',
        'contract_start',
        'contract_end',
        'notes',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'rent_due_day' => 'integer',
        'is_active' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];

    // Relationships
    public function products(): HasMany
    {
        return $this->hasMany(TenantProduct::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TenantTransaction::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(TenantBilling::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function currentMonthRevenue(): float
    {
        return (float) $this->transactions()
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('total_amount');
    }

    public function hasOverdueBilling(): bool
    {
        return $this->billings()
            ->where('status', 'overdue')
            ->exists();
    }

    public function isContractExpired(): bool
    {
        if (!$this->contract_end) {
            return false;
        }
        return $this->contract_end->isPast();
    }
}
