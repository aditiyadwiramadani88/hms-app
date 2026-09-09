<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the kost report permission
        Permission::firstOrCreate(['name' => 'reports.kost', 'guard_name' => 'web']);

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo('reports.kost');
        }

        // Assign to Manager role
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo('reports.kost');
        }

        // Assign to General Manager role
        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->givePermissionTo('reports.kost');
        }

        // Assign to Front Office role (they usually manage kost too)
        $foRole = Role::where('name', 'Front Office')->first();
        if ($foRole) {
            $foRole->givePermissionTo('reports.kost');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'reports.kost')->first();
        if ($permission) {
            $permission->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
