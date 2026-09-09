@extends('layouts.master')
@section('title')
    Comprehensive Transactions Report
@endsection
@section('content')
    <x-breadcrumb title="Transactions Report" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Filter Transactions</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('reports.export.transactions', request()->all()) }}" class="btn btn-success btn-sm">
                            <i class="ri-file-excel-2-line align-bottom me-1"></i> Export to Excel
                        </a>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.transactions') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>Income (Pembayaran)</option>
                                    <option value="charge" {{ $type === 'charge' ? 'selected' : '' }}>Charge (Tagihan)</option>
                                    <option value="refund" {{ $type === 'refund' ? 'selected' : '' }}>Refund (Pengembalian)</option>
                                    <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                                    <option value="deposit" {{ $type === 'deposit' ? 'selected' : '' }}>Deposit</option>
                                </select>
                            </div>
                            <div class="col-xxl-3 col-sm-12 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body pt-0">
                    {{-- Summary Widgets --}}
                    <div class="row mt-4 mb-2">
                        <div class="col-md-3">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Income (Payments)</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($totals['income'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Total Charges (Billed)</p>
                                    <h4 class="mb-0 text-info">Rp {{ number_format($totals['charge'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-danger fs-12 mb-1">Total Refunds</p>
                                    <h4 class="mb-0 text-danger">Rp {{ number_format($totals['refund'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-warning fs-12 mb-1">Total Pengeluaran</p>
                                    <h4 class="mb-0 text-warning">Rp {{ number_format($totals['expense'] ?? 0, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>Type</th>
                                    <th>Guest / Room</th>
                                    <th>Sumber Booking</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td>{{ $trx->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="fw-medium">#TRX-{{ $trx->id }}</span></td>
                                    <td>
                                        @if($trx->is_deposit && $trx->type === 'charge')
                                            <span class="badge bg-purple-subtle text-purple">DEPOSIT</span>
                                        @elseif($trx->is_deposit && $trx->type === 'refund')
                                            <span class="badge bg-purple-subtle text-purple">REFUND DEPOSIT</span>
                                        @elseif($trx->type === 'payment')
                                            <span class="badge bg-success-subtle text-success">INCOME</span>
                                        @elseif($trx->type === 'charge')
                                            <span class="badge bg-info-subtle text-info">CHARGE</span>
                                        @elseif($trx->type === 'expense')
                                            <span class="badge bg-warning-subtle text-warning">PENGELUARAN</span>
                                        @elseif($trx->type === 'refund')
                                            <span class="badge bg-danger-subtle text-danger">REFUND</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($trx->type) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <h6 class="mb-0 fs-13 text-dark">{{ $trx->guest->name ?? 'System' }}</h6>
                                                <small class="text-muted">
                                                    @if($trx->booking && $trx->booking->is_custom)
                                                        Room: {{ $trx->booking->custom_room_name }} (Custom)
                                                    @else
                                                        Room: {{ $trx->booking->room->room_number ?? '-' }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($trx->booking?->bookingSource)
                                            @php $textColor = in_array($trx->booking->bookingSource->color, ['light', 'white']) ? 'dark' : $trx->booking->bookingSource->color; @endphp
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $trx->booking->bookingSource->name }}</span>
                                        @elseif($trx->booking?->source)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($trx->booking->source) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-wrap" style="width: 250px;">
                                            {{ $trx->description }}
                                            @if($trx->is_markup)
                                                <span class="badge bg-warning-subtle text-warning">MARKUP</span>
                                            @endif
                                            @if($trx->payment_method)
                                                <br><small class="text-muted">via {{ ucfirst($trx->payment_method) }} ({{ $trx->bankAccount->name ?? '-' }})</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end fw-medium">
                                        <span class="{{ $trx->type === 'payment' ? 'text-success' : ($trx->type === 'charge' ? 'text-dark' : 'text-danger') }}">
                                            {{ $trx->type === 'refund' ? '-' : '' }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $trx->status === 'success' ? 'success' : 'warning' }}-subtle text-{{ $trx->status === 'success' ? 'success' : 'warning' }} text-uppercase">
                                            {{ $trx->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No transactions found for the selected period.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $transactions->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
