<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convertEnumToVarchar('bookings', 'status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'], 'pending');
        $this->convertEnumToVarchar('bookings', 'payment_status', ['unpaid', 'partial', 'paid', 'refunded'], 'unpaid');
        $this->convertEnumToVarchar('bookings', 'source', ['online', 'walk_in', 'phone', 'email', 'agent'], 'walk_in');
        $this->convertEnumToVarchar('bookings', 'stay_type', ['daily', 'monthly'], 'daily');

        $this->convertEnumToVarchar('transactions', 'type', ['payment', 'charge', 'refund']);
        $this->convertEnumToVarchar('transactions', 'payment_method', ['cash', 'credit_card', 'bank_transfer', 'midtrans', 'xendit', 'charge_to_room']);
        $this->convertEnumToVarchar('transactions', 'status', ['pending', 'success', 'failed', 'cancelled'], 'pending');

        $this->convertEnumToVarchar('pos_orders', 'payment_method', ['cash', 'credit_card', 'charge_to_room', 'qris', 'bank_transfer', 'other']);
        $this->convertEnumToVarchar('pos_orders', 'payment_status', ['unpaid', 'paid'], 'unpaid');
        $this->convertEnumToVarchar('pos_orders', 'status', ['pending', 'processing', 'completed', 'cancelled'], 'pending');

        $this->convertEnumToVarchar('rooms', 'status', ['available', 'occupied', 'dirty', 'cleaning', 'out_of_order', 'maintenance'], 'available');
        $this->convertEnumToVarchar('guests', 'gender', ['male', 'female']);
        $this->convertEnumToVarchar('cleaning_tasks', 'status', ['belum_mulai', 'sedang_dikerjakan', 'selesai'], 'belum_mulai');

        $this->convertEnumToVarchar('vehicle_logs', 'vehicle_type', ['motor', 'mobil', 'truck']);
        $this->convertEnumToVarchar('vehicle_logs', 'purpose', ['menginap', 'kunjungan', 'delivery', 'karyawan', 'lainnya']);
        $this->convertEnumToVarchar('vehicle_logs', 'status', ['in', 'out', 'rejected'], 'in');

        $this->convertEnumToVarchar('shift_swap_requests', 'status', ['pending', 'approved', 'rejected', 'cancelled'], 'pending');
        $this->convertEnumToVarchar('shift_handovers', 'status', ['draft', 'submitted', 'confirmed', 'disputed'], 'draft');

        $this->convertEnumToVarchar('work_orders', 'type', ['cleaning', 'maintenance', 'other'], 'cleaning');
        $this->convertEnumToVarchar('work_orders', 'status', ['pending', 'in_progress', 'completed'], 'pending');

        $this->convertEnumToVarchar('tenant_billings', 'status', ['unpaid', 'paid', 'overdue'], 'unpaid');
        $this->convertEnumToVarchar('attendances', 'status', ['present', 'late', 'early_leave', 'late_and_early_leave', 'absent'], 'present');
        $this->convertEnumToVarchar('purchases', 'status', ['draft', 'received', 'cancelled'], 'draft');

        $this->convertEnumToVarchar('transaction_categories', 'type', ['income', 'expense']);
        $this->convertEnumToVarchar('vouchers', 'type', ['percentage', 'fixed']);

        $this->convertEnumToVarchar('final_cash_mutations', 'type', ['in', 'out']);
        $this->convertEnumToVarchar('final_cash_mutations', 'reference_type', ['booking_checkout', 'owner_withdrawal', 'manual_adjustment']);

        $this->convertEnumToVarchar('booking_edit_requests', 'status', ['pending', 'approved', 'rejected'], 'pending');
        $this->convertEnumToVarchar('kost_payment_approvals', 'status', ['pending', 'approved', 'rejected'], 'pending');
    }

    private function convertEnumToVarchar(string $table, string $column, array $values, string $default = null, bool $nullable = true): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // Skip if column is already varchar (migration partially ran before)
        if (config('database.default') === 'pgsql') {
            $columnType = DB::selectOne("SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?", [$table, $column]);
            if ($columnType && $columnType->data_type === 'character varying') {
                return;
            }
        } else {
            $columnType = DB::selectOne("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $column]);
            if ($columnType && $columnType->DATA_TYPE === 'varchar') {
                return;
            }
        }

        Schema::table($table, function (Blueprint $table) use ($column, $nullable) {
            $col = $table->string($column, 50);
            if ($nullable) {
                $col->nullable();
            }
            $col->change();
        });

        $valuesStr = "'" . implode("', '", $values) . "'";
        $constraintName = "check_{$table}_{$column}";
        
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE $table DROP CONSTRAINT IF EXISTS $constraintName");
            DB::statement("ALTER TABLE $table ADD CONSTRAINT $constraintName CHECK ($column IN ($valuesStr))");
            if ($default !== null) {
                DB::statement("ALTER TABLE $table ALTER COLUMN $column SET DEFAULT '$default'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For rollback, we just leave them as strings for now to avoid complexity with PG native enums.
    }
};
