<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * housekeeping.update-status (the quick-manage Clean/Dirty/etc control on the
 * HK room grid) was gated by 'manage housekeeping', which "Housekeeping team"
 * (regular staff) also holds -- so any HK staff could freely change a room's
 * status, not just Admin/Kepala Housekeeping. New, narrower permission for
 * that one action; the route itself ORs it with 'manage system' so Admin
 * doesn't need an explicit grant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'rooms.status.change', 'guard_name' => 'web']);

        $leader = Role::where('name', 'Housekeeping leader')->first();
        if ($leader && !$leader->hasPermissionTo('rooms.status.change')) {
            $leader->givePermissionTo('rooms.status.change');
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'rooms.status.change')->first();
        $permission?->delete();
    }
};
