<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportApproval extends Model
{
    protected $fillable = [
        'hotel_id',
        'report_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
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

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
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
