<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->integer('year');
            $table->integer('allocated')->default(0);
            $table->integer('used')->default(0);
            $table->integer('remaining')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'employee_id', 'leave_type_id', 'year'], 'leave_balance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
