<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaning_task_photos', function (Blueprint $table) {
            $table->string('type', 20)->default('general')->after('cleaning_task_id');
            // type: 'before', 'after', 'general'
        });
    }

    public function down(): void
    {
        Schema::table('cleaning_task_photos', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
