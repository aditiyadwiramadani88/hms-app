<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->cascadeOnDelete();
            $table->index('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropColumn('hotel_id');
        });
    }
};
