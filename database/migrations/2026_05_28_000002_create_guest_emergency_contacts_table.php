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
        Schema::create('guest_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels');
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('contact_name', 255);
            $table->string('phone_number', 20);
            $table->string('relationship', 100)->nullable();
            $table->timestamps();

            $table->index('guest_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_emergency_contacts');
    }
};
