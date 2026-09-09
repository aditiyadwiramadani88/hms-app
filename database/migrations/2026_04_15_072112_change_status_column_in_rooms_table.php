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
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('status', 50)->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Note: Reverting back to ENUM might fail if there's invalid data, 
            // so we'll just keep it as a string or manually revert it if strictly needed.
            // But typically down() for ENUM -> String is better left as string or skipped.
        });
    }
};
