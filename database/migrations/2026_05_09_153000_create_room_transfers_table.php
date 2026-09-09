<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('to_room_id')->constrained('rooms')->cascadeOnDelete();
            $table->dateTime('transferred_at');
            $table->string('reason');
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->integer('nights_in_old_room');
            $table->decimal('old_room_price_per_night', 10, 2);
            $table->decimal('new_room_price_per_night', 10, 2);
            $table->decimal('price_difference', 10, 2);
            $table->string('new_tier_applied');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_transfers');
    }
};