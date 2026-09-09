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
        Schema::create('room_kost_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels');
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->integer('duration_months');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('fixed_price', 15, 2)->nullable();
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'duration_months']);
            $table->index(['hotel_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_kost_pricing_tiers');
    }
};
