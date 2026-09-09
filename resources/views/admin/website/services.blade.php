@extends('layouts.master')
@section('title')
    Manage Services
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Admin @endslot
        @slot('li_2') Website Content @endslot
        @slot('title') Manage Services @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-add-line me-2 text-primary"></i>Add Service</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.website.services.store') }}" method="POST" data-ajax="true">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon (emoji)</label>
                            <input type="text" class="form-control" name="icon" placeholder="🚗" maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" value="{{ $services->count() + 1 }}">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100"><i class="ri-add-line me-1"></i> Add Service</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-service-line me-2 text-primary"></i>Services List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $service)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fs-20">{{ $service->icon }}</td>
                                    <td>{{ $service->name }}</td>
                                    <td>{{ Str::limit($service->description, 50) }}</td>
                                    <td>{{ $service->sort_order }}</td>
                                    <td>
                                        <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">
                                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-soft-info" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form action="{{ route('admin.website.services.destroy', $service->id) }}" method="POST" data-ajax="true" class="d-inline" onsubmit="return confirm('Delete this service?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-submit-protect="true" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.website.services.update', $service->id) }}" method="POST" data-ajax="true">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Service</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Service Name</label>
                                                        <input type="text" class="form-control" name="name" value="{{ $service->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" rows="3">{{ $service->description }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Icon (emoji)</label>
                                                        <input type="text" class="form-control" name="icon" value="{{ $service->icon }}" maxlength="10">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Sort Order</label>
                                                        <input type="number" class="form-control" name="sort_order" value="{{ $service->sort_order }}">
                                                    </div>
                                                    <div class="mb-3 form-check">
                                                        <input type="checkbox" class="form-check-input" id="is_active{{ $service->id }}" name="is_active" {{ $service->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="is_active{{ $service->id }}">Active</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" data-submit-protect="true" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No services added yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
@endsection
