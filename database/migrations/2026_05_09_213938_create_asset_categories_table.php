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
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hotel_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->unique(['hotel_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
