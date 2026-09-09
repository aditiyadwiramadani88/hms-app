<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class ShiftHandover extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'handover_date', 'outgoing_shift_id', 'incoming_shift_id',
        'outgoing_employee_id', 'incoming_employee_id', 'financial_summary',
        'transaction_details', 'cash_amount', 'cash_discrepancy', 'discrepancy_notes',
        'notes', 'incoming_notes', 'status', 'submitted_at', 'confirmed_at',
    ];

    protected $casts = [
        'financial_summary' => 'array',
        'transaction_details' => 'array',
        'cash_amount' => 'decimal:2',
        'cash_discrepancy' => 'boolean',
        'handover_date' => 'date',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function outgoingShift()
    {
        return $this->belongsTo(Shift::class, 'outgoing_shift_id');
    }

    public function incomingShift()
    {
        return $this->belongsTo(Shift::class, 'incoming_shift_id');
    }

    public function outgoingEmployee()
    {
        return $this->belongsTo(User::class, 'outgoing_employee_id');
    }

    public function incomingEmployee()
    {
        return $this->belongsTo(User::class, 'incoming_employee_id');
    }

    public function items()
    {
        return $this->hasMany(ShiftHandoverItem::class);
    }

    public function scopePending($q)
    {
        return $q->where('status', 'submitted');
    }

    public function scopeForShift($q, $shiftId, $date)
    {
        return $q->where('incoming_shift_id', $shiftId)->where('handover_date', $date);
    }

    public function scopeDisputed($q)
    {
        return $q->where('status', 'disputed');
    }
}
