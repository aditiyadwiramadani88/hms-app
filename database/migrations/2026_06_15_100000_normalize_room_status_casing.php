<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE rooms SET status = 'Available' WHERE LOWER(status) = 'available'");
    }

    public function down(): void
    {
        DB::statement("UPDATE rooms SET status = 'available' WHERE status = 'Available'");
    }
};
