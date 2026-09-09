<?php

namespace Database\Seeders;

use App\Models\ScheduleLocation;
use Illuminate\Database\Seeder;

class ScheduleLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'HS 1', 'code' => 'HS1'],
            ['name' => 'HS 2', 'code' => 'HS2'],
            ['name' => 'Gedung A', 'code' => 'GA'],
            ['name' => 'Gedung B', 'code' => 'GB'],
        ];

        foreach ($locations as $loc) {
            ScheduleLocation::firstOrCreate(
                ['code' => $loc['code']],
                $loc
            );
        }
    }
}
