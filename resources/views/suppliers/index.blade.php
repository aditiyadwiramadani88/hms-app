@extends('layouts.master')
@section('title')
    Supplier Management
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
            Supplier Management
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
            <div class="card" id="supplierList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-truck-line me-2 text-primary"></i>Suppliers</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('suppliers.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add New Supplier
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="supplierTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">#</th>
                                    <th class="sort text-uppercase" data-sort="supplier_code">Code</th>
                                    <th class="sort text-uppercase" data-sort="supplier_name">Name</th>
                                    <th class="sort text-uppercase" data-sort="supplier_phone">Phone</th>
                                    <th class="sort text-uppercase" data-sort="supplier_address">Address</th>
                                    <th class="sort text-uppercase" data-sort="action">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($suppliers ?? [] as $supplier)
                                <tr>
                                    <th scope="row">{{ $loop->iteration }}</th>
                                    <td class="fw-medium">{{ $supplier->code }}</td>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->phone }}</td>
                                    <td>{{ Str::limit($supplier->address, 50) }}</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $supplier->id }}, '{{ $supplier->name }}')">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No suppliers found. <a href="{{ route('suppliers.create') }}" class="text-primary">Add a new supplier</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $suppliers->links() ?? '' }}
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
                        <h4>Delete Supplier <span id="deleteName"></span>?</h4>
                        <p class="text-muted fs-15 mb-4">This action cannot be undone.</p>
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
            document.getElementById('deleteForm').action = '{{ route("suppliers.destroy", ":id") }}'.replace(':id', id);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
@endsection
