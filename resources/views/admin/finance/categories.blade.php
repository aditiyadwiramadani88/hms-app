@extends('layouts.master')
@section('title')
    Finance Categories
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Finance
        @endslot
        @slot('title')
            Finance Categories
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Manage Categories</h4>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="ri-add-line align-middle me-1"></i> Add New Category
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Category Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                <tr>
                                    <td><h6 class="fs-14 mb-0">{{ $category->name }}</h6></td>
                                    <td>
                                        <span class="badge bg-{{ $category->type === 'income' ? 'success' : 'danger' }}-subtle text-{{ $category->type === 'income' ? 'success' : 'danger' }} text-uppercase">
                                            {{ $category->type }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-fill fs-18"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item edit-cat-btn" 
                                                       data-id="{{ $category->id }}" 
                                                       data-name="{{ $category->name }}"
                                                       data-active="{{ $category->is_active }}">
                                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                    </a>
                                                </li>
                                                @if(!$category->transactions()->exists())
                                                <li>
                                                    <form action="{{ route('finance.categories.destroy', $category->id) }}" method="POST" data-ajax="true">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" data-submit-protect="true" class="dropdown-item text-danger" onclick="return confirm('Are you sure?')">
                                                            <i class="ri-delete-bin-fill align-bottom me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Finance Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('finance.categories.store') }}" method="POST" data-ajax="true">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Laundry Service, Repair" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="income">Income (Uang Masuk)</option>
                                <option value="expense">Expense (Uang Keluar)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Finance Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" method="POST" data-ajax="true">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" id="edit_cat_name" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active" id="edit_cat_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-cat-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    document.getElementById('edit_cat_name').value = this.dataset.name;
                    document.getElementById('edit_cat_active').value = this.dataset.active;
                    document.getElementById('editCategoryForm').action = '{{ route("finance.categories.update", ":id") }}'.replace(':id', id);
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                });
            });
        });
    </script>
@endsection
