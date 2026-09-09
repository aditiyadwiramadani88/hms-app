@extends('layouts.master')
@section('title')
    Employees Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('title')
            Employees Management
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="employeeList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-user-2-fill me-2 text-primary"></i>Employees List</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('employees.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add New Employee
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Employees Table --}}
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="employeeTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                        </div>
                                    </th>
                                    <th class="sort text-uppercase" data-sort="employee_code">Code</th>
                                    <th class="sort text-uppercase" data-sort="employee_name">Name</th>
                                    <th class="sort text-uppercase" data-sort="employee_position">Position</th>
                                    <th class="sort text-uppercase" data-sort="employee_phone">Phone</th>
                                    <th class="sort text-uppercase" data-sort="status">Status</th>
                                    <th class="sort text-uppercase" data-sort="action">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($employees ?? [] as $employee)
                                <tr>
                                    <th scope="row">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="checkAll" value="{{ $employee->id }}">
                                        </div>
                                    </th>
                                    <td class="fw-medium">{{ $employee->code }}</td>
                                    <td>
                                        <a href="{{ route('employees.edit', $employee->id) }}" class="text-primary">
                                            {{ $employee->name }}
                                        </a>
                                    </td>
                                    <td>{{ $employee->position ?? 'N/A' }}</td>
                                    <td>{{ $employee->phone ?? 'N/A' }}</td>
                                    <td>
                                        @if($employee->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <a href="{{ route('employees.edit', $employee->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $employee->id }}, '{{ $employee->name }}')">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ri-user-unfollow-line fs-1 d-block mb-2"></i>
                                        No employees found. <a href="{{ route('employees.create') }}" class="text-primary">Add a new employee</a> to get started.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end mt-3">
                        {{ $employees ?? []->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade flip" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                        colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                    </lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Employee <span id="deleteName"></span>?</h4>
                        <p class="text-muted fs-15 mb-4">Deleting this employee will remove all associated data. This action cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1 align-middle"></i> Cancel
                            </button>
                            <form id="deleteForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Yes, Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function confirmDelete(id, name) {
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteForm').action = '{{ route("employees.destroy", ":id") }}'.replace(':id', id);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
@endsection
