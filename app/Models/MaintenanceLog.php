<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class MaintenanceLog extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'room_id',
        'reported_by',
        'assigned_to',
        'issue',
        'priority',
        'status',
        'cost',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'priority' => 'string',
        'status' => 'string',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
