@extends('layouts.master')
@section('title')
    User Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('title')
            User Management
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="userList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-user-settings-fill me-2 text-primary"></i>User Management</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="ri-user-add-line align-bottom me-1"></i> Add User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body border-bottom">
                    <form action="{{ route('admin.users') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control" placeholder="Search name, email..." value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <select class="form-select" name="role">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <select class="form-select" name="hotel_id">
                                    <option value="">All Hotels</option>
                                    @foreach($hotels as $hotel)
                                        <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-sm-6">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ri-equalizer-fill me-1 align-bottom"></i> Filter
                                    </button>
                                    <a href="{{ route('admin.users') }}" class="btn btn-soft-secondary w-100" title="Reset">
                                        <i class="ri-refresh-line align-bottom"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Users Table --}}
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="userTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Hotels</th>
                                    <th>Roles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($user->avatar)
                                                @php
                                                    $avatarUrl = ($user->avatar !== 'avatar-1.jpg' && file_exists(storage_path('app/public/avatars/' . $user->avatar)))
                                                        ? asset('storage/avatars/' . $user->avatar)
                                                        : asset('images/' . $user->avatar);
                                                @endphp
                                                <img src="{{ $avatarUrl }}"
                                                     alt="{{ $user->name }}"
                                                     class="rounded-circle user-avatar-thumb"
                                                     width="32" height="32"
                                                     style="object-fit: cover; cursor: pointer; border: 2px solid #e9ebec;"
                                                     data-bs-toggle="modal"
                                                     data-bs-target="#avatarPreviewModal"
                                                     data-avatar="{{ $avatarUrl }}"
                                                     data-name="{{ $user->name }}"
                                                     title="Lihat foto {{ $user->name }}">
                                            @else
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                                     style="width:32px; height:32px; font-size:13px; flex-shrink:0;">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="fw-medium">{{ $user->name }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->hotels as $hotel)
                                            <span class="badge bg-info-subtle text-info">{{ $hotel->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-primary-subtle text-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="javascript:void(0);" class="text-primary d-inline-block" 
                                                   onclick="editUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->roles->first()?->name }}', {{ $user->hotels->pluck('id') }}, {{ $user->is_ban ? 'true' : 'false' }}, {{ $user->is_force_logout ? 'true' : 'false' }})">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            @if(auth()->user()->id !== $user->id)
                                            <li class="list-inline-item">
                                                <form action="{{ route('impersonate.start', $user->id) }}" method="POST" class="d-inline" title="Login sebagai {{ $user->name }}" onsubmit="return confirm('Login sebagai {{ addslashes($user->name) }}?')">
                                                    @csrf
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-warning p-0 m-0"><i class="ri-login-box-line fs-16"></i></button>
                                                </form>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0 m-0"><i class="ri-delete-bin-5-fill fs-16"></i></button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit User Modal --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="userModalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="userForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div id="method_field"></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name*</label>
                            <input type="text" class="form-control" name="name" id="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email*</label>
                            <input type="email" class="form-control" name="email" id="user_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span id="pwd_label">*</span></label>
                            <input type="password" class="form-control" name="password" id="user_password">
                            <small class="text-muted" id="pwd_hint">Leave blank to keep current password.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role*</label>
                            <select class="form-select" name="role" id="user_role" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Hotels*</label>
                            <select class="form-select select2-multiple" name="hotels[]" id="user_hotels" multiple required>
                                @foreach($hotels as $hotel)
                                    <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="banForceLogoutSection" style="display:none;">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_ban" value="1" id="user_is_ban">
                                        <label class="form-check-label text-danger fw-medium" for="user_is_ban">
                                            <i class="ri-forbid-line me-1"></i>Ban Akun
                                        </label>
                                    </div>
                                    <small class="text-muted">User tidak bisa login</small>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_force_logout" value="1" id="user_is_force_logout">
                                        <label class="form-check-label text-warning fw-medium" for="user_is_force_logout">
                                            <i class="ri-logout-box-r-line me-1"></i>Force Logout
                                        </label>
                                    </div>
                                    <small class="text-muted">Paksa logout sekarang</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-success" id="userSubmitBtn">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Avatar Preview Modal --}}
    <div class="modal fade" id="avatarPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title" id="avatarPreviewName"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <img id="avatarPreviewImg" src="" alt="" class="img-fluid rounded" style="max-height: 400px; width: 100%; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>

    {{-- Re-use Add User Button trigger for new modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Avatar preview modal
            document.querySelectorAll('.user-avatar-thumb').forEach(function(img) {
                img.addEventListener('click', function() {
                    document.getElementById('avatarPreviewImg').src = this.dataset.avatar;
                    document.getElementById('avatarPreviewImg').alt = this.dataset.name;
                    document.getElementById('avatarPreviewName').textContent = this.dataset.name;
                });
            });

            // Replace trigger for Add User button
            const addBtn = document.querySelector('[data-bs-target="#addUserModal"]');
            if (addBtn) {
                addBtn.setAttribute('data-bs-target', '#userModal');
                addBtn.addEventListener('click', function() {
                    document.getElementById('userModalTitle').textContent = 'Add New User';
                    document.getElementById('userForm').action = '{{ route("admin.users.store") }}';
                    document.getElementById('method_field').innerHTML = '';
                    document.getElementById('userForm').reset();
                    document.getElementById('pwd_label').textContent = '*';
                    document.getElementById('pwd_hint').style.display = 'none';
                    document.getElementById('user_password').required = true;
                    document.getElementById('userSubmitBtn').textContent = 'Create User';
                    $('#user_hotels').val(null).trigger('change');
                });
            }
        });

        function editUser(id, name, email, role, hotels, isBan, isForceLogout) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userForm').action = '{{ route("admin.users.update", ":id") }}'.replace(':id', id);
            document.getElementById('method_field').innerHTML = '@method("PUT")';
            document.getElementById('user_name').value = name;
            document.getElementById('user_email').value = email;
            document.getElementById('user_role').value = role;
            document.getElementById('user_password').required = false;
            document.getElementById('pwd_label').textContent = '(Optional)';
            document.getElementById('pwd_hint').style.display = 'block';
            document.getElementById('userSubmitBtn').textContent = 'Update User';
            
            // Show ban/force-logout section for edit
            document.getElementById('banForceLogoutSection').style.display = 'block';
            document.getElementById('user_is_ban').checked = isBan || false;
            document.getElementById('user_is_force_logout').checked = isForceLogout || false;
            
            // Set Select2 values
            $('#user_hotels').val(hotels).trigger('change');
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    </script>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-multiple').select2({
                placeholder: "Select Hotels",
                allowClear: true,
                dropdownParent: $('#userModal')
            });
        });
    </script>
@endsection
