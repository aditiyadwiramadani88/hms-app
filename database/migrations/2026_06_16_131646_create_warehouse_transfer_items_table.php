<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};
