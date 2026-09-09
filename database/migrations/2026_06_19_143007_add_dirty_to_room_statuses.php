<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add "dirty" status for rooms that need cleaning after check-out or room transfer
        $exists = DB::table("room_statuses")->where("name", "dirty")->exists();
        if (!$exists) {
            DB::table("room_statuses")->insert([
                "name" => "dirty",
                "is_available" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table("room_statuses")->where("name", "dirty")->delete();
    }
};
