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
        Schema::table('guests', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['password', 'email_verified_at', 'remember_token']);
        });
    }
};
