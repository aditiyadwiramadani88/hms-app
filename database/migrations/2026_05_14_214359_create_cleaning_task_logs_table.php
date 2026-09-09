<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaning_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('cleaning_checklist_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // submitted, approved, rejected, revised, photo_uploaded
            $table->string('role'); // ob, checker
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();

            $table->index(['cleaning_task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_task_logs');
    }
};
