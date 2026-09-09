<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->decimal('total_amount', 12, 2);
            $table->unsignedInteger('items_count');
            $table->string('payment_method', 50);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_date']);
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_transactions');
    }
};
