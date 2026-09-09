<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->string('check_in_photo')->nullable();
            $table->string('check_out_photo')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->foreignId('check_in_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            $table->foreignId('check_out_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            $table->string('status', 50)->default('present');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'employee_id', 'attendance_date'], 'attendance_unique');
            $table->index(['employee_id', 'attendance_date']);
            $table->index(['hotel_id', 'attendance_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
