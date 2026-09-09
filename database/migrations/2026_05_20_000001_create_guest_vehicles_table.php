<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->string('plate_number', 20);
            $table->string('vehicle_type', 50);
            $table->string('vehicle_brand', 100)->nullable();
            $table->string('vehicle_color', 50)->nullable();
            $table->string('owner_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plate_number', 'hotel_id']);
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_vehicles');
    }
};
