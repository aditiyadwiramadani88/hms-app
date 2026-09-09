<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name'); // Snapshot nama saat transaksi
            $table->unsignedInteger('quantity');
            $table->decimal('price', 12, 2); // Snapshot harga saat transaksi
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('tenant_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_transaction_items');
    }
};
