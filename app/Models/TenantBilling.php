<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'billing_period',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'paid_amount',
        'notes',
        'generated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'paid_amount' => 'decimal:2',
    ];

    // Status constants
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Helpers
    public function markAsPaid(float $amount, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'paid_amount' => $amount,
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return $this;
    }

    public function markAsOverdue(): self
    {
        if ($this->status === self::STATUS_UNPAID) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
            ($this->status === self::STATUS_UNPAID && $this->due_date->isPast());
    }
}
