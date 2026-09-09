<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'requires_deposit')) {
                $table->boolean('requires_deposit')->default(false);
            }
            if (!Schema::hasColumn('inventories', 'deposit_amount')) {
                $table->decimal('deposit_amount', 15, 2)->default(0);
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'price_extra_person')) {
                $table->decimal('price_extra_person', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('rooms', 'max_occupancy')) {
                $table->integer('max_occupancy')->default(2);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['requires_deposit', 'deposit_amount']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['price_extra_person', 'max_occupancy']);
        });
    }
};
