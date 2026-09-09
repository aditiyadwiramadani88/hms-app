<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Voucher extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_booking_amount',
        'max_discount',
        'usage_limit',
        'usage_count',
        'valid_from',
        'valid_until',
        'is_active',
        'applicable_room_types',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'applicable_room_types' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereDate('valid_until', '>=', Carbon::today());
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->valid_from && $today->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $today->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
