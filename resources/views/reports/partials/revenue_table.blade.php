<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('translation.total_gross_revenue') }} (Kas)</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2">Rp {{ number_format($report['gross_revenue'] ?? 0, 0, ',', '.') }}</h4>
                <p class="text-muted mb-0 mt-2 fs-12">{{ __('translation.gross_revenue_kas_desc') }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-success border-start border-4">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-success mb-0">{{ __('translation.realized_revenue_owner') }}</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2 text-success">Rp {{ number_format($report['realized_revenue'] ?? 0, 0, ',', '.') }}</h4>
                <p class="text-muted mb-0 mt-2 fs-12">{{ __('translation.realized_revenue_sah_desc') }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('translation.total_amount') }} Expenses</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2 text-danger">Rp {{ number_format($report['expenses'] ?? 0, 0, ',', '.') }}</h4>
                <p class="text-muted mb-0 mt-2 fs-12">Operational costs & withdrawals</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('translation.net_profit_realized') }}</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2 {{ ($report['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($report['net_profit'] ?? 0, 0, ',', '.') }}
                </h4>
                <p class="text-muted mb-0 mt-2 fs-12">{{ __('translation.profit_realized_desc') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 mb-4">
    <div class="d-flex">
        <div class="flex-shrink-0">
            <i class="ri-information-line fs-18"></i>
        </div>
        <div class="flex-grow-1 ms-3">
            <h6 class="alert-heading fs-14 fw-bold">{{ __('translation.realized_revenue_info_title') }}</h6>
            <p class="mb-0 fs-13">{{ __('translation.realized_revenue_explanation') }}</p>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-nowrap align-middle table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('translation.room_types') }}</th>
                <th class="text-center">Rooms Sold</th>
                <th class="text-end">Revenue</th>
                <th class="text-center">Performance</th>
            </tr>
        </thead>
        <tbody>
            @php $roomTotal = $report['room_revenue']['total'] ?? 0; @endphp
            @forelse($roomTypePerformance ?? [] as $perf)
            <tr>
                <td>{{ $perf->room_type_name }}</td>
                <td class="text-center">{{ $perf->total_bookings }}</td>
                <td class="text-end">Rp {{ number_format($perf->total_revenue, 0, ',', '.') }}</td>
                <td class="text-center">
                    <div class="progress animated-progress progress-sm">
                        @php $percent = $roomTotal > 0 ? ($perf->total_revenue / $roomTotal * 100) : 0; @endphp
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%"></div>
                    </div>
                    <small>{{ round($percent, 1) }}% of {{ __('translation.room_revenue') }}</small>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
