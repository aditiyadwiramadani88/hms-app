<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingEditRequest extends Model
{
    protected $fillable = [
        'hotel_id',
        'booking_id',
        'requested_by',
        'change_type',
        'reason',
        'proposed_changes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'expires_at',
    ];

    protected $casts = [
        'proposed_changes' => 'array',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
