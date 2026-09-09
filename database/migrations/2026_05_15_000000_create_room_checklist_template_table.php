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
        Schema::create('room_checklist_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_template_id')
                ->constrained('cleaning_checklist_templates')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['room_id', 'checklist_template_id'], 'unique_room_checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_checklist_template');
    }
};
