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
        Schema::create('hotel_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('slug', 50);
            $table->string('title', 255)->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['hotel_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_pages');
    }
};
