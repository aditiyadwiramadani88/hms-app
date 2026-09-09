<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultHotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hotel = \App\Models\Hotel::updateOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Default Hotel Branch',
                'address' => 'Main Street No. 1',
                'phone' => '021-123456',
                'is_active' => true,
            ]
        );

        $tables = [
            'room_types',
            'rooms',
            'guests',
            'bookings',
            'transactions',
            'pos_orders',
            'pos_order_items',
            'inventories',
            'maintenance_logs',
            'vouchers',
            'audit_logs'
        ];

        foreach ($tables as $tableName) {
            \Illuminate\Support\Facades\DB::table($tableName)
                ->whereNull('hotel_id')
                ->update(['hotel_id' => $hotel->id]);
        }

        // Assign all existing users to this hotel
        foreach (\App\Models\User::all() as $user) {
            if (!$user->hotels()->where('hotel_id', $hotel->id)->exists()) {
                $user->hotels()->attach($hotel->id, ['role' => 'Admin']);
            }
        }

        // Also update Spatie roles
        $tableNames = config('permission.table_names');
        $hotelIdColumn = config('permission.column_names.team_foreign_key', 'hotel_id');

        \Illuminate\Support\Facades\DB::table($tableNames['roles'])
            ->whereNull($hotelIdColumn)
            ->update([$hotelIdColumn => $hotel->id]);

        \Illuminate\Support\Facades\DB::table($tableNames['model_has_roles'])
            ->whereNull($hotelIdColumn)
            ->update([$hotelIdColumn => $hotel->id]);

        \Illuminate\Support\Facades\DB::table($tableNames['model_has_permissions'])
            ->whereNull($hotelIdColumn)
            ->update([$hotelIdColumn => $hotel->id]);
    }
}
