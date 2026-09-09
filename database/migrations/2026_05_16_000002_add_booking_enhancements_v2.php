<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPermissions = [
            'bookings.payment.edit',
            'bookings.payment.edit.unlimited',
            'bookings.payment.delete',
            'bookings.payment.delete.unlimited',
            'bookings.charge.delete',
            'bookings.edit.request',
            'bookings.edit.approve',
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($newPermissions);
        }

        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo($newPermissions);
        }

        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->givePermissionTo($newPermissions);
        }

        $foRole = Role::where('name', 'Front Office')->first();
        if ($foRole) {
            $foRole->givePermissionTo([
                'bookings.payment.edit',
                'bookings.payment.delete',
                'bookings.charge.delete',
                'bookings.edit.request',
            ]);
        }

        // booking_edit_requests table
        Schema::create('booking_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_type', 50);
            $table->text('reason');
            $table->json('proposed_changes')->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Add check_in_time and check_out_time to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('check_in_time', 5)->nullable();
            $table->string('check_out_time', 5)->default('12:00');
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'bookings.payment.edit',
            'bookings.payment.edit.unlimited',
            'bookings.payment.delete',
            'bookings.payment.delete.unlimited',
            'bookings.charge.delete',
            'bookings.edit.request',
            'bookings.edit.approve',
        ])->delete();

        Schema::dropIfExists('booking_edit_requests');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
