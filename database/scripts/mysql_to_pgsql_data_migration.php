<?php

/**
 * Migrate data from MySQL to PostgreSQL.
 * Run this after creating the PG schema via migrations.
 * 
 * Usage: php artisan tinker database/scripts/mysql_to_pgsql_data_migration.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Configuration
$mysqlConn = 'mysql'; // Ensure this points to your source MySQL in config/database.php
$pgsqlConn = 'pgsql'; // Your target PostgreSQL

$tables = [
    'users',
    'hotels',
    'customer_types',
    'room_types',
    'rooms',
    'guest_categories',
    'guests',
    'bank_accounts',
    'transaction_categories',
    'booking_sources',
    'vouchers',
    'bookings',
    'transactions',
    'pos_orders',
    'pos_order_items',
    'employees',
    'shifts',
    'attendances',
    'employee_schedules',
    'inventories',
    'inventory_categories',
    'inventory_mutations',
    'suppliers',
    'purchases',
    'purchase_items',
    'assets',
    'asset_categories',
    'asset_histories',
    'tenants',
    'tenant_products',
    'tenant_transactions',
    'tenant_transaction_items',
    'tenant_billings',
    'cleaning_tasks',
    'cleaning_task_logs',
    'cleaning_task_photos',
    'cleaning_checklist_templates',
    'cleaning_checklist_items',
    'work_orders',
    'maintenance_logs',
    'ob_bonus_settings',
    'shift_handovers',
    'shift_handover_items',
    'vehicle_logs',
    'guest_vehicles',
];

echo "Starting MySQL to PostgreSQL data migration...\n";

// Disable foreign key checks for the session if possible
if (config("database.connections.$pgsqlConn.driver") === 'pgsql') {
    DB::connection($pgsqlConn)->statement("SET session_replication_role = 'replica';");
}

foreach ($tables as $table) {
    if (!Schema::connection($mysqlConn)->hasTable($table)) {
        echo "SKIP: Table '{$table}' not found in source.\n";
        continue;
    }

    echo "MIGRATING: {$table}...\n";
    
    // Clear target table
    DB::connection($pgsqlConn)->table($table)->delete();
    
    $count = 0;
    DB::connection($mysqlConn)->table($table)->orderBy('id')->chunk(500, function ($rows) use ($pgsqlConn, $table, &$count) {
        $data = json_decode(json_encode($rows), true);
        
        // Sanitize data for PG
        foreach ($data as &$row) {
            foreach ($row as $key => $value) {
                // Convert 0/1 to boolean for PG boolean columns
                // This is a bit generic, ideally we'd check column types
                if ($value === 0 && str_starts_with($key, 'is_')) $row[$key] = false;
                if ($value === 1 && str_starts_with($key, 'is_')) $row[$key] = true;
            }
        }
        
        DB::connection($pgsqlConn)->table($table)->insert($data);
        $count += count($data);
    });
    
    echo "SUCCESS: Migrated {$count} rows for '{$table}'.\n";
}

// Re-enable foreign key checks
if (config("database.connections.$pgsqlConn.driver") === 'pgsql') {
    DB::connection($pgsqlConn)->statement("SET session_replication_role = 'origin';");
}

echo "Data migration completed. Please run the sequence reset script next.\n";
