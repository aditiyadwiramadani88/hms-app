<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ob_bonus_settings', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->change();
        });

        // Delete old percentage settings
        \DB::table('ob_bonus_settings')
            ->whereIn('category', [
                'online_percentage', 'umum_percentage', 'sales_percentage', 
                'kos_percentage', 'pk_percentage', 'kosong_percentage', 'fixed_work_order_bonus'
            ])->delete();

        // Insert new default rates based on physical report image
        $rates = [
            'online_rate' => 2310,
            'umum_rate' => 2565,
            'sales_rate' => 1426,
            'kos_rate' => 8914,
            'pk_rate' => 750,
            'kosong_rate' => 209,
        ];

        foreach ($rates as $category => $value) {
            \DB::table('ob_bonus_settings')->updateOrInsert(
                ['category' => $category],
                [
                    'value' => $value, 
                    'description' => 'Fixed bonus rate for ' . str_replace('_rate', '', $category), 
                    'created_at' => now(), 
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ob_bonus_settings', function (Blueprint $table) {
            $table->decimal('value', 5, 2)->change();
        });
    }
};
