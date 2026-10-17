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
        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_orders', 'rental_status')) {
                    $table->string('rental_status', 50)->nullable()->after('order_type')->index();
                }
                if (!Schema::hasColumn('pos_orders', 'returned_at')) {
                    $table->timestamp('returned_at')->nullable()->after('rental_status');
                }
                if (!Schema::hasColumn('pos_orders', 'returned_by')) {
                    $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('pos_orders', 'return_notes')) {
                    $table->text('return_notes')->nullable()->after('returned_by');
                }
            });
        }

        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_order_items', 'returned_quantity')) {
                    $table->integer('returned_quantity')->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('pos_order_items', 'returned_at')) {
                    $table->timestamp('returned_at')->nullable()->after('returned_quantity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropForeign(['returned_by']);
                $table->dropColumn(['rental_status', 'returned_at', 'returned_by', 'return_notes']);
            });
        }

        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                $table->dropColumn(['returned_quantity', 'returned_at']);
            });
        }
    }
};
