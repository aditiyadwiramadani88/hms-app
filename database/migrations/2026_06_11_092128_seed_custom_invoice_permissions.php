<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'custom-invoices.list',
            'custom-invoices.create',
            'custom-invoices.edit',
            'custom-invoices.delete',
            'custom-invoices.mark-paid',
            'custom-invoices.print',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRoles = ['Super Admin', 'Admin', 'Manager', 'General Manager'];
        $foRoles = ['Front Office'];

        foreach ($adminRoles as $roleName) {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        $foPermissions = [
            'custom-invoices.list',
            'custom-invoices.create',
            'custom-invoices.edit',
            'custom-invoices.print',
        ];

        foreach ($foRoles as $roleName) {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($foPermissions);
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            'custom-invoices.list',
            'custom-invoices.create',
            'custom-invoices.edit',
            'custom-invoices.delete',
            'custom-invoices.mark-paid',
            'custom-invoices.print',
        ];

        \Spatie\Permission\Models\Permission::whereIn('name', $permissions)->delete();
    }
};
