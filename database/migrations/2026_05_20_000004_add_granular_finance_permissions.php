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

        $newPermissions = [
            'finance.income',        // Create income (deposit)
            'finance.expense',       // Create expense (withdraw)
            'finance.edit',          // Edit transactions
            'finance.delete',        // Delete transactions
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin gets all
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($newPermissions);
        }

        // Manager/GM gets all
        $managerRoles = Role::whereIn('name', ['Manager', 'General Manager'])->get();
        foreach ($managerRoles as $role) {
            $role->givePermissionTo($newPermissions);
        }

        // Front Office roles get income + expense (not edit/delete)
        $foRoles = Role::where('name', 'like', '%Front%')->get();
        foreach ($foRoles as $role) {
            $role->givePermissionTo(['finance.income', 'finance.expense']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'finance.income',
            'finance.expense',
            'finance.edit',
            'finance.delete',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
