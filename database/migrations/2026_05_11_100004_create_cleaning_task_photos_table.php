<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_task_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaning_task_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->bigInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_task_photos');
    }
};
