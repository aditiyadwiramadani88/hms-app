<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_product_addon_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_product_id')->nullable()->constrained('tenant_products')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['single', 'multi'])->default('single');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('max_selections')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'tenant_product_id']);
            $table->index(['tenant_product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_product_addon_groups');
    }
};
