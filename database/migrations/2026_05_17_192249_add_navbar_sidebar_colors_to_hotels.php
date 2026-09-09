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
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('navbar_color', 20)->nullable();
            $table->string('sidebar_color', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['navbar_color', 'sidebar_color']);
        });
    }
};
