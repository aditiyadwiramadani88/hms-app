<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        Permission::firstOrCreate(['name' => 'bookings.checkin.force']);

        foreach (['Admin', 'Manager'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo('bookings.checkin.force')) {
                $role->givePermissionTo('bookings.checkin.force');
            }
        }
    }

    public function down(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $permission = Permission::where('name', 'bookings.checkin.force')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
