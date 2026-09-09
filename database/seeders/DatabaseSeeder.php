<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DefaultHotelSeeder::class,
            RolePermissionSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            InventorySeeder::class,
            VoucherSeeder::class,
            ObBonusSettingSeeder::class,
            BookingSourceSeeder::class,
            CleaningChecklistTemplateSeeder::class,
            ShiftSeeder::class,
            ScheduleLocationSeeder::class,
            LeaveTypeSeeder::class,
        ]);
    }
}
