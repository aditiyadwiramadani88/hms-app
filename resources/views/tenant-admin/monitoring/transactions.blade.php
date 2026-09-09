@extends('layouts.master')
@section('title')
    {{ $tenant->name }} - Transactions
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenant-monitoring.index') }}">Monitoring</a>
        @endslot
        @slot('li_2')
            <a href="{{ route('admin.tenant-monitoring.show', $tenant) }}">{{ $tenant->name }}</a>
        @endslot
        @slot('title')
            Transactions
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">
                        Transactions - {{ $tenant->name }}
                    </h5>

                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('admin.tenant-monitoring.transactions', $tenant) }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-4">
                                <select class="form-control" name="period" data-choices onchange="toggleCustomDate(this)">
                                    <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                                    <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                                    <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3 custom-date-field" style="display:none;">
                                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}" placeholder="Start">
                            </div>
                            <div class="col-xxl-2 col-sm-3 custom-date-field" style="display:none;">
                                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}" placeholder="End">
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
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <p class="text-muted mb-1 small">Total Revenue</p>
                                    <h5 class="mb-0">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <p class="text-muted mb-1 small">Transactions</p>
                                    <h5 class="mb-0">{{ $summary['transaction_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <p class="text-muted mb-1 small">Avg/Transaction</p>
                                    <h5 class="mb-0">Rp {{ number_format($summary['average_transaction'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <p class="text-muted mb-1 small">Items Sold</p>
                                    <h5 class="mb-0">{{ $summary['total_items_sold'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Transaction #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $i => $tx)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $i }}</td>
                                    <td>{{ $tx->transaction_number }}</td>
                                    <td>{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                                    <td>{{ $tx->items_count }}</td>
                                    <td class="fw-semibold">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($tx->payment_method) }}</span></td>
                                    <td>
                                        <a href="{{ route('tenant.transactions.show', $tx) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No transactions found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDate(el) {
            const fields = document.querySelectorAll('.custom-date-field');
            fields.forEach(f => f.style.display = el.value === 'custom' ? 'block' : 'none');
        }
        document.addEventListener('DOMContentLoaded', () => {
            const period = document.querySelector('[name="period"]');
            if (period) toggleCustomDate(period);
        });
    </script>
@endsection
