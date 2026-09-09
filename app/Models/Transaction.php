<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Transaction extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'booking_id',
        'guest_id',
        'user_id',
        'bank_account_id',
        'category_id',
        'type',
        'amount',
        'tax_amount',
        'payment_method',
        'reference_id',
        'description',
        'status',
        'is_realized',
        'is_markup',
        'is_room_markup',
        'is_deposit',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_realized' => 'boolean',
        'is_markup' => 'boolean',
        'is_room_markup' => 'boolean',
        'is_deposit' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kostApproval()
    {
        return $this->hasOne(KostPaymentApproval::class);
    }
}
