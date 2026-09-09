<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixAdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin.users permission if it doesn't exist
        $adminUsersPerm = Permission::firstOrCreate(
            ['name' => 'admin.users', 'guard_name' => 'web']
        );

        // Create admin.roles permission if it doesn't exist
        $adminRolesPerm = Permission::firstOrCreate(
            ['name' => 'admin.roles', 'guard_name' => 'web']
        );

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($adminUsersPerm);
            $adminRole->givePermissionTo($adminRolesPerm);

            // Also assign all granular permissions if they exist
            $allPerms = Permission::where('name', 'like', '%.%')->get();
            foreach ($allPerms as $perm) {
                if (!$adminRole->hasPermissionTo($perm)) {
                    $adminRole->givePermissionTo($perm);
                }
            }
        }

        // Assign to Super Admin role if exists
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(Permission::all());
        }

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
