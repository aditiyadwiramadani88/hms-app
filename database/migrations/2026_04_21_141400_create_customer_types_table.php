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
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('legacy_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'legacy_code']);
            $table->unique(['hotel_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_types');
    }
};
