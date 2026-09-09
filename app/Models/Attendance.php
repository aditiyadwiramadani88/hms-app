<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Attendance extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'employee_id',
        'employee_schedule_id',
        'shift_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'break_start_time',
        'break_end_time',
        'check_in_photo',
        'check_out_photo',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_location_id',
        'check_out_location_id',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function schedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'employee_schedule_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function checkInLocation()
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_in_location_id');
    }

    public function checkOutLocation()
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_out_location_id');
    }
}
