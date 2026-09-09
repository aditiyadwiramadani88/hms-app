<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class TenantController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $tenants = Tenant::with('users')
            ->when(request('search'), fn($q) => $q->where('name', 'like', '%' . request('search') . '%'))
            ->when(request('status'), fn($q) => $q->where('is_active', request('status') === 'active'))
            ->paginate(25);

        return view('tenant-admin.index', compact('tenants'));
    }

    public function create()
    {
        return view('tenant-admin.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location_description' => 'nullable|string|max:255',
            'rent_amount' => 'required|numeric|min:0',
            'rent_due_day' => 'required|integer|between:1,28',
            'contract_start' => 'required|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tenant = Tenant::create($request->only([
            'name', 'owner_name', 'phone', 'email', 'location_description',
            'rent_amount', 'rent_due_day', 'contract_start', 'contract_end', 'notes'
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return $this->ajaxOrRedirect('Tenant created successfully.', route('admin.tenants.show', $tenant), $tenant, 201);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['products', 'billings', 'users', 'transactions' => fn($q) => $q->latest()->take(10)]);

        return view('tenant-admin.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        return view('tenant-admin.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location_description' => 'nullable|string|max:255',
            'rent_amount' => 'required|numeric|min:0',
            'rent_due_day' => 'required|integer|between:1,28',
            'contract_start' => 'required|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tenant->update($request->only([
            'name', 'owner_name', 'phone', 'email', 'location_description',
            'rent_amount', 'rent_due_day', 'contract_start', 'contract_end', 'notes'
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return $this->ajaxOrRedirect('Tenant updated successfully.', route('admin.tenants.show', $tenant), $tenant);
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->transactions()->exists()) {
            $errorMessage = 'Cannot delete tenant with existing transactions.';
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($errorMessage);
            }
            return redirect()->back()->with('error', $errorMessage);
        }

        $tenant->delete();

        return $this->ajaxOrRedirect('Tenant deleted successfully.', route('admin.tenants.index'));
    }

    public function createUser(Tenant $tenant)
    {
        return view('tenant-admin.create-user', compact('tenant'));
    }

    public function storeUser(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        $tenantRole = Role::where('name', 'Tenant')->first();
        if ($tenantRole) {
            $user->assignRole($tenantRole);
        }

        return $this->ajaxOrRedirect('Tenant user created successfully.', route('admin.tenants.show', $tenant), $user, 201);
    }
}
