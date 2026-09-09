<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permission = 'reports.daily.approve';

        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

        $roles = ['Super Admin', 'Admin', 'Manager', 'General Manager'];
        foreach ($roles as $roleName) {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        \Spatie\Permission\Models\Permission::where('name', 'reports.daily.approve')->delete();
    }
};
