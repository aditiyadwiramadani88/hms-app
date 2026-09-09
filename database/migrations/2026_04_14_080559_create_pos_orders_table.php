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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 50);
            $table->string('payment_status', 50)->default('unpaid');
            $table->string('status', 50)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('booking_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
