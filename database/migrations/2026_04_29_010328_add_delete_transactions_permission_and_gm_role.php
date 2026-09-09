<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the permission
        $permission = Permission::firstOrCreate(['name' => 'delete transactions', 'guard_name' => 'web']);

        // Create General Manager role if it doesn't exist
        $gmRole = Role::firstOrCreate(['name' => 'General Manager', 'guard_name' => 'web']);
        
        // Find existing roles
        $adminRole = Role::where('name', 'Admin')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        // Assign permission to roles
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        if ($managerRole) {
            $managerRole->givePermissionTo($permission);
        }

        // GM gets everything Manager has + delete transactions
        if ($gmRole) {
            if ($managerRole) {
                $gmRole->syncPermissions($managerRole->permissions);
            }
            $gmRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        $permission = Permission::where('name', 'delete transactions')->first();
        if ($permission) {
            $permission->delete();
        }

        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->delete();
        }
    }
};
