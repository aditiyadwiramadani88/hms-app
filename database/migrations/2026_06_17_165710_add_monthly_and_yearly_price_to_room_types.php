<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->decimal('monthly_price', 12, 2)->nullable()->after('base_price');
            $table->decimal('yearly_price', 12, 2)->nullable()->after('monthly_price');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('yearly_price', 12, 2)->nullable()->after('price_kos');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'yearly_price']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('yearly_price');
        });
    }
};
