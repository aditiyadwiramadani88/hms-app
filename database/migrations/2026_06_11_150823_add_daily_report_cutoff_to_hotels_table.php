<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('daily_report_cutoff', 5)->default('13:00')->after('tax_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('daily_report_cutoff');
        });
    }
};
