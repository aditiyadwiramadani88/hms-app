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
        Schema::table('cleaning_checklist_items', function (Blueprint $table) {
            $table->text('comment')->nullable();
            $table->string('verification_status')->default('pending'); // pending, approved, rejected
            $table->text('checker_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_checklist_items', function (Blueprint $table) {
            $table->dropColumn(['comment', 'verification_status', 'checker_note']);
        });
    }
};
