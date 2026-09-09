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

        $staffPermissions = ['attendance.check-in', 'attendance.view-own'];

        // Assign to ALL roles that are not Admin/Manager/GM/Guest
        // This covers custom roles like "Front office 3 (branch)", "Housekeeping team", etc.
        $excludeRoles = ['Admin', 'Manager', 'General Manager', 'Guest', 'Tenant'];
        
        $staffRoles = Role::whereNotIn('name', $excludeRoles)->get();
        
        foreach ($staffRoles as $role) {
            $role->givePermissionTo($staffPermissions);
        }

        // Also ensure Admin/Manager/GM have ALL attendance permissions
        $allPermissions = ['attendance.check-in', 'attendance.view-own', 'attendance.view-all', 'attendance.manage-locations', 'attendance.export'];
        $adminRoles = Role::whereIn('name', ['Admin', 'Manager', 'General Manager'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($allPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No rollback needed - permissions stay
    }
};
