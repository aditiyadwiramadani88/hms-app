<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class ObBonusSetting extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'category',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    /**
     * Get fixed bonus rate for a specific guest/room category.
     */
    public static function getBonusRate(string $categoryKey): float
    {
        $categoryMap = [
            'online' => 'online_rate',
            'umum' => 'umum_rate',
            'sales' => 'sales_rate',
            'kos' => 'kos_rate',
            'pk' => 'pk_rate',
            'kosong' => 'kosong_rate',
        ];

        $defaultRates = [
            'online_rate' => 2310.0,
            'umum_rate' => 2565.0,
            'sales_rate' => 1426.0,
            'kos_rate' => 8914.0,
            'pk_rate' => 750.0,
            'kosong_rate' => 209.0,
        ];

        $settingKey = $categoryMap[$categoryKey] ?? $categoryKey;

        $setting = self::where('category', $settingKey)->first();

        return $setting ? (float) $setting->value : ($defaultRates[$settingKey] ?? 0.0);
    }
}
