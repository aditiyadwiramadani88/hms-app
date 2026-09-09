@extends('layouts.master')
@section('title')
    Inventory Categories
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('li_2')
            <a href="{{ route('inventory.index') }}">POS Inventory</a>
        @endslot
        @slot('title')
            Categories
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Inventory Categories</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('inventory.categories.store') }}" class="row g-3 mb-4">
                        @csrf
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control" placeholder="New category name" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success w-100">Add Category</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories ?? [] as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $category->inventories_count ?? 0 }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">Edit</button>
                                        <form method="POST" data-ajax="true" action="{{ route('inventory.categories.destroy', $category) }}" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger" onclick="return confirm('Delete category?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No categories found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>

    @foreach($categories ?? [] as $category)
    <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" data-ajax="true" action="{{ route('inventory.categories.update', $category) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
@endsection
@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        @if(session('success'))
            Toastify({
                text: "{{ session('success') }}",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, #0ab39c, #405189)" }
            }).showToast();
        @endif

        @if(session('error'))
            Toastify({
                text: "{{ session('error') }}",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, #f06548, #f7b84b)" }
            }).showToast();
        @endif
    </script>
@endsection