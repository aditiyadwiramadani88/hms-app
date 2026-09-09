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
        Schema::dropIfExists('legacy_customers');
        Schema::create('legacy_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_file')->nullable();
            $table->string('legacy_customer_code', 50)->nullable();
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('citizenship_code', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('id_number')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('legacy_customer_type_code', 50)->nullable();
            $table->unsignedInteger('legacy_stay_type_days')->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'legacy_customer_code']);
            $table->index(['hotel_id', 'legacy_customer_type_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_customers');
    }
};
