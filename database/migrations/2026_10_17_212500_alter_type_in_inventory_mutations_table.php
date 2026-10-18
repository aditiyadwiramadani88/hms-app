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
        if (Schema::hasTable('inventory_mutations')) {
            if (config('database.default') === 'pgsql') {
                DB::statement("ALTER TABLE inventory_mutations ALTER COLUMN type TYPE VARCHAR(50)");
            } else {
                DB::statement("ALTER TABLE inventory_mutations MODIFY COLUMN type VARCHAR(50) NOT NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_mutations')) {
            if (config('database.default') === 'mysql') {
                DB::statement("ALTER TABLE inventory_mutations MODIFY COLUMN type ENUM('in', 'out', 'adjustment') NOT NULL");
            }
        }
    }
};
