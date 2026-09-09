<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Drop the single-column unique on email
            $table->dropUnique('guests_email_unique');
            // Add composite unique: same email allowed in different hotels
            $table->unique(['email', 'hotel_id'], 'guests_email_hotel_unique');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique('guests_email_hotel_unique');
            $table->unique('email', 'guests_email_unique');
        });
    }
};
