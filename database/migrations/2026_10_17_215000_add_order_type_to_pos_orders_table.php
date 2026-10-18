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
        if (Schema::hasTable('pos_orders') && !Schema::hasColumn('pos_orders', 'order_type')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->string('order_type', 50)->default('pos')->after('order_number')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_orders') && Schema::hasColumn('pos_orders', 'order_type')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropColumn('order_type');
            });
        }
    }
};
