<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Booking extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'hotel_id',
        'guest_id',
        'room_id',
        'is_custom',
        'custom_room_name',
        'user_id',
        'check_in',
        'check_out',
        'actual_check_in',
        'actual_check_out',
        'cancelled_at',
        'cancelled_reason',
        'cancelled_photo',
        'adults',
        'children',
        'base_price',
        'discount_amount',
        'tax_amount',
        'total_price',
        'status',
        'payment_order_id',
        'payment_status',
        'notes',
        'include_breakfast',
        'voucher_code',
        'booking_source_id',
        'source',
        'guest_type',
        'stay_type',
        'deposit_amount',
        'tax_exempt',
        'pricing_breakdown',
        'check_in_time',
        'check_out_time',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'cancelled_at' => 'datetime',
        'base_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'include_breakfast' => 'boolean',
        'pricing_breakdown' => 'array',
    ];

    public function bookingSource()
    {
        return $this->belongsTo(BookingSource::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function roomTransfers()
    {
        return $this->hasMany(RoomTransfer::class);
    }

    public function editRequests()
    {
        return $this->hasMany(BookingEditRequest::class);
    }

    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->whereNotNull('actual_check_in');
    }

    /**
     * Bookings that were physically occupying a room on the given date,
     * based on actual check-in/check-out timestamps (not booking status
     * or booked dates) — matches Room Activity report / front-desk truth.
     */
    public function scopeOccupiedOn(Builder $query, $date): Builder
    {
        $date = Carbon::parse($date);

        return $query->whereNotIn('bookings.status', ['cancelled', 'no_show'])
            ->whereNotNull('bookings.actual_check_in')
            ->where('bookings.actual_check_in', '<=', $date->copy()->endOfDay())
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('bookings.actual_check_out')
                    ->orWhere('bookings.actual_check_out', '>', $date->copy()->startOfDay());
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getCheckedInByAttribute()
    {
        $log = \App\Models\AuditLog::where('model_type', static::class)
            ->where('model_id', $this->id)
            ->where('action', 'booking.checked_in')
            ->with('user')
            ->latest()
            ->first();
        return $log ? ($log->user->name ?? '-') : '-';
    }
}
