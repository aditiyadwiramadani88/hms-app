<?php

/**
 * Export PostgreSQL data to MySQL-compatible SQL dump.
 * Used as rollback mechanism if PG deployment fails.
 * 
 * Usage: php artisan tinker database/scripts/export_pgsql_to_mysql.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

$pgsqlConn = 'pgsql';
$tables = [
    'users', 'hotels', 'customer_types', 'room_types', 'rooms', 
    'guest_categories', 'guests', 'bank_accounts', 'transaction_categories', 
    'booking_sources', 'vouchers', 'bookings', 'transactions', 
    'pos_orders', 'pos_order_items', 'employees', 'shifts', 
    'attendances', 'employee_schedules', 'inventories', 
    'inventory_categories', 'inventory_mutations', 'suppliers', 
    'purchases', 'purchase_items', 'assets', 'asset_categories', 
    'asset_histories', 'tenants', 'tenant_products', 
    'tenant_transactions', 'tenant_transaction_items', 'tenant_billings', 
    'cleaning_tasks', 'cleaning_task_logs', 'cleaning_task_photos', 
    'cleaning_checklist_templates', 'cleaning_checklist_items', 
    'work_orders', 'maintenance_logs', 'ob_bonus_settings', 
    'shift_handovers', 'shift_handover_items', 'vehicle_logs', 'guest_vehicles'
];

$filename = 'mysql_rollback_' . date('Ymd_His') . '.sql';
$handle = fopen(storage_path('app/' . $filename), 'w');

fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

foreach ($tables as $table) {
    if (!Schema::connection($pgsqlConn)->hasTable($table)) {
        continue;
    }

    echo "Exporting '{$table}'...\n";
    fwrite($handle, "-- Table: {$table}\n");
    fwrite($handle, "TRUNCATE TABLE `{$table}`;\n");

    DB::connection($pgsqlConn)->table($table)->orderBy('id')->chunk(500, function ($rows) use ($handle, $table) {
        foreach ($rows as $row) {
            $data = (array) $row;
            $columns = array_keys($data);
            $values = array_values($data);

            $formattedValues = array_map(function ($value) {
                if ($value === null) return 'NULL';
                if (is_bool($value)) return $value ? '1' : '0';
                if (is_numeric($value)) return $value;
                return "'" . str_replace("'", "''", $value) . "'";
            }, $values);

            $sql = "INSERT INTO `{$table}` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $formattedValues) . ");\n";
            fwrite($handle, $sql);
        }
    });

    fwrite($handle, "\n");
}

fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($handle);

echo "Export completed: " . storage_path('app/' . $filename) . "\n";
