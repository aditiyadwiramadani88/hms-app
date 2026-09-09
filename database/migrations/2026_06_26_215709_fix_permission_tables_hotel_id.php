<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $hotelIdColumn = 'hotel_id';

        // Check if team_id column exists and hotel_id doesn't (wrong migration was run)
        if (Schema::hasColumn($tableNames['model_has_roles'], 'team_id') &&
            !Schema::hasColumn($tableNames['model_has_roles'], 'hotel_id')) {

            // Rename team_id to hotel_id in model_has_roles
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->dropForeign(['role_id']);
                $table->dropPrimary();
                $table->dropIndex('model_has_roles_team_foreign_key_index');

                // Rename column
                $table->renameColumn('team_id', $hotelIdColumn);
                $table->index($hotelIdColumn, 'model_has_roles_hotel_id_index');

                // Update primary key
                $table->primary([$hotelIdColumn, 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');

                // Re-add FK
                $table->foreign('role_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');
            });
        }

        if (Schema::hasColumn($tableNames['model_has_permissions'], 'team_id') &&
            !Schema::hasColumn($tableNames['model_has_permissions'], 'hotel_id')) {

            // Rename team_id to hotel_id in model_has_permissions
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->dropForeign(['permission_id']);
                $table->dropPrimary();
                $table->dropIndex('model_has_permissions_team_foreign_key_index');

                // Rename column
                $table->renameColumn('team_id', $hotelIdColumn);
                $table->index($hotelIdColumn, 'model_has_permissions_hotel_id_index');

                // Update primary key
                $table->primary([$hotelIdColumn, 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');

                // Re-add FK
                $table->foreign('permission_id')->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            });
        }

        // If neither column exists, add hotel_id
        if (!Schema::hasColumn($tableNames['model_has_roles'], 'hotel_id')) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->dropForeign(['role_id']);
                $table->dropPrimary();

                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);

                $table->primary([$hotelIdColumn, 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');

                $table->foreign('role_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');
            });
        }

        if (!Schema::hasColumn($tableNames['model_has_permissions'], 'hotel_id')) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->dropForeign(['permission_id']);
                $table->dropPrimary();

                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);

                $table->primary([$hotelIdColumn, 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');

                $table->foreign('permission_id')->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            });
        }

        // Also fix roles table if needed
        if (Schema::hasColumn($tableNames['roles'], 'team_id') && !Schema::hasColumn($tableNames['roles'], 'hotel_id')) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->dropUnique([$tableNames['roles'] . '_name_guard_name_unique']);
                $table->renameColumn('team_id', $hotelIdColumn);
                $table->unique([$hotelIdColumn, 'name', 'guard_name'], $tableNames['roles'] . '_hotel_id_name_guard_name_unique');
            });
        } elseif (!Schema::hasColumn($tableNames['roles'], 'hotel_id')) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($hotelIdColumn, $tableNames) {
                $table->bigInteger($hotelIdColumn)->nullable();
                $table->index($hotelIdColumn);

                $table->dropUnique([$tableNames['roles'] . '_name_guard_name_unique']);
                $table->unique([$hotelIdColumn, 'name', 'guard_name'], $tableNames['roles'] . '_hotel_id_name_guard_name_unique');
            });
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // This migration is not reversible in a safe way
    }
};
