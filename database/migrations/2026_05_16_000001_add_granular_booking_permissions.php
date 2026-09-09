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
            'bookings.list',
            'bookings.detail',
            'bookings.create',
            'bookings.edit',
            'bookings.edit.unlimited',
            'bookings.delete',
            'bookings.delete.unlimited',
            'bookings.search',
            'bookings.checkin',
            'bookings.checkout',
            'bookings.cancel',
            'bookings.payment',
            'bookings.discount',
            'bookings.charge',
            'bookings.invoice',
            'bookings.transfer',
            'bookings.extend',
            'bookings.send-wa',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin gets all booking permissions
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // Manager gets all booking permissions (including unlimited)
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo($permissions);
        }

        // General Manager gets all booking permissions
        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->givePermissionTo($permissions);
        }

        // Front Office gets timed permissions (no unlimited, discount, cancel, transfer, extend)
        $foRole = Role::where('name', 'Front Office')->first();
        if ($foRole) {
            $foRole->givePermissionTo([
                'bookings.list',
                'bookings.detail',
                'bookings.create',
                'bookings.edit',
                'bookings.checkin',
                'bookings.checkout',
                'bookings.payment',
                'bookings.charge',
                'bookings.invoice',
                'bookings.search',
                'bookings.send-wa',
            ]);
        }

        // Auto-assign based on legacy permissions for any custom roles
        $legacyEditRoles = Role::where('name', '!=', 'Admin')
            ->where('name', '!=', 'Manager')
            ->where('name', '!=', 'General Manager')
            ->where('name', '!=', 'Front Office')
            ->whereHas('permissions', fn($q) => $q->where('name', 'edit bookings'))
            ->get();
        foreach ($legacyEditRoles as $role) {
            $role->givePermissionTo('bookings.edit.unlimited');
        }

        $legacyDeleteRoles = Role::where('name', '!=', 'Admin')
            ->where('name', '!=', 'Manager')
            ->where('name', '!=', 'General Manager')
            ->where('name', '!=', 'Front Office')
            ->whereHas('permissions', fn($q) => $q->where('name', 'delete transactions'))
            ->get();
        foreach ($legacyDeleteRoles as $role) {
            $role->givePermissionTo('bookings.delete.unlimited');
        }

        $legacyManageRoles = Role::where('name', '!=', 'Admin')
            ->where('name', '!=', 'Manager')
            ->where('name', '!=', 'General Manager')
            ->where('name', '!=', 'Front Office')
            ->whereHas('permissions', fn($q) => $q->where('name', 'manage reservations'))
            ->get();
        foreach ($legacyManageRoles as $role) {
            $role->givePermissionTo([
                'bookings.list',
                'bookings.detail',
                'bookings.create',
                'bookings.checkin',
                'bookings.checkout',
                'bookings.cancel',
                'bookings.payment',
                'bookings.charge',
                'bookings.invoice',
                'bookings.search',
                'bookings.send-wa',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'bookings.list',
            'bookings.detail',
            'bookings.create',
            'bookings.edit',
            'bookings.edit.unlimited',
            'bookings.delete',
            'bookings.delete.unlimited',
            'bookings.search',
            'bookings.checkin',
            'bookings.checkout',
            'bookings.cancel',
            'bookings.payment',
            'bookings.discount',
            'bookings.charge',
            'bookings.invoice',
            'bookings.transfer',
            'bookings.extend',
            'bookings.send-wa',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
