<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TenantTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'transaction_number',
        'transaction_date',
        'total_amount',
        'items_count',
        'payment_method',
        'status',
        'payment_proof',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items_count' => 'integer',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenantTransactionItem::class);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber($transaction->tenant_id);
            }
        });
    }

    // Helpers
    private static function generateTransactionNumber(int $tenantId): string
    {
        $date = now()->format('Ymd');
        $prefix = 'TNT';

        $latest = static::where('tenant_id', $tenantId)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $latest ? ((int) substr($latest->transaction_number, -4)) + 1 : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
