<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For each hotel, ensure they have at least one Warehouse ("Gudang Utama")
        $hotels = DB::table('hotels')->get();
        foreach ($hotels as $hotel) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'hotel_id' => $hotel->id,
                'name' => 'Gudang Utama',
                'type' => 'storage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inventories = DB::table('inventories')
                ->where('hotel_id', $hotel->id)
                ->get();

            foreach ($inventories as $inv) {
                DB::table('inventory_warehouse_stocks')->insert([
                    'inventory_id' => $inv->id,
                    'warehouse_id' => $warehouseId,
                    'stock' => $inv->stock,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('inventory_warehouse_stocks')->truncate();
        DB::table('warehouse_transfer_items')->truncate();
        DB::table('warehouse_transfers')->truncate();
        DB::table('warehouses')->truncate();
        Schema::enableForeignKeyConstraints();
    }
};
