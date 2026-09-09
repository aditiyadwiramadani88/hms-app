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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();
            $table->integer('adults');
            $table->integer('children');
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('status', 50)->default('pending');
            $table->string('payment_status', 50)->default('unpaid');
            $table->text('notes')->nullable();
            $table->string('voucher_code')->nullable();
            $table->string('source', 50)->default('walk_in');
            $table->timestamps();
            $table->softDeletes();

            $table->index('guest_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('check_in');
            $table->index('check_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
