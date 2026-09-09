<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $permission = Permission::firstOrCreate(['name' => 'manage tenants']);

        $frontOffice = Role::where('name', 'like', '%Front Office%')->first();
        if ($frontOffice && !$frontOffice->hasPermissionTo('manage tenants')) {
            $frontOffice->givePermissionTo('manage tenants');
        }
    }

    public function down(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $permission = Permission::where('name', 'manage tenants')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
