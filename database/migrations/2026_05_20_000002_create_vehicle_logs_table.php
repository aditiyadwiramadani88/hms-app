<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('guest_vehicle_id')->nullable()->constrained('guest_vehicles')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('driver_name', 255);
            $table->string('plate_number', 20);
            $table->string('vehicle_type', 50);
            $table->string('vehicle_color', 50)->nullable();
            $table->string('photo_in', 255)->nullable();
            $table->string('photo_out', 255)->nullable();
            $table->string('purpose', 50);
            $table->string('destination_room', 50)->nullable();
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->string('status', 50)->default('in');
            $table->foreignId('security_in_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('security_out_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['plate_number', 'status']);
            $table->index(['hotel_id', 'time_in']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_logs');
    }
};
