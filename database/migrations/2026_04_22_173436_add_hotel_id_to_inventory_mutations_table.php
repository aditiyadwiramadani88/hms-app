<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Set default hotel_id for existing records if any
        $defaultHotel = DB::table('hotels')->where('is_active', true)->first();
        if ($defaultHotel) {
            DB::table('inventory_mutations')->update(['hotel_id' => $defaultHotel->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropColumn('hotel_id');
        });
    }
};
