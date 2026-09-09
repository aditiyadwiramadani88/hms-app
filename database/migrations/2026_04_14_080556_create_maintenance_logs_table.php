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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('issue');
            $table->string('priority', 50)->default('medium');
            $table->string('status', 50)->default('reported');
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('room_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
