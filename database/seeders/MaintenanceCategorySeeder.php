<?php

namespace Database\Seeders;

use App\Models\MaintenanceCategory;
use Illuminate\Database\Seeder;

class MaintenanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $hotelId = \App\Models\Hotel::first()?->id;
        if (!$hotelId) return;

        $categories = [
            ['name' => 'AC', 'description' => 'Perbaikan dan perawatan AC', 'sort_order' => 1],
            ['name' => 'Listrik', 'description' => 'Perbaikan instalasi dan perangkat listrik', 'sort_order' => 2],
            ['name' => 'Air', 'description' => 'Perbaikan saluran air, pompa, dan sanitasi', 'sort_order' => 3],
            ['name' => 'Fogging', 'description' => 'Fogging/pengasapan anti nyamuk dan hama', 'sort_order' => 4],
            ['name' => 'Elektronik', 'description' => 'Perbaikan TV, kulkas, dan perangkat elektronik lainnya', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            MaintenanceCategory::firstOrCreate(
                ['hotel_id' => $hotelId, 'name' => $cat['name']],
                ['description' => $cat['description'], 'sort_order' => $cat['sort_order'], 'is_active' => true]
            );
        }
    }
}
