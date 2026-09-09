<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class LeaveRequest extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'total_days' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
