<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('billing_period'); // Format: "2026-01" (YYYY-MM)
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status', 50)->default('unpaid');
            $table->datetime('paid_at')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'billing_period']);
            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billings');
    }
};
