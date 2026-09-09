<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrar = app()[PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();

        // Create a default Hotel if none exists
        Hotel::firstOrCreate(
            ['name' => 'Main Branch'],
            [
                'code' => 'MAIN',
                'address' => 'Default Address', 
                'phone' => '1234567890'
            ]
        );

        // Temporarily disable teams feature for global roles/permissions
        $registrar->setPermissionsTeamId(null);

        // Create permissions
        $permissions = [
            'manage users', 'manage roles', 'manage system', 'manage reservations', 'manage walk-in', 
            'manage pos', 'manage housekeeping', 'manage maintenance', 'manage procurement', 'view reports', 
            'view analytics', 'book rooms', 'view own bookings',
            'housekeeping.my-tasks', 'housekeeping.dashboard', 'ob.dashboard',
            'manage leaves', 'leaves.request', 'payroll.view-own',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($permissions);
        
        Role::firstOrCreate(['name' => 'Front Office', 'guard_name' => 'web'])->givePermissionTo(['manage reservations', 'manage walk-in', 'manage pos', 'view reports', 'leaves.request']);
        Role::firstOrCreate(['name' => 'Housekeeping', 'guard_name' => 'web'])->givePermissionTo(['manage housekeeping', 'manage maintenance', 'housekeeping.my-tasks', 'housekeeping.dashboard', 'leaves.request']);
        Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web'])->givePermissionTo(['view reports', 'view analytics', 'manage reservations', 'manage procurement', 'manage leaves', 'leaves.request']);
        Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web'])->givePermissionTo(['book rooms', 'view own bookings']);
        Role::firstOrCreate(['name' => 'Purchasing', 'guard_name' => 'web'])->givePermissionTo(['manage procurement']);
        Role::firstOrCreate(['name' => 'Tenant', 'guard_name' => 'web']); // Tenant role for POS - no permissions needed as it uses middleware

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@hotel.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'avatar' => 'avatar-1.jpg',
            ]
        );
        
        $hotel = Hotel::where('code', 'MAIN')->first();
        $admin->hotels()->syncWithoutDetaching([$hotel->id => ['role' => 'Admin']]);

        foreach ($admin->hotels as $h) {
            $registrar->setPermissionsTeamId($h->id);
            if (! $admin->hasRole($adminRole)) {
                $admin->assignRole($adminRole);
            }
        }
        
        // Restore teams feature
        $registrar->setPermissionsTeamId(config('permission.column_names.team_foreign_key'));
        $registrar->forgetCachedPermissions();
    }
}
