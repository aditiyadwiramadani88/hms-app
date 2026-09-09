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
        Schema::create('checklist_template_room_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaning_checklist_template_id')->constrained('cleaning_checklist_templates', 'id', 'ctrt_template_id_foreign')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('room_types', 'id', 'ctrt_room_type_id_foreign')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_template_room_type');
    }
};
