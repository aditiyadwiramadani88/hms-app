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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->change();
            $table->boolean('is_custom')->default(false);
            $table->string('custom_room_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable(false)->change();
            $table->dropColumn(['is_custom', 'custom_room_name']);
        });
    }
};
