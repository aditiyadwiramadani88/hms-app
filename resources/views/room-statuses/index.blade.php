@extends('layouts.master')
@section('title')
    Room Statuses
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
            Room Statuses
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="ri-error-warning-line me-2"></i> Terdapat Kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Room Statuses</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('room-statuses.store') }}" class="row g-3 mb-4">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label visually-hidden">Status Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Status name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label visually-hidden">Color</label>
                            <div class="d-flex align-items-center h-100">
                                <span class="text-muted me-2">Color:</span>
                                <input type="color" name="color" class="form-control form-control-color p-1" title="Choose status color" value="#28a745" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label visually-hidden">Order</label>
                            <input type="number" name="display_order" class="form-control" placeholder="Order" value="0">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_available" value="1" class="form-check-input" id="is_available">
                                <label class="form-check-label" for="is_available">Available</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success w-100">Add Status</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Order</th>
                                    <th>Name</th>
                                    <th>Color</th>
                                    <th>Available</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statuses ?? [] as $status)
                                <tr>
                                    <td>{{ $status->display_order }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $status->color }}; color: white;">{{ $status->name }}</span>
                                    </td>
                                    <td>{{ $status->color }}</td>
                                    <td>
                                        <span class="badge bg-{{ $status->is_available ? 'success' : 'secondary' }}">
                                            {{ $status->is_available ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $status->id }}">Edit</button>
                                        <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('room-statuses.destroy', $status) }}" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No statuses found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $statuses->links() }}
                </div>
            </div>
        </div>
    </div>

    @foreach($statuses ?? [] as $status)
    <div class="modal fade" id="editModal{{ $status->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('room-statuses.update', $status) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $status->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color p-1" value="{{ $status->color }}" required>
                        </div>                        <div class="mb-3">
                            <label class="form-label">Order</label>
                            <input type="number" name="display_order" class="form-control" value="{{ $status->display_order }}">
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" name="is_available" value="1" class="form-check-input" id="is_available_{{ $status->id }}" {{ $status->is_available ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_available_{{ $status->id }}">Available</label>
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
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection