@extends('layouts.master')
@section('title')
    Guest Details - {{ $guest->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Guests
        @endslot
        @slot('title')
            Guest Details
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="avatar-lg">
                                <span class="avatar-title rounded bg-primary-subtle text-primary fs-24">
                                    {{ substr($guest->name ?? 'G', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-1">{{ $guest->name }}</h4>
                            <p class="text-muted mb-0">Legacy Code: <span class="badge bg-secondary-subtle text-secondary">{{ $guest->legacy_customer_code ?? 'N/A' }}</span></p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ route('guests.edit', $guest->id) }}" class="btn btn-success">
                                <i class="ri-pencil-fill align-bottom me-1"></i> Edit Profile
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="ps-0" scope="row" style="width: 30%;">Full Name:</th>
                                    <td class="text-muted">{{ $guest->name }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Email:</th>
                                    <td class="text-muted">{{ $guest->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Phone Number:</th>
                                    <td class="text-muted">{{ $guest->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">ID Number:</th>
                                    <td class="text-muted">{{ $guest->id_number ?? '-' }} ({{ ucfirst($guest->identity_type ?? 'Legacy') }})</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Citizenship:</th>
                                    <td class="text-muted"><span class="badge bg-info-subtle text-info">{{ $guest->citizenship_code ?? 'WNI' }}</span> ({{ $guest->nationality ?? 'Indonesian' }})</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Guest Category:</th>
                                    <td class="text-muted"><span class="badge bg-info-subtle text-info">{{ $guest->guestCategory->name ?? 'General' }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="ps-0" scope="row" style="width: 30%;">Company:</th>
                                    <td class="text-muted">{{ $guest->company_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Customer Type:</th>
                                    <td class="text-muted">{{ $guest->customerType->name ?? 'Regular' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Vehicle Number:</th>
                                    <td class="text-muted">{{ $guest->vehicle_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Legacy Stay Days:</th>
                                    <td class="text-muted">{{ $guest->legacy_stay_type_days ?? '0' }} days</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Address:</th>
                                    <td class="text-muted">{{ $guest->address ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Booking History</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Booking #</th>
                                    <th scope="col">Room</th>
                                    <th scope="col">Check In</th>
                                    <th scope="col">Check Out</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($guest->bookings as $booking)
                                <tr>
                                    <td><a href="{{ route('bookings.show', $booking->id) }}" class="fw-medium">#{{ $booking->booking_number ?? $booking->id }}</a></td>
                                    <td>{{ $booking->room->room_number ?? 'N/A' }} ({{ $booking->room->roomType->name ?? 'N/A' }})</td>
                                    <td>{{ $booking->check_in ? $booking->check_in->format('d M, Y') : '-' }}</td>
                                    <td>{{ $booking->check_out ? $booking->check_out->format('d M, Y') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $booking->status === 'checked_out' ? 'success' : ($booking->status === 'cancelled' ? 'danger' : 'warning') }}-subtle text-{{ $booking->status === 'checked_out' ? 'success' : ($booking->status === 'cancelled' ? 'danger' : 'warning') }} text-uppercase">
                                            {{ str_replace('_', ' ', $booking->status) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($booking->total_price, 2) }}</td>
                                    <td>
                                        <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-sm btn-light">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No booking history available.</td>
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
