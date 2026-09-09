<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Nama usaha tenant
            $table->string('owner_name'); // Nama pemilik/penanggung jawab
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('location_description')->nullable(); // "Kantin Lantai 1", "Kios A3"
            $table->decimal('rent_amount', 12, 2); // Jumlah sewa per bulan
            $table->tinyInteger('rent_due_day')->default(1); // Tanggal jatuh tempo (1-28)
            $table->boolean('is_active')->default(true);
            $table->date('contract_start');
            $table->date('contract_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
