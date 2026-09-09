<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('status', 50)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_approvals');
    }
};
