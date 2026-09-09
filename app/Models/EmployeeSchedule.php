<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class EmployeeSchedule extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'employee_id',
        'schedule_date',
        'shift_id',
        'location',
        'notes',
        'is_overtime',
        'overtime_hours',
        'created_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'decimal:1',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function swapRequestsAsRequester()
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requester_schedule_id');
    }

    public function swapRequestsAsTarget()
    {
        return $this->hasMany(ShiftSwapRequest::class, 'target_schedule_id');
    }
}
