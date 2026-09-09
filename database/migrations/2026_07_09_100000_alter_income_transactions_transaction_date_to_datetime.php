<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->datetime('transaction_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->date('transaction_date')->change();
        });
    }
};
