@extends('layouts.master')
@section('title')
    Room Rate Management
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
            Room Rate Management
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
            <div class="card" id="rateList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-price-tag-3-line me-2 text-primary"></i>Room Rates</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('room-rates.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add New Rate
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('room-rates.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search" 
                                           placeholder="Search for rate name..." value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-sm-6">
                                <div>
                                    <select class="form-select" name="room_type_id">
                                        <option value="">All Room Types</option>
                                        @foreach($roomTypes as $type)
                                            <option value="{{ $type->id }}" {{ request('room_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-sm-6">
                                <div>
                                    <select class="form-select" name="guest_category_id">
                                        <option value="">All Guest Categories</option>
                                        @foreach($guestCategories as $category)
                                            <option value="{{ $category->id }}" {{ request('guest_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <div class="d-flex gap-2">
                                    <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                        <i class="ri-equalizer-fill me-1 align-bottom"></i> Filters
                                    </button>
                                    <a href="{{ route('room-rates.index') }}" class="btn btn-soft-secondary w-100">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="rateTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">#</th>
                                    <th class="sort text-uppercase" data-sort="rate_name">Name</th>
                                    <th class="sort text-uppercase" data-sort="room_type">Room Type</th>
                                    <th class="sort text-uppercase" data-sort="guest_category">Guest Category</th>
                                    <th class="sort text-uppercase" data-sort="price">Price</th>
                                    <th class="sort text-uppercase" data-sort="dates">Effective Dates</th>
                                    <th class="sort text-uppercase" data-sort="status">Status</th>
                                    <th class="sort text-uppercase" data-sort="action">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($roomRates ?? [] as $rate)
                                <tr>
                                    <th scope="row">{{ $loop->iteration }}</th>
                                    <td class="fw-medium">{{ $rate->name }}</td>
                                    <td>{{ $rate->roomType->name ?? 'N/A' }}</td>
                                    <td>{{ $rate->guestCategory->name ?? 'All' }}</td>
                                    <td class="fw-medium">@money($rate->price)</td>
                                    <td>{{ \Carbon\Carbon::parse($rate->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($rate->end_date)->format('d M Y') }}</td>
                                    <td>
                                        @if($rate->is_locked)
                                            <span class="badge bg-danger-subtle text-danger">Locked (Flash Sale)</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Standard</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <a href="{{ route('room-rates.edit', $rate->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $rate->id }}, '{{ $rate->name }}')">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No room rates found. <a href="{{ route('room-rates.create') }}" class="text-primary">Add a new rate</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $roomRates->withQueryString()->links() }}
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
                        <h4>Delete Rate <span id="deleteName"></span>?</h4>
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
            document.getElementById('deleteForm').action = '{{ route("room-rates.destroy", ":id") }}'.replace(':id', id);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
@endsection
