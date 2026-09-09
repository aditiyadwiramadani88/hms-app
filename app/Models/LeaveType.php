<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class LeaveType extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'is_paid',
        'max_days_per_year',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'max_days_per_year' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
