<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class ShiftSwapRequest extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'requester_id',
        'target_id',
        'requester_schedule_id',
        'target_schedule_id',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function requesterSchedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'requester_schedule_id');
    }

    public function targetSchedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'target_schedule_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
