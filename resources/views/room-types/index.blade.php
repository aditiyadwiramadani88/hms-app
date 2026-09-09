@extends('layouts.master')
@section('title')
    Room Types
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
            Room Types
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
                    <h5 class="card-title mb-0">Room Types</h5>
                    <a href="{{ route('room-types.create') }}" class="btn btn-success">
                        <i class="ri-add-line align-bottom"></i> Add Room Type
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-secondary w-100">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Base Price</th>
                                    <th>Max Guests</th>
                                    <th>Size (m²)</th>
                                    <th>Rooms</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomTypes ?? [] as $type)
                                <tr>
                                    <td class="fw-bold">{{ $type->name }}</td>
                                    <td>Rp {{ number_format($type->base_price, 0, ',', '.') }}</td>
                                    <td>{{ $type->max_guests }}</td>
                                    <td>{{ $type->size_sqm ?? '-' }}</td>
                                    <td>{{ $type->rooms_count ?? 0 }}</td>
                                    <td>
                                        <span class="badge bg-{{ $type->is_active ? 'success' : 'secondary' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('room-types.show', $type) }}" class="btn btn-sm btn-info">View</a>
                                        <a href="{{ route('room-types.edit', $type) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('room-types.destroy', $type) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No room types found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $roomTypes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection