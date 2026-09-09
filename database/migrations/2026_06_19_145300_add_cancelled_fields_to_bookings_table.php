<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('actual_check_out');
            $table->string('cancelled_reason')->nullable()->after('cancelled_at');
            $table->string('cancelled_photo')->nullable()->after('cancelled_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancelled_reason', 'cancelled_photo']);
        });
    }
};
