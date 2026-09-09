<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RoomType::create([
            'name' => 'Standard',
            'description' => 'Comfortable standard room with essential amenities',
            'base_price' => 300000,
            'max_guests' => 2,
            'amenities' => ['WiFi', 'AC', 'TV'],
            'is_active' => true,
        ]);

        RoomType::create([
            'name' => 'Deluxe',
            'description' => 'Spacious deluxe room with premium amenities',
            'base_price' => 500000,
            'max_guests' => 3,
            'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar'],
            'is_active' => true,
        ]);

        RoomType::create([
            'name' => 'Suite',
            'description' => 'Luxurious suite with extra space and premium facilities',
            'base_price' => 800000,
            'max_guests' => 4,
            'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Bathtub', 'Kitchen'],
            'is_active' => true,
        ]);

        RoomType::create([
            'name' => 'Presidential',
            'description' => 'Our finest presidential suite with ultimate luxury',
            'base_price' => 1500000,
            'max_guests' => 6,
            'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Bathtub', 'Kitchen', 'Living Room', 'Jacuzzi'],
            'is_active' => true,
        ]);
    }
}
