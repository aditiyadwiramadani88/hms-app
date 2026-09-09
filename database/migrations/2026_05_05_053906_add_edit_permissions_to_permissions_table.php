<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        $permissions = [
            'edit payments',
            'edit bookings',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Assign to Admin and GM by default
        $roles = Role::whereIn('name', ['Admin', 'GM', 'General Manager'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ['edit payments', 'edit bookings'])->delete();
    }
};
