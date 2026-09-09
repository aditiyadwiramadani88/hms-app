<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_transactions');
    }
};
