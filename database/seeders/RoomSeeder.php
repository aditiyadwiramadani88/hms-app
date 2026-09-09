<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Standard Rooms (101-105), Floor 1
        for ($i = 101; $i <= 105; $i++) {
            Room::firstOrCreate(['room_number' => (string) $i], [
                'room_type_id' => 1,
                'floor' => 1,
                'status' => 'Available',
            ]);
        }

        // Deluxe Rooms (201-205), Floor 2
        for ($i = 201; $i <= 205; $i++) {
            Room::firstOrCreate(['room_number' => (string) $i], [
                'room_type_id' => 2,
                'floor' => 2,
                'status' => 'Available',
            ]);
        }

        // Suite Rooms (301-303), Floor 3
        for ($i = 301; $i <= 303; $i++) {
            Room::firstOrCreate(['room_number' => (string) $i], [
                'room_type_id' => 3,
                'floor' => 3,
                'status' => 'Available',
            ]);
        }

        // Presidential Room (401), Floor 4
        Room::firstOrCreate(['room_number' => '401'], [
            'room_type_id' => 4,
            'floor' => 4,
            'status' => 'Available',
        ]);
    }
}
