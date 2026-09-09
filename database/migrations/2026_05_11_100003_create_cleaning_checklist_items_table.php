<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaning_task_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['cleaning_task_id', 'is_done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_checklist_items');
    }
};
