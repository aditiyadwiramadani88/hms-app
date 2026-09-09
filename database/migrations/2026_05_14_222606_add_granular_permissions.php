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

        // New granular permissions grouped by module
        $newPermissions = [
            // Housekeeping module
            'housekeeping.dashboard',
            'housekeeping.my-tasks',
            'housekeeping.assign-staff',
            'housekeeping.checker',
            'housekeeping.work-orders',
            'housekeeping.templates',

            // Front Office module
            'frontoffice.bookings',
            'frontoffice.guests',
            'frontoffice.pos',
            'frontoffice.calendar',
            'frontoffice.walk-in',

            // Reports module
            'reports.revenue',
            'reports.occupancy',
            'reports.transactions',
            'reports.analytics',
            'reports.bonus',

            // Finance module
            'finance.bank-accounts',
            'finance.categories',

            // Administration module
            'admin.users',
            'admin.roles',
            'admin.settings',
            'admin.booking-sources',

            // System / Master Data
            'system.rooms',
            'system.room-types',
            'system.room-rates',
            'system.employees',
            'system.suppliers',
            'system.vouchers',
            'system.guest-categories',
            'system.inventory',

            // Procurement
            'procurement.purchases',

            // Assets
            'assets.manage',
            'assets.categories',

            // Tenants
            'tenants.manage',
            'tenants.billing',
            'tenants.monitoring',
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Auto-assign granular permissions to existing roles based on their parent permissions
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($newPermissions);
        }

        $hkRole = Role::where('name', 'Housekeeping')->first();
        if ($hkRole) {
            $hkRole->givePermissionTo([
                'housekeeping.my-tasks',
                'housekeeping.dashboard',
                'housekeeping.assign-staff',
                'housekeeping.checker',
                'housekeeping.work-orders',
                'housekeeping.templates',
                'frontoffice.bookings',
                'frontoffice.pos',
                'frontoffice.calendar',
            ]);
        }

        $foRole = Role::where('name', 'Front Office')->first();
        if ($foRole) {
            $foRole->givePermissionTo([
                'frontoffice.bookings',
                'frontoffice.guests',
                'frontoffice.pos',
                'frontoffice.calendar',
                'frontoffice.walk-in',
            ]);
        }

        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'frontoffice.bookings',
                'frontoffice.calendar',
                'reports.revenue',
                'reports.occupancy',
                'reports.transactions',
                'reports.analytics',
                'procurement.purchases',
            ]);
        }

        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->givePermissionTo([
                'frontoffice.bookings',
                'frontoffice.calendar',
                'reports.revenue',
                'reports.occupancy',
                'reports.transactions',
                'reports.analytics',
                'procurement.purchases',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permsToDelete = Permission::where('name', 'like', '%.%')->get();
        foreach ($permsToDelete as $perm) {
            $perm->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
