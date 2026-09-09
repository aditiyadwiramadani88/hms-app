<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        Schema::table('guest_categories', function (Blueprint $table) {
            $table->decimal('breakfast_price', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_categories', function (Blueprint $table) {
            $table->dropColumn('breakfast_price');
        });
    }
};
