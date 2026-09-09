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
        $tables = [
            'room_types',
            'rooms',
            'guests',
            'bookings',
            'transactions',
            'pos_orders',
            'pos_order_items',
            'inventories',
            'maintenance_logs',
            'vouchers',
            'audit_logs'
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasColumn($tableName, 'hotel_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('cascade');
                });
            }
        }

        // Spatie Permission Teams support
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $hotelIdColumn = $columnNames['team_foreign_key'] ?? 'hotel_id';

        // Add to roles
        if (!Schema::hasColumn($tableNames['roles'], $hotelIdColumn)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($hotelIdColumn) {
                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);
                
                // Update unique constraint
                $table->dropUnique(['name', 'guard_name']);
                $table->unique([$hotelIdColumn, 'name', 'guard_name']);
            });
        }

        // Add to model_has_roles
        if (!Schema::hasColumn($tableNames['model_has_roles'], $hotelIdColumn)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                // Drop FK first to allow changing PK
                $table->dropForeign(['role_id']);
                
                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);
                
                $table->dropPrimary();
                $table->primary([$hotelIdColumn, 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
                
                // Re-add FK
                $table->foreign('role_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');
            });
        }

        // Add to model_has_permissions
        if (!Schema::hasColumn($tableNames['model_has_permissions'], $hotelIdColumn)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                // Drop FK first
                $table->dropForeign(['permission_id']);
                
                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);
                
                $table->dropPrimary();
                $table->primary([$hotelIdColumn, 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
                
                // Re-add FK
                $table->foreign('permission_id')->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'room_types',
            'rooms',
            'guests',
            'bookings',
            'transactions',
            'pos_orders',
            'pos_order_items',
            'inventories',
            'maintenance_logs',
            'vouchers',
            'audit_logs'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['hotel_id']);
                $table->dropColumn('hotel_id');
            });
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $hotelIdColumn = $columnNames['team_foreign_key'] ?? 'hotel_id';

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($hotelIdColumn) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            $table->dropColumn($hotelIdColumn);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($hotelIdColumn) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            $table->dropColumn($hotelIdColumn);
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($hotelIdColumn) {
            $table->dropUnique([$hotelIdColumn, 'name', 'guard_name']);
            $table->unique(['name', 'guard_name']);
            $table->dropColumn($hotelIdColumn);
        });
    }
};
