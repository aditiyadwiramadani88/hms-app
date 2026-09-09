<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_statuses', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['name', 'hotel_id']);
        });
    }

    public function down(): void
    {
        Schema::table('room_statuses', function (Blueprint $table) {
            $table->dropUnique(['name', 'hotel_id']);
            $table->unique('name');
        });
    }
};
