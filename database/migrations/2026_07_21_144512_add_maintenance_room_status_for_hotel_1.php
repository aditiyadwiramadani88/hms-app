<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every hotel's rooms table has real status values with no matching
 * RoomStatus row for that hotel -- the add/edit room form's Status select
 * (sourced from RoomStatus) can't represent those rooms' current status at
 * all, and any filter dropdown built from RoomStatus can't reach them
 * either. Found by cross-checking rooms.status against room_statuses per
 * hotel: hotel 1 is missing "maintenance" (2 rooms), hotel 3 is missing
 * "Available" and "In-House" (19 + 12 rooms).
 */
return new class extends Migration
{
    protected array $missing = [
        ['hotel_id' => 1, 'name' => 'maintenance', 'color' => '#dc3545', 'is_available' => false],
        ['hotel_id' => 3, 'name' => 'Available', 'color' => 'success', 'is_available' => true],
        ['hotel_id' => 3, 'name' => 'In-House', 'color' => 'primary', 'is_available' => false],
    ];

    public function up(): void
    {
        foreach ($this->missing as $row) {
            $exists = DB::table('room_statuses')
                ->where('hotel_id', $row['hotel_id'])
                ->where('name', $row['name'])
                ->exists();

            if (!$exists) {
                DB::table('room_statuses')->insert([
                    'hotel_id' => $row['hotel_id'],
                    'name' => $row['name'],
                    'color' => $row['color'],
                    'icon' => null,
                    'is_available' => $row['is_available'],
                    'display_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->missing as $row) {
            DB::table('room_statuses')->where('hotel_id', $row['hotel_id'])->where('name', $row['name'])->delete();
        }
    }
};
