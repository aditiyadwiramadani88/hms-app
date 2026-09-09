@extends('layouts.master')
@section('title')
    Role Management
@endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .perm-group { border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 12px; overflow: hidden; }
    .perm-group-header { padding: 10px 16px; cursor: pointer; user-select: none; transition: background 0.2s; }
    .perm-group-header:hover { filter: brightness(0.95); }
    .perm-group-body { padding: 12px 16px; border-top: 1px solid #e9ecef; }
    .perm-check { padding: 4px 0; }
    .perm-check label { cursor: pointer; font-size: 13px; }
    .perm-check .form-check-input:checked + label { font-weight: 600; }
    .select-all-btn { font-size: 11px; cursor: pointer; }
    .role-card { transition: transform 0.2s, box-shadow 0.2s; }
    .role-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Admin @endslot
        @slot('title') Role Management @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <!-- Roles Grid -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Roles ({{ $roles->count() }})</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="openAddModal()">
            <i class="ri-add-line me-1"></i> Add Role
        </button>
    </div>

    <div class="row">
        @foreach($roles as $role)
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card role-card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0">{{ $role->name }}</h6>
                        <span class="badge bg-primary-subtle text-primary">{{ $role->users_count }} user</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-3" style="max-height: 60px; overflow: hidden;">
                        @foreach($role->permissions->take(6) as $perm)
                            <span class="badge bg-light text-dark fs-10">{{ $perm->name }}</span>
                        @endforeach
                        @if($role->permissions->count() > 6)
                            <span class="badge bg-secondary fs-10">+{{ $role->permissions->count() - 6 }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ json_encode($role->permissions->pluck('name')) }}, {{ $role->is_housekeeping_staff ? 'true' : 'false' }})">
                            <i class="ri-pencil-line me-1"></i> Edit
                        </button>
                        @if($role->name !== 'Admin')
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRole({{ $role->id }}, '{{ addslashes($role->name) }}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Add/Edit Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="roleModalTitle"><i class="ri-shield-key-line me-2"></i>Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="roleForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('admin.roles.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="roleFormMethod" value="POST">
                    <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                        <!-- Role Name -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="name" id="roleNameInput" required placeholder="Contoh: Housekeeping Supervisor">
                        </div>

                        <!-- Housekeeping Staff Flag -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_housekeeping_staff" value="1" id="roleHousekeepingStaff">
                                <label class="form-check-label fw-semibold" for="roleHousekeepingStaff">
                                    Tampilkan di Assign Housekeeping Staff
                                </label>
                            </div>
                            <small class="text-muted">Jika dicentang, user dengan role ini akan muncul di dropdown "Assign Cleaning Staff" di halaman Housekeeping.</small>
                        </div>

                        <!-- Quick Actions -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <label class="form-label fw-semibold mb-0">Permissions</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllPerms()">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPerms()">Deselect All</button>
                            </div>
                        </div>

                        <!-- Permission Groups (Tree) -->
                        <div class="row">
                            @foreach($permissionGroups as $groupName => $group)
                            <div class="col-md-6">
                                <div class="perm-group">
                                    <div class="perm-group-header bg-{{ $group['color'] }}-subtle d-flex align-items-center" onclick="toggleGroup(this)">
                                        <i class="{{ $group['icon'] }} me-2 text-{{ $group['color'] }}"></i>
                                        <strong class="flex-grow-1 fs-13">{{ $groupName }}</strong>
                                        <span class="badge bg-{{ $group['color'] }} perm-count">0/{{ count($group['permissions']) + collect($group['subgroups'] ?? [])->flatten()->count() }}</span>
                                        <i class="ri-arrow-down-s-line ms-2 group-arrow"></i>
                                    </div>
                                    <div class="perm-group-body" style="display:none;">
                                        <div class="d-flex justify-content-end mb-2">
                                            <span class="select-all-btn badge bg-light text-primary border" onclick="toggleGroupPerms(this, true)">Select All</span>
                                            <span class="select-all-btn badge bg-light text-muted border ms-1" onclick="toggleGroupPerms(this, false)">None</span>
                                        </div>
                                        {{-- Top-level permissions (no sub-group) --}}
                                        @foreach($group['permissions'] as $perm)
                                        <div class="perm-check form-check">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="modal_perm_{{ $perm->id }}" onchange="updateGroupCount(this)">
                                            <label class="form-check-label fw-medium" for="modal_perm_{{ $perm->id }}">
                                                {{ $perm->display_name ?? $perm->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                        {{-- Sub-groups with indentation --}}
                                        @foreach($group['subgroups'] ?? [] as $subgroupName => $subPerms)
                                        <div class="mt-2 mb-1">
                                            <small class="text-uppercase fw-bold text-muted ps-3">
                                                <i class="ri-arrow-right-s-line"></i> {{ $subgroupName }}
                                            </small>
                                        </div>
                                        @foreach($subPerms as $perm)
                                        <div class="perm-check form-check ps-4">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="modal_perm_{{ $perm->id }}" onchange="updateGroupCount(this)">
                                            <label class="form-check-label" for="modal_perm_{{ $perm->id }}">
                                                {{ $perm->display_name ?? $perm->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <div class="flex-grow-1">
                            <span class="text-muted fs-12" id="totalPermCount">0 permissions selected</span>
                        </div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-success" id="roleSubmitBtn">
                            <i class="ri-save-line me-1"></i> Save Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteRoleForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display:none;">@csrf @method('DELETE')</form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function openAddModal() {
        document.getElementById('roleModalTitle').innerHTML = '<i class="ri-add-circle-line me-2 text-success"></i>Add New Role';
        document.getElementById('roleForm').action = '{{ route("admin.roles.store") }}';
        document.getElementById('roleFormMethod').value = 'POST';
        document.getElementById('roleNameInput').value = '';
        document.getElementById('roleSubmitBtn').innerHTML = '<i class="ri-add-line me-1"></i> Create Role';
        deselectAllPerms();
    }

    function openEditModal(id, name, permissions, isHousekeepingStaff) {
        document.getElementById('roleModalTitle').innerHTML = '<i class="ri-pencil-line me-2 text-info"></i>Edit Role: ' + name;
        document.getElementById('roleForm').action = '{{ route("admin.roles.update", ":id") }}'.replace(':id', id);
        document.getElementById('roleFormMethod').value = 'PUT';
        document.getElementById('roleNameInput').value = name;
        document.getElementById('roleHousekeepingStaff').checked = isHousekeepingStaff || false;
        document.getElementById('roleSubmitBtn').innerHTML = '<i class="ri-save-line me-1"></i> Save Changes';

        // Reset all checkboxes
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);

        // Check matching permissions
        permissions.forEach(perm => {
            const cb = document.querySelector(`.perm-checkbox[value="${perm}"]`);
            if (cb) cb.checked = true;
        });

        // Update all group counts
        document.querySelectorAll('.perm-group').forEach(group => {
            updateGroupCountForGroup(group);
        });
        updateTotalCount();

        new bootstrap.Modal(document.getElementById('roleModal')).show();
    }

    function deleteRole(id, name) {
        Swal.fire({
            title: 'Delete Role?',
            text: `Role "${name}" akan dihapus. Ini tidak bisa dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteRoleForm');
                form.action = '{{ route("admin.roles.destroy", ":id") }}'.replace(':id', id);
                form.submit();
            }
        });
    }

    function toggleGroup(header) {
        const body = header.nextElementSibling;
        const arrow = header.querySelector('.group-arrow');
        if (body.style.display === 'none') {
            body.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    }

    function toggleGroupPerms(btn, checked) {
        const body = btn.closest('.perm-group-body');
        body.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
        updateGroupCountForGroup(body.closest('.perm-group'));
        updateTotalCount();
    }

    function selectAllPerms() {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
        document.querySelectorAll('.perm-group').forEach(g => updateGroupCountForGroup(g));
        updateTotalCount();
    }

    function deselectAllPerms() {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.perm-group').forEach(g => updateGroupCountForGroup(g));
        updateTotalCount();
    }

    function updateGroupCount(checkbox) {
        updateGroupCountForGroup(checkbox.closest('.perm-group'));
        updateTotalCount();
    }

    function updateGroupCountForGroup(group) {
        const total = group.querySelectorAll('.perm-checkbox').length;
        const checked = group.querySelectorAll('.perm-checkbox:checked').length;
        const badge = group.querySelector('.perm-count');
        badge.textContent = checked + '/' + total;
    }

    function updateTotalCount() {
        const total = document.querySelectorAll('.perm-checkbox:checked').length;
        document.getElementById('totalPermCount').textContent = total + ' permissions selected';
    }

    // Initialize counts on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.perm-group').forEach(g => updateGroupCountForGroup(g));
    });
</script>
@endsection
