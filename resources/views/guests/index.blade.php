@extends('layouts.master')
@section('title')
    Guest Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Guests
        @endslot
        @slot('title')
            Guest Management
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1"><i class="ri-group-fill me-2 text-primary"></i>Guest Records</h5>
                        <div class="flex-shrink-0">
                            <div class="d-flex gap-2">
                                <a href="{{ route('guests.create') }}" class="btn btn-primary">
                                    <i class="ri-user-add-line align-bottom me-1"></i> Add New Guest
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('guests.index') }}" method="GET" id="guestFilterForm">
                        <input type="hidden" name="sort_by" value="{{ request('sort_by', 'name') }}">
                        <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'asc') }}">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="name" value="{{ request('name') }}" placeholder="Search by name...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <input type="text" class="form-control" name="phone" value="{{ request('phone') }}" placeholder="Phone number...">
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <input type="text" class="form-control" name="company_name" value="{{ request('company_name') }}" placeholder="Company/Institution...">
                            </div>
                            {{-- Filter Guest Category disembunyikan: redundant dgn Customer Type (isinya sama)
                                 & path pricing-nya tidak aktif. Kolom DB dipertahankan. --}}
                            <div class="col-xxl-2 col-sm-6">
                                <select class="form-select" name="customer_type_id">
                                    <option value="">All Types</option>
                                    @foreach($customerTypes as $type)
                                        <option value="{{ $type->id }}" {{ request('customer_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-1 col-sm-6">
                                <select class="form-select" name="citizenship_code">
                                    <option value="">WN</option>
                                    <option value="WNI" {{ request('citizenship_code') == 'WNI' ? 'selected' : '' }}>WNI</option>
                                    <option value="WNA" {{ request('citizenship_code') == 'WNA' ? 'selected' : '' }}>WNA</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" data-submit-protect="true" class="btn btn-secondary w-100">
                                        <i class="ri-equalizer-fill me-1 align-bottom"></i> Filter
                                    </button>
                                    <a href="{{ route('guests.index') }}" class="btn btn-soft-danger w-100">
                                        <i class="ri-refresh-line align-bottom"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Quick Sort Bar --}}
                        <div class="row g-2 mt-2 align-items-center">
                            <div class="col-auto">
                                <span class="fs-12 text-muted fw-semibold"><i class="ri-sort-desc align-middle me-1"></i>Urutkan Data:</span>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" id="quickSortSelect" onchange="applyQuickSort(this.value)">
                                    <option value="name_asc" {{ request('sort_by', 'name') === 'name' && request('sort_dir', 'asc') === 'asc' ? 'selected' : '' }}>Nama (A - Z)</option>
                                    <option value="name_desc" {{ request('sort_by') === 'name' && request('sort_dir') === 'desc' ? 'selected' : '' }}>Nama (Z - A)</option>
                                    <option value="created_at_desc" {{ request('sort_by') === 'created_at' && request('sort_dir', 'desc') === 'desc' ? 'selected' : '' }}>Terdaftar Terbaru</option>
                                    <option value="created_at_asc" {{ request('sort_by') === 'created_at' && request('sort_dir') === 'asc' ? 'selected' : '' }}>Terdaftar Terlama</option>
                                    <option value="bookings_desc" {{ request('sort_by') === 'bookings_count' && request('sort_dir') === 'desc' ? 'selected' : '' }}>Booking Terbanyak</option>
                                    <option value="company_asc" {{ request('sort_by') === 'company_name' && request('sort_dir') === 'asc' ? 'selected' : '' }}>Perusahaan (A - Z)</option>
                                    <option value="code_asc" {{ in_array(request('sort_by'), ['code', 'legacy_customer_code']) && request('sort_dir') === 'asc' ? 'selected' : '' }}>Kode Tamu (A - Z)</option>
                                    <option value="phone_asc" {{ request('sort_by') === 'phone' ? 'selected' : '' }}>No. Telepon</option>
                                    <option value="id_number_asc" {{ request('sort_by') === 'id_number' ? 'selected' : '' }}>No. Identitas</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @php
                        $currentSort = request('sort_by', 'name');
                        $currentDir = request('sort_dir', 'asc');
                    @endphp
                    <div class="table-responsive table-card">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 text-nowrap" style="cursor: pointer;" onclick="sortByGuest('name')" title="Klik untuk mengurutkan Nama">
                                        Guest Info
                                        @if($currentSort === 'name')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-nowrap" style="cursor: pointer;" onclick="sortByGuest('phone')" title="Klik untuk mengurutkan Telepon">
                                        Contact
                                        @if($currentSort === 'phone')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-nowrap" style="cursor: pointer;" onclick="sortByGuest('id_number')" title="Klik untuk mengurutkan No. Identitas">
                                        ID & WN
                                        @if($currentSort === 'id_number')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-nowrap" style="cursor: pointer;" onclick="sortByGuest('category')" title="Klik untuk mengurutkan Kategori">
                                        Category
                                        @if($currentSort === 'category')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-nowrap" style="cursor: pointer;" onclick="sortByGuest('company_name')" title="Klik untuk mengurutkan Perusahaan">
                                        Type & Company
                                        @if($currentSort === 'company_name')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-nowrap" style="cursor: pointer;" onclick="sortByGuest('vehicle_number')" title="Klik untuk mengurutkan Plat Kendaraan">
                                        Vehicle
                                        @if($currentSort === 'vehicle_number')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th>Address</th>
                                    <th class="text-center text-nowrap" style="cursor: pointer;" onclick="sortByGuest('bookings_count')" title="Klik untuk mengurutkan Jumlah Booking">
                                        Bookings
                                        @if($currentSort === 'bookings_count')
                                            <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill text-primary ms-1"></i>
                                        @else
                                            <i class="ri-arrow-up-down-line text-muted ms-1 fs-11"></i>
                                        @endif
                                    </th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($guests as $guest)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 avatar-xs me-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ substr($guest->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="fs-14 mb-1"><a href="{{ route('guests.show', $guest->id) }}" class="link-dark">{{ $guest->name }}</a></h6>
                                                <p class="text-muted mb-0 fs-12">Code: {{ $guest->legacy_customer_code ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted fs-13"><i class="ri-phone-fill me-1 align-bottom"></i>{{ $guest->phone ?? '-' }}</div>
                                        <div class="text-muted fs-12"><i class="ri-mail-fill me-1 align-bottom"></i>{{ $guest->email ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $guest->id_number ?? '-' }}</div>
                                        <span class="badge bg-{{ $guest->citizenship_code == 'WNI' ? 'success' : 'info' }}-subtle text-{{ $guest->citizenship_code == 'WNI' ? 'success' : 'info' }} mt-1">
                                            {{ $guest->citizenship_code ?? 'WNI' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $guest->guestCategory->name ?? 'General' }}</span>
                                    </td>
                                    <td>
                                        <div class="badge bg-primary-subtle text-primary mb-1">{{ $guest->customerType->name ?? 'Regular' }}</div>
                                        <div class="text-muted fs-12">{{ $guest->company_name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><i class="ri-roadster-fill me-1 align-bottom"></i>{{ $guest->vehicle_number ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="text-muted fs-12" style="max-width: 200px; white-space: normal;">
                                            {{ Str::limit($guest->address, 60) }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-info">{{ $guest->bookings_count }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('guests.show', $guest->id) }}" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" title="View Detail"><i class="ri-eye-fill"></i></a>
                                            <a href="{{ route('guests.edit', $guest->id) }}" class="btn btn-soft-info btn-sm" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-fill"></i></a>
                                            <button type="button" class="btn btn-soft-danger btn-sm" onclick="confirmDelete({{ $guest->id }}, '{{ $guest->name }}')" data-bs-toggle="tooltip" title="Delete"><i class="ri-delete-bin-fill"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <lord-icon src="https://cdn.lordicon.com/vlyjqzno.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2 text-muted">No guests found matching your criteria.</h5>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 p-3">
                        <p class="text-muted mb-0">Showing {{ $guests->firstItem() }} to {{ $guests->lastItem() }} of {{ $guests->total() }} entries</p>
                        <div class="pagination-hide-info">
                            {{ $guests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pagination-hide-info nav .flex.items-center.justify-between div:first-child {
            display: none !important;
        }
        /* Untuk Bootstrap pagination info yang muncul otomatis */
        .pagination-hide-info nav p.text-muted {
            display: none !important;
        }
    </style>

    {{-- Delete Modal --}}
    <div class="modal fade flip" id="deleteGuestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px"></lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Guest Record?</h4>
                        <p class="text-muted fs-15 mb-4">Are you sure you want to delete <b id="deleteGuestName"></b>? This action will remove all history and cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i> Cancel</button>
                            <form id="deleteGuestForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Yes, Delete It</button>
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
        function confirmDelete(guestId, guestName) {
            document.getElementById('deleteGuestName').textContent = guestName;
            document.getElementById('deleteGuestForm').action = '{{ route("guests.destroy", ":id") }}'.replace(':id', guestId);
            new bootstrap.Modal(document.getElementById('deleteGuestModal')).show();
        }

        function sortByGuest(column) {
            const currentSort = '{{ $currentSort }}';
            const currentDir = '{{ $currentDir }}';
            let newDir = 'asc';
            if (currentSort === column && currentDir === 'asc') {
                newDir = 'desc';
            }
            const url = new URL(window.location.href);
            url.searchParams.set('sort_by', column);
            url.searchParams.set('sort_dir', newDir);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function applyQuickSort(val) {
            const map = {
                'name_asc': { col: 'name', dir: 'asc' },
                'name_desc': { col: 'name', dir: 'desc' },
                'created_at_desc': { col: 'created_at', dir: 'desc' },
                'created_at_asc': { col: 'created_at', dir: 'asc' },
                'bookings_desc': { col: 'bookings_count', dir: 'desc' },
                'company_asc': { col: 'company_name', dir: 'asc' },
                'code_asc': { col: 'code', dir: 'asc' },
                'phone_asc': { col: 'phone', dir: 'asc' },
                'id_number_asc': { col: 'id_number', dir: 'asc' }
            };
            if (map[val]) {
                const url = new URL(window.location.href);
                url.searchParams.set('sort_by', map[val].col);
                url.searchParams.set('sort_dir', map[val].dir);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        }
    </script>
@endsection
