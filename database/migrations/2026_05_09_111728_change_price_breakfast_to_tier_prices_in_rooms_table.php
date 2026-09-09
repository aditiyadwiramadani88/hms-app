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
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('price_breakfast_public', 12, 2)->nullable();
            $table->decimal('price_breakfast_sales', 12, 2)->nullable();
            $table->decimal('price_breakfast_high_season', 12, 2)->nullable();
        });

        if (Schema::hasColumn('rooms', 'price_breakfast')) {
            DB::table('rooms')->whereNotNull('price_breakfast')->update([
                'price_breakfast_public' => DB::raw('price_breakfast'),
                'price_breakfast_sales' => DB::raw('price_breakfast'),
                'price_breakfast_high_season' => DB::raw('price_breakfast'),
            ]);

            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('price_breakfast');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('price_breakfast', 12, 2)->nullable();
        });

        DB::table('rooms')->whereNotNull('price_breakfast_public')->update([
            'price_breakfast' => DB::raw('price_breakfast_public'),
        ]);

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['price_breakfast_public', 'price_breakfast_sales', 'price_breakfast_high_season']);
        });
    }
};
