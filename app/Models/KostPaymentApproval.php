<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KostPaymentApproval extends Model
{
    protected $fillable = [
        'transaction_id',
        'booking_id',
        'approved_by',
        'approved_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function approve(int $userId, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        return $this;
    }

    public function reject(int $userId, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        return $this;
    }
}
