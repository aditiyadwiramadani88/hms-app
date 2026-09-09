@extends('layouts.master')
@section('title')
    {{ $tenant->name }} - Tenant Details
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenants.index') }}">Tenants</a>
        @endslot
        @slot('title')
            {{ $tenant->name }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Tenant Details</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-warning btn-sm">
                            <i class="ri-edit-line"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-medium" style="width: 180px;">Business Name</td>
                                    <td>{{ $tenant->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Owner</td>
                                    <td>{{ $tenant->owner_name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Location</td>
                                    <td>{{ $tenant->location_description ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Phone</td>
                                    <td>{{ $tenant->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Email</td>
                                    <td>{{ $tenant->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Monthly Rent</td>
                                    <td>Rp {{ number_format($tenant->rent_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Due Day</td>
                                    <td>{{ $tenant->rent_due_day }} of each month</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Contract Period</td>
                                    <td>{{ $tenant->contract_start->format('d M Y') }}
                                        @if($tenant->contract_end)
                                            - {{ $tenant->contract_end->format('d M Y') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Status</td>
                                    <td>
                                        <span class="badge bg-{{ $tenant->is_active ? 'success' : 'danger' }}">
                                            {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @if($tenant->notes)
                                <tr>
                                    <td class="fw-medium">Notes</td>
                                    <td>{{ $tenant->notes }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    @if($tenant->transactions->isEmpty())
                        <p class="text-muted text-center py-3 mb-0">No transactions yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transaction #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenant->transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_number }}</td>
                                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                        <td><span class="badge bg-secondary">{{ $transaction->payment_method }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Users</h5>
                </div>
                <div class="card-body">
                    @forelse($tenant->users as $user)
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <div class="avatar-xs">
                                    <span class="avatar-title bg-soft-primary text-primary rounded-circle">
                                        {{ substr($user->name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h6 class="mb-0 fs-14">{{ $user->name }}</h6>
                                <p class="text-muted mb-0 small">{{ $user->email }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-2">No users.</p>
                    @endforelse
                    <a href="{{ route('admin.tenants.users.create', $tenant) }}" class="btn btn-soft-primary btn-sm w-100 mt-2">
                        <i class="ri-add-line align-bottom me-1"></i> Add User
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.tenant-monitoring.show', $tenant) }}" class="btn btn-soft-info btn-sm w-100 mb-2">
                        <i class="ri-line-chart-line align-bottom me-1"></i> View Monitoring
                    </a>
                    <a href="{{ route('admin.tenant-monitoring.transactions', $tenant) }}" class="btn btn-soft-secondary btn-sm w-100 mb-2">
                        <i class="ri-file-list-3-line align-bottom me-1"></i> View Transactions
                    </a>
                    <a href="{{ route('admin.tenant-billings.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-soft-primary btn-sm w-100">
                        <i class="ri-file-list-3-line align-bottom me-1"></i> View Billings
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
