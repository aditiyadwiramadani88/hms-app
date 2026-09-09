<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-success-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Income (Payments)</p>
                <h4 class="mb-0 text-success">Rp {{ number_format($totals['income'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-info fs-12 mb-1">Total Charges (Billed)</p>
                <h4 class="mb-0 text-info">Rp {{ number_format($totals['charge'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-danger fs-12 mb-1">Total Refunds</p>
                <h4 class="mb-0 text-danger">Rp {{ number_format($totals['refund'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-nowrap align-middle table-borderless mb-0">
        <thead class="table-light text-muted">
            <tr>
                <th>Date</th>
                <th>Transaction ID</th>
                <th>Type</th>
                <th>Guest / Room</th>
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
                    @if($trx->type === 'payment')
                        <span class="badge bg-success-subtle text-success">INCOME</span>
                    @elseif($trx->type === 'charge')
                        <span class="badge bg-info-subtle text-info">CHARGE</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">REFUND</span>
                    @endif
                </td>
                <td>
                    <div>
                        <h6 class="mb-0 fs-13 text-dark">{{ $trx->guest->name ?? 'System' }}</h6>
                        <small class="text-muted">Room: {{ $trx->booking->room->room_number ?? '-' }}</small>
                    </div>
                </td>
                <td>
                    <div class="text-wrap" style="width: 250px;">
                        {{ Str::limit($trx->description, 100) }}
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
            <tr><td colspan="7" class="text-center py-3">No transactions found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">
    {{ $transactions->appends(request()->all())->links() }}
</div>
