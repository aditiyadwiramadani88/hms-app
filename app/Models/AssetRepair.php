<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class AssetRepair extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'asset_id',
        'hotel_id',
        'repair_date',
        'repair_type',
        'technician',
        'problem_description',
        'action_taken',
        'result',
        'cost',
        'status',
        'warranty_info',
        'reported_by',
        'approved_by',
    ];

    protected $casts = [
        'repair_date' => 'date',
        'cost' => 'decimal:2',
    ];

    const TYPE_LIGHT = 'Perbaikan Ringan';
    const TYPE_HEAVY = 'Perbaikan Berat';
    const TYPE_SERVICE = 'Servis Rutin';
    const TYPE_PART_REPLACEMENT = 'Penggantian Part';

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public static function repairTypes(): array
    {
        return [
            self::TYPE_LIGHT,
            self::TYPE_HEAVY,
            self::TYPE_SERVICE,
            self::TYPE_PART_REPLACEMENT,
        ];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
