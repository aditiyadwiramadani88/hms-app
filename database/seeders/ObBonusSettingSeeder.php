<?php

namespace Database\Seeders;

use App\Models\ObBonusSetting;
use Illuminate\Database\Seeder;

class ObBonusSettingSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'category' => 'online_percentage',
                'value' => 2.5,
                'description' => 'Bonus percentage for online guest bookings',
            ],
            [
                'category' => 'umum_percentage',
                'value' => 2.0,
                'description' => 'Bonus percentage for general (umum) guest bookings',
            ],
            [
                'category' => 'sales_percentage',
                'value' => 1.5,
                'description' => 'Bonus percentage for sales guest bookings',
            ],
            [
                'category' => 'kos_percentage',
                'value' => 1.0,
                'description' => 'Bonus percentage for kos (boarding) guest bookings',
            ],
            [
                'category' => 'fixed_work_order_bonus',
                'value' => 750,
                'description' => 'Fixed bonus amount per completed work order',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            ObBonusSetting::updateOrCreate(
                ['category' => $setting['category']],
                $setting
            );
        }
    }
}
