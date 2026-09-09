<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_transactions', function (Blueprint $table) {
            $table->dropUnique('tenant_transactions_transaction_number_unique');
            $table->unique(['tenant_id', 'transaction_number'], 'tenant_transactions_tenant_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_transactions', function (Blueprint $table) {
            $table->dropUnique('tenant_transactions_tenant_number_unique');
            $table->unique('transaction_number');
        });
    }
};
