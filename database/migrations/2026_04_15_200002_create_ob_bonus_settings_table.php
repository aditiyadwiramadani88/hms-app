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
        Schema::create('ob_bonus_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('category'); // e.g., 'online_percentage', 'umum_percentage', etc.
            $table->decimal('value', 5, 2); // percentage or fixed value
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ob_bonus_settings');
    }
};
