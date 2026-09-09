@extends('layouts.master')
@section('title')
    Tenant Management
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('title')
            Tenant Management
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Tenant List</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> Add Tenant
                        </a>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('admin.tenants.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-4">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Search tenant name...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <select class="form-control" name="status" data-choices data-choices-search-false>
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-12">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                    <i class="ri-filter-3-line me-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Tenant</th>
                                    <th>Owner</th>
                                    <th>Location</th>
                                    <th>Rent Amount</th>
                                    <th>Contract</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tenants as $tenant)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm">
                                                    <span class="avatar-title bg-soft-primary text-primary rounded">
                                                        {{ substr($tenant->name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fs-14 mb-0">{{ $tenant->name }}</h6>
                                                <p class="text-muted mb-0 small">{{ $tenant->users_count ?? $tenant->users->count() }} user(s)</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $tenant->owner_name }}</td>
                                    <td>{{ $tenant->location_description ?? '-' }}</td>
                                    <td>Rp {{ number_format($tenant->rent_amount, 0, ',', '.') }}</td>
                                    <td>
                                        {{ $tenant->contract_start->format('d/m/Y') }}
                                        @if($tenant->contract_end)
                                            - {{ $tenant->contract_end->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $tenant->is_active ? 'success' : 'danger' }}">
                                            {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-soft-warning btn-sm">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No tenants found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $tenants->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
