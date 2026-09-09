<?php

/**
 * Verify data migration between MySQL and PostgreSQL.
 * Compares row counts and basic schema structure.
 * 
 * Usage: php artisan tinker database/scripts/verify_migration.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$mysqlConn = 'mysql';
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

echo str_pad("Table Name", 30) . " | " . str_pad("MySQL", 10) . " | " . str_pad("PGSQL", 10) . " | Status\n";
echo str_repeat("-", 65) . "\n";

foreach ($tables as $table) {
    if (!Schema::connection($mysqlConn)->hasTable($table)) {
        echo str_pad($table, 30) . " | " . str_pad("N/A", 10) . " | " . str_pad("-", 10) . " | MISSING IN MYSQL\n";
        continue;
    }
    
    if (!Schema::connection($pgsqlConn)->hasTable($table)) {
        echo str_pad($table, 30) . " | " . str_pad("-", 10) . " | " . str_pad("N/A", 10) . " | MISSING IN PGSQL\n";
        continue;
    }

    $mysqlCount = DB::connection($mysqlConn)->table($table)->count();
    $pgsqlCount = DB::connection($pgsqlConn)->table($table)->count();
    
    $status = ($mysqlCount === $pgsqlCount) ? "OK" : "MISMATCH";
    
    echo str_pad($table, 30) . " | " . str_pad($mysqlCount, 10) . " | " . str_pad($pgsqlCount, 10) . " | {$status}\n";
}

echo str_repeat("-", 65) . "\n";
echo "Verification completed.\n";
