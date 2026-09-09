<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->decimal('available_balance', 15, 2)->default(0);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_realized')->default(false);
        });

        // Initialize available_balance with current balance for existing accounts
        // We assume existing balances are already realized for safety
        DB::table('bank_accounts')->update([
            'available_balance' => DB::raw('balance')
        ]);

        // Mark existing transactions as realized for historical consistency
        DB::table('transactions')->update([
            'is_realized' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_realized');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('available_balance');
        });
    }
};
