<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add quantity and acquisition source to assets
        Schema::table('assets', function (Blueprint $table) {
            $table->integer('quantity')->default(1);
            $table->string('acquisition_type', 50)->default('purchase');
            $table->text('acquisition_notes')->nullable();
        });

        // Create asset_repairs table for repair history
        Schema::create('asset_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->date('repair_date');
            $table->string('repair_type', 100); // Perbaikan Ringan, Perbaikan Berat, Servis Rutin, Penggantian Part
            $table->string('technician', 255)->nullable(); // Nama teknisi/toko
            $table->text('problem_description'); // Deskripsi masalah
            $table->text('action_taken')->nullable(); // Tindakan yang dilakukan
            $table->text('result')->nullable(); // Hasil perbaikan
            $table->decimal('cost', 12, 2)->default(0); // Biaya perbaikan
            $table->string('status', 50)->default('completed');
            $table->string('warranty_info', 255)->nullable(); // Info garansi perbaikan
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'repair_date']);
        });

        // Create asset_stock_mutations for qty tracking
        Schema::create('asset_stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // masuk atau keluar
            $table->integer('quantity');
            $table->string('reason', 50);
            $table->text('notes')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable(); // Harga per unit (untuk pembelian)
            $table->string('reference', 255)->nullable(); // No nota/invoice
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_stock_mutations');
        Schema::dropIfExists('asset_repairs');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'acquisition_type', 'acquisition_notes']);
        });
    }
};
