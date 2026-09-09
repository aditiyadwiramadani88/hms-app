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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_id')->nullable();
            $table->text('description');
            $table->string('status', 50)->default('pending');
            $table->timestamps();

            $table->index('booking_id');
            $table->index('guest_id');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
