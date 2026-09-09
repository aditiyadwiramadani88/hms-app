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
        Schema::table('guests', function (Blueprint $table) {
            if (! Schema::hasColumn('guests', 'customer_type_id')) {
                $table->foreignId('customer_type_id')->nullable()->constrained('customer_types')->nullOnDelete();
            }

            if (! Schema::hasColumn('guests', 'legacy_customer_code')) {
                $table->string('legacy_customer_code', 50)->nullable();
            }

            if (! Schema::hasColumn('guests', 'company_name')) {
                $table->string('company_name')->nullable();
            }

            if (! Schema::hasColumn('guests', 'vehicle_number')) {
                $table->string('vehicle_number', 50)->nullable();
            }

            if (! Schema::hasColumn('guests', 'legacy_stay_type_days')) {
                $table->unsignedInteger('legacy_stay_type_days')->nullable();
            }

            if (! Schema::hasColumn('guests', 'id_card_photo')) {
                $table->string('id_card_photo')->nullable();
            }

            if (! Schema::hasColumn('guests', 'reference_source')) {
                $table->string('reference_source')->nullable();
            }

            if (! Schema::hasColumn('guests', 'identity_type')) {
                $table->string('identity_type', 100)->nullable();
            }

            if (! Schema::hasColumn('guests', 'citizenship_code')) {
                $table->string('citizenship_code', 20)->nullable();
            }
        });

        $indexes = collect();
        if (config('database.default') === 'mysql') {
            $indexes = collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM guests"))->pluck('Key_name');
        }

        Schema::table('guests', function (Blueprint $table) use ($indexes) {
            if (config('database.default') !== 'mysql' || ! $indexes->contains('guests_hotel_id_legacy_customer_code_index')) {
                $table->index(['hotel_id', 'legacy_customer_code'], 'guests_hotel_id_legacy_customer_code_index');
            }
            if (config('database.default') !== 'mysql' || ! $indexes->contains('guests_hotel_id_company_name_index')) {
                $table->index(['hotel_id', 'company_name'], 'guests_hotel_id_company_name_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['hotel_id', 'legacy_customer_code']);
            $table->dropIndex(['hotel_id', 'company_name']);
            $table->dropConstrainedForeignId('customer_type_id');
            $table->dropColumn([
                'legacy_customer_code',
                'company_name',
                'vehicle_number',
                'legacy_stay_type_days',
                'reference_source',
                'id_card_photo',
                'identity_type',
                'citizenship_code',
            ]);
        });
    }
};
