@extends('layouts.master')
@section('title')
    Tenant Billings
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('title')
            Tenant Billings
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Billing List</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.tenant-billings.generate') }}" class="btn btn-success btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> Generate Billings
                        </a>
                        <form action="{{ route('admin.tenant-billings.check-overdue') }}" method="POST" data-ajax="true" class="d-inline">
                            @csrf
                            <button type="submit" data-submit-protect="true" class="btn btn-warning btn-sm">
                                <i class="ri-time-line align-bottom me-1"></i> Check Overdue
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('admin.tenant-billings.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-4">
                                <select class="form-control" name="tenant_id" data-choices>
                                    <option value="">All Tenants</option>
                                    @foreach($tenants as $t)
                                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-control" name="status" data-choices>
                                    <option value="">All Status</option>
                                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-control" name="billing_period" data-choices>
                                    <option value="">All Periods</option>
                                    @foreach($periods as $period)
                                        <option value="{{ $period }}" {{ request('billing_period') === $period ? 'selected' : '' }}>{{ $period }}</option>
                                    @endforeach
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
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($billings as $billing)
                                <tr>
                                    <td>{{ $billing->tenant->name }}</td>
                                    <td>{{ $billing->billing_period }}</td>
                                    <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
                                    <td>{{ $billing->due_date->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $billing->status === 'paid' ? 'success' : ($billing->status === 'overdue' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($billing->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tenant-billings.show', $billing) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        @if($billing->status !== 'paid')
                                            <a href="{{ route('admin.tenant-billings.mark-paid', $billing) }}" class="btn btn-soft-success btn-sm">
                                                <i class="ri-check-line"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No billings found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $billings->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
