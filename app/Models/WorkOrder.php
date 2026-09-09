<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToHotel;

class WorkOrder extends Model
{
    use BelongsToHotel, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'room_id',
        'assigned_to',
        'type',
        'status',
        'completed_at',
        'bonus_amount',
        'bonus_category',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'bonus_amount' => 'decimal:2',
    ];

    /**
     * Get the room associated with this work order.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the assigned OB/user for this work order.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for pending work orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in_progress work orders.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed work orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Mark the work order as in progress.
     */
    public function markAsInProgress()
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark the work order as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
