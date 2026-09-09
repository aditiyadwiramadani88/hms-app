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
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('price_public', 15, 2)->default(0);
            $table->decimal('price_sales', 15, 2)->default(0);
            $table->decimal('price_high_season', 15, 2)->default(0);
            $table->boolean('is_kos')->default(false);
            $table->decimal('price_kos', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['price_public', 'price_sales', 'price_high_season', 'is_kos', 'price_kos']);
        });
    }
};
