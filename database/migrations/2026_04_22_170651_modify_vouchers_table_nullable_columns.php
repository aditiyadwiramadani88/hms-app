<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->integer('usage_limit')->nullable()->change();
            $table->date('valid_from')->nullable()->change();
            $table->string('type', 50)->change(); // Syncing with controller expectations
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->integer('usage_limit')->nullable(false)->change();
            $table->date('valid_from')->nullable(false)->change();
            $table->string('type', 50)->change();
        });
    }
};
