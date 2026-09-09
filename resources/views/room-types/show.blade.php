@extends('layouts.master')
@section('title')
    {{ $roomType->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('li_2')
            <a href="{{ route('room-types.index') }}">Room Types</a>
        @endslot
        @slot('title')
            {{ $roomType->name }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Room Type Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Name</td>
                            <td class="fw-bold">{{ $roomType->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Description</td>
                            <td>{{ $roomType->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Base Price</td>
                            <td>Rp {{ number_format($roomType->base_price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Max Guests</td>
                            <td>{{ $roomType->max_guests }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Size</td>
                            <td>{{ $roomType->size_sqm ?? '-' }} m²</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <span class="badge bg-{{ $roomType->is_active ? 'success' : 'secondary' }}">
                                    {{ $roomType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <a href="{{ route('room-types.edit', $roomType) }}" class="btn btn-warning w-100 mt-3">Edit</a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rooms in this Type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Room Number</th>
                                    <th>Floor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roomType->rooms ?? [] as $room)
                                <tr>
                                    <td>{{ $room->room_number }}</td>
                                    <td>{{ $room->floor }}</td>
                                    <td>
                                        <span class="badge bg-{{ in_array($room->status, ['Available', 'available']) ? 'success' : ($room->status == 'occupied' ? 'warning' : 'secondary') }}">
                                            {{ $room->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No rooms in this type.</td>
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