<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('is_cash')->default(false);
        });

        Schema::create('final_cash_mutations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hotel_id');
            $table->bigInteger('bank_account_id');
            $table->string('type', 50);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('reference_type', 50);
            $table->bigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['bank_account_id', 'created_at']);
        });

        $trueValue = config('database.default') === 'pgsql' ? 'true' : '1';
        \Illuminate\Support\Facades\DB::statement("
            UPDATE bank_accounts SET is_cash = $trueValue WHERE LOWER(name) LIKE '%tunai%'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('final_cash_mutations');

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_cash');
        });
    }
};
