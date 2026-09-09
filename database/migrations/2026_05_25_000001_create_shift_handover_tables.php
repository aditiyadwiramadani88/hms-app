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

        $permissions = [
            'handover.create',
            'handover.confirm',
            'handover.view-own',
            'handover.view-all',
            'handover.manage-templates',
            'handover.export',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $adminRoles = Role::whereIn('name', ['Admin', 'Manager', 'General Manager'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        $staffPermissions = ['handover.create', 'handover.confirm', 'handover.view-own'];
        $staffRoles = Role::whereIn('name', ['Front Office', 'Housekeeping', 'OB', 'Security'])->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo($staffPermissions);
        }

        Schema::create('handover_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'name']);
            $table->index(['hotel_id', 'is_active', 'sort_order']);
        });

        Schema::create('shift_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->date('handover_date');
            $table->foreignId('outgoing_shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('incoming_shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('outgoing_employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incoming_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('financial_summary');
            $table->json('transaction_details')->nullable();
            $table->decimal('cash_amount', 15, 2)->nullable();
            $table->boolean('cash_discrepancy')->default(false);
            $table->text('discrepancy_notes')->nullable();
            $table->text('notes')->nullable();
            $table->text('incoming_notes')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'handover_date', 'outgoing_shift_id', 'outgoing_employee_id'], 'shift_handover_unique');
            $table->index(['hotel_id', 'status']);
            $table->index(['hotel_id', 'handover_date']);
            $table->index(['incoming_shift_id', 'handover_date', 'status']);
        });

        Schema::create('shift_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_handover_id')->constrained('shift_handovers')->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->nullable()->constrained('handover_checklist_templates')->nullOnDelete();
            $table->string('item_name', 150);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_checked')->default(false);
            $table->string('value', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('shift_handover_id');
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Schema::dropIfExists('shift_handover_items');
        Schema::dropIfExists('shift_handovers');
        Schema::dropIfExists('handover_checklist_templates');

        Permission::whereIn('name', [
            'handover.create',
            'handover.confirm',
            'handover.view-own',
            'handover.view-all',
            'handover.manage-templates',
            'handover.export',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
