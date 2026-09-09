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

        $permissions = [
            'security.vehicle-gate',
            'security.vehicle-log',
            'security.vehicle-report',
            'security.manage-vehicles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin, Manager, General Manager get all security permissions
        $adminRoles = Role::whereIn('name', ['Admin', 'Manager', 'General Manager'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Front Office gets report and manage-vehicles
        $foRole = Role::where('name', 'Front Office')->first();
        if ($foRole) {
            $foRole->givePermissionTo(['security.vehicle-report', 'security.manage-vehicles']);
        }

        // Create Security role if it doesn't exist, assign vehicle-gate and vehicle-log
        $securityRole = Role::firstOrCreate(['name' => 'Security', 'guard_name' => 'web']);
        $securityRole->givePermissionTo(['security.vehicle-gate', 'security.vehicle-log']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'security.vehicle-gate',
            'security.vehicle-log',
            'security.vehicle-report',
            'security.manage-vehicles',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
