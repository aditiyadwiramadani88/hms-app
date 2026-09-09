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
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('pos_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_items');
    }
};
