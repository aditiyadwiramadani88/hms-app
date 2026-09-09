<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class LegacyCustomer extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'hotel_id',
        'source_file',
        'legacy_customer_code',
        'name',
        'company_name',
        'citizenship_code',
        'address',
        'id_number',
        'phone',
        'legacy_customer_type_code',
        'legacy_stay_type_days',
        'vehicle_number',
        'raw_payload',
        'imported_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'imported_at' => 'datetime',
        'legacy_stay_type_days' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
