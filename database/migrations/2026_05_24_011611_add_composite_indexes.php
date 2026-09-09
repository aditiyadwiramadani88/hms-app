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
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['hotel_id', 'status'], 'idx_bookings_hotel_status');
            $table->index(['hotel_id', 'check_in'], 'idx_bookings_hotel_checkin');
            $table->index(['hotel_id', 'created_at'], 'idx_bookings_hotel_created');
        });

        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->index(['hotel_id', 'employee_id', 'schedule_date'], 'idx_emp_schedules_hotel_emp_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['hotel_id', 'created_at'], 'idx_transactions_hotel_created');
            $table->index(['hotel_id', 'type'], 'idx_transactions_hotel_type');
        });

        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->index(['hotel_id', 'time_in'], 'idx_vehicle_logs_hotel_timein');
        });

        Schema::table('cleaning_tasks', function (Blueprint $table) {
            $table->index(['hotel_id', 'status'], 'idx_cleaning_tasks_hotel_status');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->index(['hotel_id', 'status'], 'idx_pos_orders_hotel_status');
            $table->index(['hotel_id', 'created_at'], 'idx_pos_orders_hotel_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_hotel_status');
            $table->dropIndex('idx_bookings_hotel_checkin');
            $table->dropIndex('idx_bookings_hotel_created');
        });

        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_emp_schedules_hotel_emp_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_hotel_created');
            $table->dropIndex('idx_transactions_hotel_type');
        });

        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_logs_hotel_timein');
        });

        Schema::table('cleaning_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_cleaning_tasks_hotel_status');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('idx_pos_orders_hotel_status');
            $table->dropIndex('idx_pos_orders_hotel_created');
        });
    }
};
