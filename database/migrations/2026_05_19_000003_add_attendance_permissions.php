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
            'attendance.check-in',
            'attendance.view-own',
            'attendance.view-all',
            'attendance.manage-locations',
            'attendance.export',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin, Manager, General Manager get all attendance permissions
        $adminRoles = Role::whereIn('name', ['Admin', 'Manager', 'General Manager'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Staff roles get check-in and view-own
        $staffPermissions = ['attendance.check-in', 'attendance.view-own'];
        $staffRoles = Role::whereIn('name', ['Housekeeping', 'OB', 'Front Office', 'Front Page Only'])->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo($staffPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'attendance.check-in',
            'attendance.view-own',
            'attendance.view-all',
            'attendance.manage-locations',
            'attendance.export',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
