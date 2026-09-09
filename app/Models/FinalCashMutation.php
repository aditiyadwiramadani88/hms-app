<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class FinalCashMutation extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'bank_account_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'reference_id');
    }
}
