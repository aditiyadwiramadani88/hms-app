<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('stay_type', 50)->default('daily')->change();
            });
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('stay_type', 50)->nullable()->default(null)->change();
            });
        }
    }
};
