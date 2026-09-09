<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class LeaveBalance extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'employee_id',
        'leave_type_id',
        'year',
        'allocated',
        'used',
        'remaining',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated' => 'integer',
        'used' => 'integer',
        'remaining' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
