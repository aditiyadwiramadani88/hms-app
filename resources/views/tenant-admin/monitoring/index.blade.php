@extends('layouts.master')
@section('title')
    Tenant Monitoring
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('title')
            Tenant Monitoring
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Tenant Overview</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.tenant-monitoring.comparison') }}" class="btn btn-info btn-sm">
                            <i class="ri-bar-chart-line align-bottom me-1"></i> Comparison
                        </a>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('admin.tenant-monitoring.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-4">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Search tenant name...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <select class="form-control" name="status" data-choices>
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
                                    <th>Location</th>
                                    <th class="text-center">Products</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Monthly Revenue</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tenants as $tenant)
                                <tr>
                                    <td>
                                        <h6 class="mb-0">{{ $tenant->name }}</h6>
                                        <small class="text-muted">{{ $tenant->owner_name }}</small>
                                    </td>
                                    <td>{{ $tenant->location_description ?? '-' }}</td>
                                    <td class="text-center">{{ $tenant->products_count ?? $tenant->products->count() }}</td>
                                    <td class="text-center">{{ $tenant->transactions_count ?? $tenant->transactions->count() }}</td>
                                    <td class="text-end">-</td>
                                    <td>
                                        <span class="badge bg-{{ $tenant->is_active ? 'success' : 'danger' }}">
                                            {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if($tenant->hasOverdueBilling())
                                            <span class="badge bg-warning">Overdue</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tenant-monitoring.show', $tenant) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('admin.tenant-monitoring.transactions', $tenant) }}" class="btn btn-soft-info btn-sm">
                                            <i class="ri-file-list-3-line"></i>
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
