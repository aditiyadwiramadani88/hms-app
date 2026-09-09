<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('guests') && Schema::hasColumn('guests', 'citizenship_code')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->string('citizenship_code', 100)->nullable()->change();
            });
        }

        if (Schema::hasTable('legacy_customers') && Schema::hasColumn('legacy_customers', 'citizenship_code')) {
            Schema::table('legacy_customers', function (Blueprint $table) {
                $table->string('citizenship_code', 100)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('guests') && Schema::hasColumn('guests', 'citizenship_code')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->string('citizenship_code', 20)->nullable()->change();
            });
        }

        if (Schema::hasTable('legacy_customers') && Schema::hasColumn('legacy_customers', 'citizenship_code')) {
            Schema::table('legacy_customers', function (Blueprint $table) {
                $table->string('citizenship_code', 20)->nullable()->change();
            });
        }
    }
};
