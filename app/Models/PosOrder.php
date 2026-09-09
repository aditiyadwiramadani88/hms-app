<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class PosOrder extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'order_number',
        'booking_id',
        'room_id',
        'guest_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_method' => 'string',
        'payment_status' => 'string',
        'status' => 'string',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PosOrderItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::nextOrderNumber();
            }
        });
    }

    /**
     * Generate the next POS-{date}-{seq} order number.
     *
     * Two bugs compounded here in production:
     * 1. order_number is unique globally (not per-hotel), but this
     *    hotel-scoped model normally only sees its own rows via
     *    BelongsToHotel — so two different hotels placing their first
     *    order of the day both computed sequence 0001 and collided.
     * 2. The old logic took the single latest row and did
     *    (int) substr($latest->order_number, -4) to get the next sequence.
     *    Whenever that latest row's suffix was non-numeric (e.g. a manual
     *    "POS-...-I2AY" patch used to unblock a stuck order after an
     *    earlier collision), the int cast silently produced 0, so the
     *    "next" number reset to 0001 and collided with the original
     *    0001 all over again — a self-repeating loop visible in
     *    production on 07-17, 07-18 and 08-21.
     *
     * Counting all of today's rows (globally, not just the latest one)
     * sidesteps both: it's immune to non-numeric suffixes, and it's not
     * scoped to the current hotel. lockForUpdate serializes concurrent
     * creates racing for the same count.
     */
    public static function nextOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::withoutGlobalScope('hotel')
            ->where('order_number', 'like', 'POS-' . $date . '-%')
            ->lockForUpdate()
            ->count();

        return 'POS-' . $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
