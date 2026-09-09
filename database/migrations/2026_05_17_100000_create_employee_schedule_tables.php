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

        // ========== Permissions ==========
        $permissions = [
            'schedules.view',
            'schedules.create',
            'schedules.manage-shifts',
            'schedules.manage-locations',
            'schedules.approve-swap',
            'schedules.my-schedule',
            'schedules.request-swap',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo($permissions);
        }

        $gmRole = Role::where('name', 'General Manager')->first();
        if ($gmRole) {
            $gmRole->givePermissionTo($permissions);
        }

        // Karyawan/staff roles get my-schedule and request-swap
        $staffPermissions = ['schedules.my-schedule', 'schedules.request-swap'];
        $staffRoles = Role::whereIn('name', ['Housekeeping', 'OB', 'Front Office', 'Front Page Only'])->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo($staffPermissions);
        }

        // ========== shifts ==========
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->string('color', 10)->default('#28a745');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('start_time_2')->nullable();
            $table->time('end_time_2')->nullable();
            $table->boolean('is_off')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ========== schedule_locations ==========
        Schema::create('schedule_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ========== employee_schedules ==========
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('schedule_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('location', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->decimal('overtime_hours', 4, 1)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hotel_id', 'employee_id', 'schedule_date'], 'emp_schedule_unique');
        });

        // ========== shift_swap_requests ==========
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_schedule_id')->constrained('employee_schedules')->cascadeOnDelete();
            $table->foreignId('target_schedule_id')->constrained('employee_schedules')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('schedule_locations');
        Schema::dropIfExists('shifts');

        Permission::whereIn('name', [
            'schedules.view',
            'schedules.create',
            'schedules.manage-shifts',
            'schedules.manage-locations',
            'schedules.approve-swap',
            'schedules.my-schedule',
            'schedules.request-swap',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
