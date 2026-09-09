@extends('layouts.master')
@section('title')
    My Transactions
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Tenant
        @endslot
        @slot('title')
            My Transactions
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">My Transactions</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('tenant.transactions.create') }}" class="btn btn-success btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> New Transaction
                        </a>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('tenant.transactions.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-4">
                                <select class="form-control" name="period" data-choices>
                                    <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                                    <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <select class="form-control" name="payment_method" data-choices>
                                    <option value="">All Payment</option>
                                    @php $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(); @endphp
                                    @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->code }}" {{ request('payment_method') === $pm->code ? 'selected' : '' }}>{{ $pm->name }}</option>
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
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Total Revenue</p>
                                    <h4 class="mb-0">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Transactions</p>
                                    <h4 class="mb-0">{{ $summary['transaction_count'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Avg/Transaction</p>
                                    <h4 class="mb-0">Rp {{ number_format($summary['average_transaction'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Transaction #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th class="text-end">Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $i => $tx)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $i }}</td>
                                    <td>{{ $tx->transaction_number }}</td>
                                    <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $tx->items_count }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($tx->payment_method) }}</span></td>
                                    <td>
                                        @if($tx->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($tx->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-warning">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('tenant.transactions.show', $tx) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.transactions.print', $tx) }}" target="_blank" class="btn btn-soft-info btn-sm">
                                            <i class="ri-printer-line"></i>
                                        </a>
                                        <form action="{{ route('tenant.transactions.destroy', $tx) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus transaksi {{ $tx->transaction_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-soft-danger btn-sm">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
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
@endsection
