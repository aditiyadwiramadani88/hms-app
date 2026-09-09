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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->decimal('value', 10, 2);
            $table->decimal('min_booking_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->integer('usage_limit');
            $table->integer('usage_count')->default(0);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->boolean('is_active')->default(true);
            $table->json('applicable_room_types')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index('valid_from');
            $table->index('valid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
