<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $col) {
            $col->boolean('is_room_markup')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $col) {
            $col->dropColumn('is_room_markup');
        });
    }
};
