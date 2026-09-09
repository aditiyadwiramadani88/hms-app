<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class BankAccount extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'account_number',
        'account_holder',
        'balance',
        'available_balance',
        'is_active',
        'is_cash',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_cash' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function finalCashMutations()
    {
        return $this->hasMany(FinalCashMutation::class);
    }

    public function isCashAccount(): bool
    {
        return $this->is_cash || str_contains(strtolower($this->name), 'tunai');
    }
}
