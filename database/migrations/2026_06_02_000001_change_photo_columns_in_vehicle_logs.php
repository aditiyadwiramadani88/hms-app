<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->text('photo_in')->nullable()->change();
            $table->text('photo_out')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->string('photo_in', 255)->nullable()->change();
            $table->string('photo_out', 255)->nullable()->change();
        });
    }
};
