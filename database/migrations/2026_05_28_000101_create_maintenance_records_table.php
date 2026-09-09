<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->foreignId('category_id')->constrained('maintenance_categories')->onDelete('cascade');
            $table->date('maintenance_date');
            $table->text('description')->nullable();
            $table->json('actions')->nullable()->comment('Checklist actions performed, e.g. ["service","replace_module","add_freon","replace_equipment"]');
            $table->string('technician_name')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed'])->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
