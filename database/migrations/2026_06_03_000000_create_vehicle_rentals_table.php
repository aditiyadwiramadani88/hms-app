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
        Schema::create('vehicle_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            
            $table->string('renter_name');
            $table->string('company_name')->nullable();
            $table->string('nik')->nullable();
            $table->string('sim_number')->nullable();
            
            $table->string('vehicle_plate_number');
            $table->integer('start_km')->nullable();
            $table->integer('end_km')->nullable();
            $table->integer('rental_days')->default(1);
            
            $table->decimal('daily_price', 15, 2)->default(0);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            
            $table->enum('status', ['rented', 'returned', 'cancelled'])->default('rented');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_rentals');
    }
};
