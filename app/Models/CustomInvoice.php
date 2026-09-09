<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToHotel;

class CustomInvoice extends Model
{
    use BelongsToHotel, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'invoice_number',
        'guest_id',
        'booking_id',
        'source',
        'room_name',
        'check_in',
        'check_out',
        'nights',
        'adults',
        'children',
        'sell_price',
        'agent_commission',
        'notes',
        'status',
        'payment_method',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'sell_price' => 'decimal:2',
        'agent_commission' => 'decimal:2',
        'nights' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateInvoiceNumber(int $hotelId): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-CUSTOM-{$date}-";

        $last = static::withTrashed()
            ->where('hotel_id', $hotelId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'draft' => 'bg-secondary',
            'sent' => 'bg-info',
            'paid' => 'bg-success',
            'cancelled' => 'bg-danger',
        ];
        $class = $badges[$this->status] ?? 'bg-secondary';
        return "<span class='badge {$class}'>{$this->status}</span>";
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}
