<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'name',
        'legacy_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
