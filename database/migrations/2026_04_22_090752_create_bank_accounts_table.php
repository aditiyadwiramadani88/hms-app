<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., Tunai, BCA, Mandiri
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
        Schema::dropIfExists('bank_accounts');
    }
};
