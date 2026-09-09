<?php

namespace Database\Seeders;

use App\Models\CleaningChecklistTemplate;
use App\Models\Hotel;
use Illuminate\Database\Seeder;

class CleaningChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Lantai', 'sort_order' => 1],
            ['name' => 'Kasur', 'sort_order' => 2],
            ['name' => 'Lemari', 'sort_order' => 3],
            ['name' => 'Kamar Mandi', 'sort_order' => 4],
            ['name' => 'Handuk', 'sort_order' => 5],
            ['name' => 'Tempat Sampah', 'sort_order' => 6],
        ];

        $hotels = Hotel::all();

        foreach ($hotels as $hotel) {
            foreach ($defaults as $default) {
                CleaningChecklistTemplate::updateOrCreate(
                    [
                        'hotel_id' => $hotel->id,
                        'name' => $default['name'],
                    ],
                    [
                        'sort_order' => $default['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
