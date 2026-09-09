@extends('layouts.master')
@section('title')
    Add New Employee
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Employees
        @endslot
        @slot('title')
            Add New Employee
        @endslot
    @endcomponent

    <form action="{{ route('employees.store') }}" method="POST" data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-add-fill me-2 text-primary"></i>Employee Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}"
                                       placeholder="Enter full name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Employee Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code') }}"
                                       placeholder="e.g., EMP-001" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control @error('position') is-invalid @enderror"
                                       id="position" name="position" value="{{ old('position') }}"
                                       placeholder="e.g., Front Office, Housekeeping">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}"
                                       placeholder="Enter phone number">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="3"
                                      placeholder="Enter full address">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="user_id" class="form-label">Link to Existing User Account</label>
                            <select class="form-select @error('user_id') is-invalid @enderror"
                                    id="user_id" name="user_id" data-choices data-choices-search-true>
                                <option value="">None</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Link this employee profile to an existing system user account.</small>
                        </div>

                        <hr class="my-4">

                        <div class="bg-light p-3 rounded border">
                            <div class="form-check form-switch form-switch-md mb-3">
                                <input class="form-check-input" type="checkbox" id="create_user" name="create_user" value="1" {{ old('create_user') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="create_user">Create New Login Account</label>
                            </div>

                            <div id="user_fields" style="display: {{ old('create_user') ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Login Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{ old('email') }}" placeholder="email@hotel.com">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Initial Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                               id="password" name="password" placeholder="Minimum 6 characters">
                                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label for="role" class="form-label">Assign Role <span class="text-danger">*</span></label>
                                    <select class="form-select @error('role') is-invalid @enderror" name="role" id="role">
                                        <option value="">Select a Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        </div>
                        </div>
                        </div>
                        ...
                        @section('script')
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                        const createUserCheckbox = document.getElementById('create_user');
                        const userFields = document.getElementById('user_fields');
                        const existingUserSelect = document.getElementById('user_id');

                        createUserCheckbox.addEventListener('change', function() {
                        userFields.style.display = this.checked ? 'block' : 'none';
                        if (this.checked) {
                        // Disable existing user select if creating new
                        if (existingUserSelect.choices) {
                        // If using choices.js
                        } else {
                        existingUserSelect.value = "";
                        existingUserSelect.disabled = true;
                        }
                        } else {
                        existingUserSelect.disabled = false;
                        }
                        });
                        });
                        </script>
                        @endsection
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-save-line me-1 align-bottom"></i> Save Employee
                            </button>
                            <a href="{{ route('employees.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
@endsection
