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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->string('type', 50)->default('cleaning');
            $table->string('status', 50)->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->decimal('bonus_amount', 10, 2)->default(750);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
