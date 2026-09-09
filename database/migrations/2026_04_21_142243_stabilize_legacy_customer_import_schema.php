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
        Schema::table('legacy_customers', function (Blueprint $table) {
            $table->string('citizenship_code', 100)->nullable()->change();
            
            $indexes = collect(Schema::getIndexes('legacy_customers'))->pluck('name');
            
            if ($indexes->contains('legacy_customers_hotel_id_legacy_customer_code_index')) {
                $table->dropIndex(['hotel_id', 'legacy_customer_code']);
            }
            
            if (!$indexes->contains('legacy_customers_hotel_id_code_unique')) {
                $table->unique(['hotel_id', 'legacy_customer_code'], 'legacy_customers_hotel_id_code_unique');
            }
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('citizenship_code', 100)->nullable()->change();
            
            $indexes = collect(Schema::getIndexes('guests'))->pluck('name');
            
            if ($indexes->contains('guests_hotel_id_legacy_customer_code_index')) {
                $table->dropIndex(['hotel_id', 'legacy_customer_code']);
            }
            
            if (!$indexes->contains('guests_hotel_id_legacy_code_unique')) {
                $table->unique(['hotel_id', 'legacy_customer_code'], 'guests_hotel_id_legacy_code_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('guests'))->pluck('name');
            if ($indexes->contains('guests_hotel_id_legacy_code_unique')) {
                $table->dropUnique('guests_hotel_id_legacy_code_unique');
            }
            $table->index(['hotel_id', 'legacy_customer_code']);
        });

        Schema::table('legacy_customers', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('legacy_customers'))->pluck('name');
            if ($indexes->contains('legacy_customers_hotel_id_code_unique')) {
                $table->dropUnique('legacy_customers_hotel_id_code_unique');
            }
            $table->index(['hotel_id', 'legacy_customer_code']);
        });
    }
};
