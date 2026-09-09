<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = Hotel::all();

        $defaults = [
            ['name' => 'Cuti Tahunan', 'is_paid' => true, 'max_days_per_year' => 12, 'sort_order' => 1],
            ['name' => 'Cuti Sakit', 'is_paid' => true, 'max_days_per_year' => null, 'sort_order' => 2],
            ['name' => 'Izin', 'is_paid' => true, 'max_days_per_year' => null, 'sort_order' => 3],
            ['name' => 'Cuti Tanpa Gaji', 'is_paid' => false, 'max_days_per_year' => null, 'sort_order' => 4],
            ['name' => 'Cuti Melahirkan', 'is_paid' => true, 'max_days_per_year' => 90, 'sort_order' => 5],
            ['name' => 'Cuti Menikah', 'is_paid' => true, 'max_days_per_year' => 3, 'sort_order' => 6],
        ];

        foreach ($hotels as $hotel) {
            foreach ($defaults as $default) {
                LeaveType::firstOrCreate(
                    ['hotel_id' => $hotel->id, 'name' => $default['name']],
                    [
                        'is_paid' => $default['is_paid'],
                        'max_days_per_year' => $default['max_days_per_year'],
                        'is_active' => true,
                        'sort_order' => $default['sort_order'],
                    ]
                );
            }
        }
    }
}
