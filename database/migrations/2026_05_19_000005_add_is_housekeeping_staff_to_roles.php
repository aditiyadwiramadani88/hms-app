<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_housekeeping_staff')->default(false);
        });

        // Auto-set for existing roles that contain 'Housekeeping' or 'OB'
        \DB::table('roles')
            ->where('name', 'like', '%Housekeeping%')
            ->orWhere('name', 'like', '%OB%')
            ->update(['is_housekeeping_staff' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_housekeeping_staff');
        });
    }
};
