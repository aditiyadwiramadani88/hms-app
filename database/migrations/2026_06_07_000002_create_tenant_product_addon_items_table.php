<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_product_addon_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_group_id')->constrained('tenant_product_addon_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['addon_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_product_addon_items');
    }
};
