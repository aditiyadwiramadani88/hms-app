<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['name' => 'Pagi', 'code' => 'P', 'color' => '#28a745', 'start_time' => '06:00', 'end_time' => '14:00', 'is_off' => false, 'sort_order' => 1],
            ['name' => 'Siang', 'code' => 'S', 'color' => '#007bff', 'start_time' => '14:00', 'end_time' => '22:00', 'is_off' => false, 'sort_order' => 2],
            ['name' => 'Malam', 'code' => 'M', 'color' => '#6f42c1', 'start_time' => '22:00', 'end_time' => '06:00', 'is_off' => false, 'sort_order' => 3],
            ['name' => '06-15', 'code' => '0615', 'color' => '#17a2b8', 'start_time' => '06:00', 'end_time' => '15:00', 'is_off' => false, 'sort_order' => 4],
            ['name' => 'Piket', 'code' => 'PK', 'color' => '#ffc107', 'start_time' => '06:00', 'end_time' => '12:00', 'start_time_2' => '18:00', 'end_time_2' => '22:00', 'is_off' => false, 'sort_order' => 5],
            ['name' => 'Terus 1', 'code' => 'T1', 'color' => '#e83e8c', 'start_time' => '08:00', 'end_time' => '19:00', 'is_off' => false, 'sort_order' => 6],
            ['name' => 'Terus 2', 'code' => 'T2', 'color' => '#20c997', 'start_time' => '08:30', 'end_time' => '19:30', 'is_off' => false, 'sort_order' => 7],
            ['name' => 'Security', 'code' => 'SC', 'color' => '#dc3545', 'start_time' => '06:00', 'end_time' => '06:00', 'is_off' => false, 'sort_order' => 8],
            ['name' => 'Libur', 'code' => 'L', 'color' => '#6c757d', 'is_off' => true, 'sort_order' => 99],
            ['name' => 'Putus', 'code' => 'PT', 'color' => '#fd7e14', 'start_time' => '06:00', 'end_time' => '12:00', 'is_off' => false, 'sort_order' => 10],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['code' => $shift['code']],
                $shift
            );
        }
    }
}
