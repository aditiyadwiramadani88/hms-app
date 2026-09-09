<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of all users.
     */
    public function users(Request $request)
    {
        $query = User::with('roles', 'hotels')->withCount('bookings');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }

        if ($request->filled('hotel_id')) {
            $hotel_id = $request->hotel_id;
            $query->whereHas('hotels', function($q) use ($hotel_id) {
                $q->where('hotels.id', $hotel_id);
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $hotels = Hotel::all();
        $roles = Role::all();

        return view('admin.users', compact('users', 'hotels', 'roles'));
    }

    public function storeUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|exists:roles,name',
                'hotels' => 'required|array',
                'hotels.*' => 'exists:hotels,id',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'avatar' => 'avatar-1.jpg',
            ]);

            $user->assignRole($validated['role']);
            $user->hotels()->attach($validated['hotels'], ['role' => $validated['role']]);

            return $this->ajaxOrRedirect('User created successfully.', route('admin.users'), $user);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function updateUser(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8',
                'role' => 'required|string|exists:roles,name',
                'hotels' => 'required|array',
                'hotels.*' => 'exists:hotels,id',
            ]);

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_ban' => $request->boolean('is_ban'),
                'is_force_logout' => $request->boolean('is_force_logout'),
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $user->syncRoles($validated['role']);
            $user->hotels()->sync($validated['hotels']);

            return $this->ajaxOrRedirect('User updated successfully.', route('admin.users'), $user);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function destroyUser(User $user)
    {
        try {
            $user->delete();

            return $this->ajaxOrRedirect('User deleted successfully.', route('admin.users'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function assignHotel(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|array',
                'hotel_id.*' => 'exists:hotels,id',
            ]);

            foreach ($validated['hotel_id'] as $hotelId) {
                if (! $user->hotels()->where('hotel_id', $hotelId)->exists()) {
                    $user->hotels()->attach($hotelId, ['role' => 'Staff']);
                }
            }

            return $this->ajaxOrRedirect('Hotel access assigned successfully.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function removeHotel(User $user, Hotel $hotel)
    {
        try {
            $user->hotels()->detach($hotel->id);

            return $this->ajaxOrRedirect('Hotel access removed successfully.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Display roles and permissions.
     */
    public function roles()
    {
        $roles = Role::with('permissions')->withCount('users')->get();
        $permissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();

        // Group permissions by module for tree display
        $permissionGroups = $this->getPermissionGroups($permissions);

        return view('admin.roles', compact('roles', 'permissions', 'permissionGroups'));
    }

    /**
     * Group permissions into categories for tree UI with sub-groups matching sidebar structure.
     */
    private function getPermissionGroups($permissions): array
    {
        $groups = [
            'Housekeeping' => [
                'icon' => 'ri-building-4-line',
                'color' => 'success',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Front Office' => [
                'icon' => 'ri-hotel-line',
                'color' => 'primary',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Reports & Analytics' => [
                'icon' => 'ri-bar-chart-2-line',
                'color' => 'info',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Finance' => [
                'icon' => 'ri-bank-card-line',
                'color' => 'warning',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Administration' => [
                'icon' => 'ri-admin-line',
                'color' => 'danger',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Master Data' => [
                'icon' => 'ri-database-2-line',
                'color' => 'secondary',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Procurement & Assets' => [
                'icon' => 'ri-shopping-bag-line',
                'color' => 'dark',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Tenants' => [
                'icon' => 'ri-store-2-line',
                'color' => 'purple',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Attendance' => [
                'icon' => 'ri-fingerprint-line',
                'color' => 'teal',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Security' => [
                'icon' => 'ri-shield-check-line',
                'color' => 'warning',
                'permissions' => [],
                'subgroups' => [],
            ],
            'Other' => [
                'icon' => 'ri-more-line',
                'color' => 'light',
                'permissions' => [],
                'subgroups' => [],
            ],
        ];

        // Define sub-group structure matching sidebar
        $subgroupMapping = [
            // Master Data sub-groups (matching sidebar)
            'system.room-types' => ['Master Data', 'Rooms'],
            'system.rooms' => ['Master Data', 'Rooms'],
            'system.room-rates' => ['Master Data', 'Rooms'],
            'housekeeping.templates' => ['Master Data', 'Rooms'],
            'system.employees' => ['Master Data', null],
            'system.suppliers' => ['Master Data', null],
            'system.guest-categories' => ['Master Data', null],
            'system.vouchers' => ['Master Data', null],
            'system.inventory' => ['Master Data', 'Point of Sale'],
            // Front Office sub-groups
            'frontoffice.bookings' => ['Front Office', 'Bookings'],
            'frontoffice.calendar' => ['Front Office', 'Bookings'],
            'frontoffice.guests' => ['Front Office', null],
            'frontoffice.pos' => ['Front Office', 'POS'],
            'frontoffice.walk-in' => ['Front Office', null],
            // Front Office > Bookings granular permissions
            'bookings.list' => ['Front Office', 'Bookings'],
            'bookings.detail' => ['Front Office', 'Bookings'],
            'bookings.create' => ['Front Office', 'Bookings'],
            'bookings.edit' => ['Front Office', 'Bookings'],
            'bookings.edit.unlimited' => ['Front Office', 'Bookings'],
            'bookings.edit.request' => ['Front Office', 'Bookings'],
            'bookings.edit.approve' => ['Front Office', 'Bookings'],
            'bookings.delete' => ['Front Office', 'Bookings'],
            'bookings.delete.unlimited' => ['Front Office', 'Bookings'],
            'bookings.search' => ['Front Office', 'Bookings'],
            'bookings.checkin' => ['Front Office', 'Bookings'],
            'bookings.checkout' => ['Front Office', 'Bookings'],
            'bookings.cancel' => ['Front Office', 'Bookings'],
            'bookings.payment' => ['Front Office', 'Bookings'],
            'bookings.payment.edit' => ['Front Office', 'Bookings'],
            'bookings.payment.edit.unlimited' => ['Front Office', 'Bookings'],
            'bookings.payment.delete' => ['Front Office', 'Bookings'],
            'bookings.payment.delete.unlimited' => ['Front Office', 'Bookings'],
            'bookings.discount' => ['Front Office', 'Bookings'],
            'bookings.charge' => ['Front Office', 'Bookings'],
            'bookings.charge.delete' => ['Front Office', 'Bookings'],
            'bookings.invoice' => ['Front Office', 'Bookings'],
            'bookings.transfer' => ['Front Office', 'Bookings'],
            'bookings.extend' => ['Front Office', 'Bookings'],
            'bookings.send-wa' => ['Front Office', 'Bookings'],
            // Housekeeping sub-groups
            'housekeeping.dashboard' => ['Housekeeping', null],
            'housekeeping.assign-staff' => ['Housekeeping', null],
            'housekeeping.my-tasks' => ['Housekeeping', null],
            'housekeeping.checker' => ['Housekeeping', null],
            'housekeeping.work-orders' => ['Housekeeping', null],
            // Procurement sub-groups
            'procurement.purchases' => ['Procurement & Assets', null],
            'assets.manage' => ['Procurement & Assets', 'Assets'],
            'assets.categories' => ['Procurement & Assets', 'Assets'],
            // Tenants sub-groups
            'tenants.manage' => ['Tenants', null],
            'tenants.billing' => ['Tenants', null],
            'tenants.monitoring' => ['Tenants', null],
            // Attendance sub-groups
            'attendance.check-in' => ['Attendance', null],
            'attendance.view-own' => ['Attendance', null],
            'attendance.view-all' => ['Attendance', null],
            'attendance.manage-locations' => ['Attendance', null],
            'attendance.export' => ['Attendance', null],
            // Security sub-groups
            'security.vehicle-gate' => ['Security', null],
            'security.vehicle-log' => ['Security', null],
            'security.vehicle-report' => ['Security', null],
            'security.manage-vehicles' => ['Security', null],
            // Reports sub-groups
            'reports.revenue' => ['Reports & Analytics', null],
            'reports.occupancy' => ['Reports & Analytics', null],
            'reports.transactions' => ['Reports & Analytics', null],
            'reports.bonus' => ['Reports & Analytics', null],
            'reports.analytics' => ['Reports & Analytics', null],
            'reports.kost' => ['Reports & Analytics', null],
            // Finance sub-groups
            'finance.bank-accounts' => ['Finance', null],
            'finance.categories' => ['Finance', null],
            'finance.income' => ['Finance', null],
            'finance.expense' => ['Finance', null],
            'finance.edit' => ['Finance', null],
            'finance.delete' => ['Finance', null],
            // Admin sub-groups
            'admin.users' => ['Administration', null],
            'admin.roles' => ['Administration', null],
            'admin.booking-sources' => ['Administration', null],
            'admin.settings' => ['Administration', null],
        ];

        // Legacy permissions mapping (no sub-group)
        $legacyMapping = [
            'manage housekeeping' => 'Housekeeping',
            'manage maintenance' => 'Housekeeping',
            'manage reservations' => 'Front Office',
            'manage walk-in' => 'Front Office',
            'manage pos' => 'Front Office',
            'view reports' => 'Reports & Analytics',
            'view analytics' => 'Reports & Analytics',
            'manage users' => 'Administration',
            'manage roles' => 'Administration',
            'manage system' => 'Master Data',
            'manage procurement' => 'Procurement & Assets',
            'book rooms' => 'Front Office',
            'view own bookings' => 'Front Office',
            'delete transactions' => 'Finance',
            'edit payments' => 'Finance',
            'edit bookings' => 'Front Office',
        ];

        // Display names for permissions
        $displayNames = [
            'manage system' => 'Manage System (All Access)',
            'system.room-types' => 'Room Types',
            'system.rooms' => 'List Rooms',
            'system.room-rates' => 'Pricing Rooms',
            'system.employees' => 'Employees',
            'system.suppliers' => 'Suppliers',
            'system.guest-categories' => 'Guests Categories',
            'system.vouchers' => 'Vouchers',
            'system.inventory' => 'Inventory',
            'manage reservations' => 'Manage Reservations (All Access)',
            'frontoffice.bookings' => 'Bookings',
            'frontoffice.calendar' => 'Calendar',
            'frontoffice.guests' => 'Guests',
            'frontoffice.pos' => 'Point of Sale',
            'frontoffice.walk-in' => 'Walk-in',
            'manage pos' => 'Manage POS',
            'manage walk-in' => 'Manage Walk-in',
            'book rooms' => 'Book Rooms',
            'view own bookings' => 'View Own Bookings',
            'edit bookings' => 'Edit Bookings',
            'bookings.list' => 'Lihat Daftar Booking',
            'bookings.detail' => 'Lihat Detail Booking',
            'bookings.create' => 'Buat Booking Baru',
            'bookings.edit' => 'Edit Booking (24 jam)',
            'bookings.edit.unlimited' => 'Edit Booking (Tanpa Batas)',
            'bookings.edit.request' => 'Request Edit Booking',
            'bookings.edit.approve' => 'Approve/Reject Edit Request',
            'bookings.delete' => 'Hapus Booking (24 jam)',
            'bookings.delete.unlimited' => 'Hapus Booking (Tanpa Batas)',
            'bookings.search' => 'Search Booking (Navbar)',
            'bookings.checkin' => 'Check-in Tamu',
            'bookings.checkout' => 'Check-out Tamu',
            'bookings.cancel' => 'Cancel Booking',
            'bookings.payment' => 'Tambah Payment',
            'bookings.payment.edit' => 'Edit Payment (24 jam)',
            'bookings.payment.edit.unlimited' => 'Edit Payment (Tanpa Batas)',
            'bookings.payment.delete' => 'Hapus Payment (24 jam)',
            'bookings.payment.delete.unlimited' => 'Hapus Payment (Tanpa Batas)',
            'bookings.discount' => 'Apply Discount',
            'bookings.charge' => 'Tambah Charge/Item',
            'bookings.charge.delete' => 'Hapus Custom Charge',
            'bookings.invoice' => 'Print Invoice',
            'bookings.transfer' => 'Pindah Kamar',
            'bookings.extend' => 'Perpanjang Booking',
            'bookings.send-wa' => 'Kirim WA Invoice',
            'manage housekeeping' => 'Manage Housekeeping (All Access)',
            'housekeeping.dashboard' => 'Dashboard',
            'housekeeping.assign-staff' => 'Assign Staff',
            'housekeeping.templates' => 'Checklist Items',
            'housekeeping.my-tasks' => 'My Tasks',
            'housekeeping.checker' => 'Checker / Verifikasi',
            'housekeeping.work-orders' => 'Work Orders',
            'manage maintenance' => 'Manage Maintenance',
            'view reports' => 'View Reports (All Access)',
            'view analytics' => 'View Analytics',
            'reports.revenue' => 'Revenue Report',
            'reports.occupancy' => 'Occupancy Report',
            'reports.transactions' => 'Transactions Report',
            'reports.bonus' => 'Bonus Report',
            'reports.analytics' => 'Analytics',
            'reports.kost' => 'Laporan Kost & Shift',
            'finance.bank-accounts' => 'Bank Accounts',
            'finance.categories' => 'Finance Categories',
            'finance.income' => 'Tambah Income (Deposit)',
            'finance.expense' => 'Tambah Expense (Pengeluaran)',
            'finance.edit' => 'Edit Transaksi Finance',
            'finance.delete' => 'Hapus Transaksi Finance',
            'edit payments' => 'Edit Payments',
            'delete transactions' => 'Delete Transactions',
            'manage users' => 'Manage Users',
            'admin.users' => 'Users & Staff',
            'manage roles' => 'Manage Roles',
            'admin.roles' => 'Roles & Permissions',
            'admin.booking-sources' => 'Booking Sources',
            'admin.settings' => 'Settings',
            'manage procurement' => 'Manage Procurement (All Access)',
            'procurement.purchases' => 'Purchases',
            'assets.manage' => 'Manage Assets',
            'assets.categories' => 'Asset Categories',
            'tenants.manage' => 'Manage Tenants',
            'tenants.billing' => 'Billing Sewa',
            'tenants.monitoring' => 'Monitoring Omzet',
            'attendance.check-in' => 'Check In / Check Out',
            'attendance.view-own' => 'Lihat Absensi Sendiri',
            'attendance.view-all' => 'Laporan Absensi Semua Karyawan',
            'attendance.manage-locations' => 'Kelola Lokasi Absensi',
            'attendance.export' => 'Export Laporan Absensi',
            // Security permissions
            'security.vehicle-gate' => 'Security Gate Dashboard',
            'security.vehicle-log' => 'Catat Masuk/Keluar Kendaraan',
            'security.vehicle-report' => 'Laporan Kendaraan',
            'security.manage-vehicles' => 'Kelola Master Kendaraan Tamu',
        ];

        foreach ($permissions as $perm) {
            $placed = false;

            // Check sub-group mapping first
            if (isset($subgroupMapping[$perm->name])) {
                [$groupName, $subgroupName] = $subgroupMapping[$perm->name];
                $perm->display_name = $displayNames[$perm->name] ?? $perm->name;

                if ($subgroupName) {
                    if (!isset($groups[$groupName]['subgroups'][$subgroupName])) {
                        $groups[$groupName]['subgroups'][$subgroupName] = [];
                    }
                    $groups[$groupName]['subgroups'][$subgroupName][] = $perm;
                } else {
                    $groups[$groupName]['permissions'][] = $perm;
                }
                $placed = true;
            }

            if (!$placed && isset($legacyMapping[$perm->name])) {
                $perm->display_name = $displayNames[$perm->name] ?? $perm->name;
                $groups[$legacyMapping[$perm->name]]['permissions'][] = $perm;
            } elseif (!$placed) {
                $perm->display_name = $displayNames[$perm->name] ?? $perm->name;
                $groups['Other']['permissions'][] = $perm;
            }
        }

        // Remove empty groups
        return array_filter($groups, fn($g) => !empty($g['permissions']) || !empty($g['subgroups']));
    }

    public function storeRole(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $role = Role::create([
                'name' => $validated['name'],
                'is_housekeeping_staff' => $request->boolean('is_housekeeping_staff'),
            ]);
            
            if ($request->filled('permissions')) {
                $role->syncPermissions($validated['permissions']);
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $this->ajaxOrRedirect('Role created successfully.', route('admin.roles'), $role);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function updateRole(Request $request, Role $role)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $role->update([
                'name' => $validated['name'],
                'is_housekeeping_staff' => $request->boolean('is_housekeeping_staff'),
            ]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($validated['permissions']);
            } else {
                $role->syncPermissions([]);
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $this->ajaxOrRedirect('Role updated successfully.', route('admin.roles'), $role);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function destroyRole(Role $role)
    {
        try {
            if ($role->users_count > 0) {
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError('Cannot delete role with assigned users.');
                }
                return redirect()->back()->with('error', 'Cannot delete role with assigned users.');
            }

            $role->delete();

            return $this->ajaxOrRedirect('Role deleted successfully.', route('admin.roles'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|exists:roles,name',
            ]);

            $user->syncRoles([$validated['role']]);

            return $this->ajaxOrRedirect("Role '{$validated['role']}' assigned to {$user->name} successfully.", back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Display system configuration settings.
     */
    public function systemSettings()
    {
        $hotel = Hotel::find(active_hotel_id());
        
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'hotel_name' => $hotel->name,
            'tax_percentage' => $hotel->tax_percentage,
            'room_deposit_amount' => $hotel->room_deposit_amount,
            'midtrans_environment' => config('services.midtrans.is_production', false) ? 'Production' : 'Sandbox',
            'logo_path' => $hotel->logo_path,
            'logo_light_path' => $hotel->logo_light_path,
            'favicon_path' => $hotel->favicon_path,
            'login_bg_path' => $hotel->login_bg_path,
            'navbar_color' => $hotel->navbar_color,
            'sidebar_color' => $hotel->sidebar_color,
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * Update system settings.
     */
    public function updateSystemSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'hotel_name' => 'required|string|max:255',
                'tax_percentage' => 'required|numeric|min:0|max:100',
                'room_deposit_amount' => 'required|numeric|min:0',
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
                'favicon' => 'nullable|file|mimes:png,ico,svg|max:512',
                'login_bg' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
                'navbar_color' => 'nullable|string|max:20',
                'sidebar_color' => 'nullable|string|max:20',
            ]);

            $hotel = Hotel::find(active_hotel_id());

            $updates = [
                'name' => $validated['hotel_name'],
                'tax_percentage' => $validated['tax_percentage'],
                'room_deposit_amount' => $validated['room_deposit_amount'],
                'navbar_color' => $validated['navbar_color'] ?? null,
                'sidebar_color' => $validated['sidebar_color'] ?? null,
            ];

            if ($request->hasFile('logo')) {
                if ($hotel->logo_path) {
                    Storage::disk('public')->delete($hotel->logo_path);
                }
                $updates['logo_path'] = $request->file('logo')->store('branding/hotel_' . $hotel->id, 'public');
            }

            if ($request->hasFile('favicon')) {
                if ($hotel->favicon_path) {
                    Storage::disk('public')->delete($hotel->favicon_path);
                }
                $updates['favicon_path'] = $request->file('favicon')->store('branding/hotel_' . $hotel->id, 'public');
            }

            if ($request->hasFile('login_bg')) {
                if ($hotel->login_bg_path) {
                    Storage::disk('public')->delete($hotel->login_bg_path);
                }
                $updates['login_bg_path'] = $request->file('login_bg')->store('branding/hotel_' . $hotel->id, 'public');
            }

            $hotel->update($updates);

            return $this->ajaxOrRedirect('Settings updated successfully.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function deleteLogo()
    {
        try {
            $hotel = Hotel::find(active_hotel_id());
            if ($hotel->logo_path) {
                Storage::disk('public')->delete($hotel->logo_path);
                $hotel->update(['logo_path' => null]);
            }
            return $this->ajaxOrRedirect('Logo removed.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function deleteFavicon()
    {
        try {
            $hotel = Hotel::find(active_hotel_id());
            if ($hotel->favicon_path) {
                Storage::disk('public')->delete($hotel->favicon_path);
                $hotel->update(['favicon_path' => null]);
            }
            return $this->ajaxOrRedirect('Favicon removed.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function deleteLoginBg()
    {
        try {
            $hotel = Hotel::find(active_hotel_id());
            if ($hotel->login_bg_path) {
                Storage::disk('public')->delete($hotel->login_bg_path);
                $hotel->update(['login_bg_path' => null]);
            }
            return $this->ajaxOrRedirect('Login background removed.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }
}
