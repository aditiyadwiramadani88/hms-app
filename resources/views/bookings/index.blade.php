@if(request()->ajax())
    @include('bookings._table-content')
    @php return; @endphp
@endif
@extends('layouts.master')
@section('title')
    Bookings Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .ajax-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 4px;
        }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Bookings
        @endslot
        @slot('title')
            Bookings Management
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
            <div class="card" id="bookingList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-calendar-event-fill me-2 text-primary"></i>Booking History</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @if(!auth()->user()->hasRole('Front Page Only'))
                                @canany(['custom-invoices.create', 'manage reservations'])
                                <a href="{{ route('custom-invoices.create') }}" class="btn btn-soft-primary">
                                    <i class="ri-file-add-line align-bottom me-1"></i> Add Custom Invoice
                                </a>
                                @endcanany
                                <a href="{{ route('custom-invoices.index') }}" class="btn btn-soft-info">
                                    <i class="ri-file-text-line align-bottom me-1"></i> Custom Invoice
                                </a>
                                <a href="{{ route('bookings.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> New Booking
                                </a>
                                @endif
                                <a href="{{ route('bookings.calendar') }}" class="btn btn-info">
                                    <i class="ri-calendar-fill align-bottom me-1"></i> Calendar View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('bookings.index') }}" method="GET" id="filterForm">
                        <input type="hidden" name="tab" id="formTab" value="{{ request('tab', 'today') }}">
                        <div class="row g-3">
                            <div class="col-xxl-2 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Search guest / ID...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <div class="input-group">
                                    <span class="input-group-text">Dari</span>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <div class="input-group">
                                    <span class="input-group-text">Sampai</span>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <select class="form-control" name="room_id" data-choices>
                                    <option value="">All Rooms</option>
                                    @foreach($roomsList as $r)
                                        <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                            Room {{ $r->room_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <div class="d-flex gap-1">
                                    <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                        <i class="ri-filter-3-line me-1 align-bottom"></i> Filter
                                    </button>
                                    <a href="{{ route('bookings.index', ['tab' => request('tab', 'today')]) }}" class="btn btn-soft-secondary">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Bookings Table --}}
                <div class="card-body">
                    <div>
                        <ul class="nav nav-tabs-custom nav-success mb-3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link {{ request('tab') === 'all' ? 'active' : '' }} py-3" data-tab="all" href="{{ route('bookings.index', array_merge(request()->only(['search','room_id','status']), ['tab' => 'all'])) }}">
                                    <i class="ri-calendar-2-line me-1 align-bottom"></i> All Bookings
                                    <span class="badge bg-secondary-subtle text-secondary align-middle ms-1">{{ $counts['all'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request('tab', 'today') === 'today' ? 'active' : '' }} py-3" data-tab="today" href="{{ route('bookings.index', array_merge(request()->only(['search','room_id','status']), ['tab' => 'today'])) }}">
                                    <i class="ri-calendar-todo-line me-1 align-bottom"></i> Today
                                    <span class="badge bg-info-subtle text-info align-middle ms-1">{{ $counts['today'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request('tab') === 'checkin' ? 'active' : '' }} py-3" data-tab="checkin" href="{{ route('bookings.index', array_merge(request()->only(['search','room_id','status']), ['tab' => 'checkin'])) }}">
                                    <i class="ri-login-box-line me-1 align-bottom"></i> Check-in
                                    <span class="badge bg-success-subtle text-success align-middle ms-1">{{ $counts['checkin'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request('tab') === 'checkout' ? 'active' : '' }} py-3" data-tab="checkout" href="{{ route('bookings.index', array_merge(request()->only(['search','room_id','status']), ['tab' => 'checkout'])) }}">
                                    <i class="ri-logout-box-line me-1 align-bottom"></i> Check-out
                                    <span class="badge bg-warning-subtle text-warning align-middle ms-1">{{ $counts['checkout'] ?? 0 }}</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request('tab') === 'cancelled' ? 'active' : '' }} py-3" data-tab="cancelled" href="{{ route('bookings.index', array_merge(request()->only(['search','room_id','start_date','end_date']), ['tab' => 'cancelled'])) }}">
                                    <i class="ri-close-circle-line me-1 align-bottom"></i> Cancelled
                                    <span class="badge bg-danger-subtle text-danger align-middle ms-1">{{ $counts['cancelled'] ?? 0 }}</span>
                                </a>
                            </li>
                        </ul>

                        <div id="ajax-table-container" style="position: relative;">
                            @include('bookings._table-content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade flip" id="deleteBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                        colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                    </lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Booking #<span id="deleteBookingNumber"></span>?</h4>
                        <p class="text-muted fs-15 mb-4">Deleting this booking will remove all associated data. This action cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1 align-middle"></i> Cancel
                            </button>
                            <form id="deleteBookingForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display: inline;">
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
        function confirmDelete(bookingId, bookingNumber) {
            document.getElementById('deleteBookingNumber').textContent = bookingNumber;
            document.getElementById('deleteBookingForm').action = '{{ route("bookings.destroy", ":id") }}'.replace(':id', bookingId);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteBookingModal'));
            deleteModal.show();
        }

        // Show/hide filter date fields based on active tab — no longer needed (all tabs use date range)
        window.updateFilterVisibility = function() {};
    </script>
    <script src="{{ URL::asset('js/bookings-ajax-handler.js') }}"></script>
@endsection
