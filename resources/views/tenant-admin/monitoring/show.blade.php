@extends('layouts.master')
@section('title')
    {{ $tenant->name }} - Monitoring
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenant-monitoring.index') }}">Monitoring</a>
        @endslot
        @slot('title')
            {{ $tenant->name }}
        @endslot
    @endcomponent

    {{-- Stats Cards --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Omzet Hari Ini</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-success-subtle text-success">Today</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">Rp {{ number_format($dailyRevenue, 0, ',', '.') }}</h4>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-money-dollar-circle-line text-success"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Omzet Minggu Ini</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-info-subtle text-info">Weekly</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">Rp {{ number_format($weeklyRevenue, 0, ',', '.') }}</h4>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-info-subtle rounded fs-3"><i class="ri-line-chart-line text-info"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Omzet Bulan Ini</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">Monthly</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</h4>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-bar-chart-box-line text-primary"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate {{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'border-danger' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Tagihan Belum Bayar</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-{{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'danger' : 'secondary' }}-subtle text-{{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'danger' : 'secondary' }}">Billing</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0 {{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'text-danger' : '' }}">Rp {{ number_format($billingSummary['total_outstanding'] ?? 0, 0, ',', '.') }}</h4>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-{{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'danger' : 'secondary' }}-subtle rounded fs-3"><i class="ri-bill-line text-{{ ($billingSummary['total_outstanding'] ?? 0) > 0 ? 'danger' : 'secondary' }}"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Info --}}
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-2 text-primary"></i>Omzet 6 Bulan Terakhir</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-store-2-line me-2"></i>Info Tenant</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled vstack gap-3 mb-0">
                        <li><span class="text-muted">Nama Usaha:</span> <strong>{{ $tenant->name }}</strong></li>
                        <li><span class="text-muted">Pemilik:</span> {{ $tenant->owner_name }}</li>
                        <li><span class="text-muted">Lokasi:</span> {{ $tenant->location_description ?? '-' }}</li>
                        <li><span class="text-muted">Sewa/Bulan:</span> <strong>Rp {{ number_format($tenant->rent_amount, 0, ',', '.') }}</strong></li>
                        <li><span class="text-muted">Kontrak:</span> {{ $tenant->contract_start?->format('d M Y') }} - {{ $tenant->contract_end?->format('d M Y') ?? 'Tidak terbatas' }}</li>
                        <li><span class="text-muted">Produk:</span> {{ $tenant->products->count() }} item</li>
                        <li><span class="text-muted">Total Transaksi:</span> {{ $tenant->transactions()->count() }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Transaksi</h5>
                    <a href="{{ route('admin.tenant-monitoring.transactions', $tenant) }}" class="btn btn-soft-primary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body border-bottom">
                    <form action="{{ route('admin.tenant-monitoring.show', $tenant) }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="q" value="{{ request('q') }}" placeholder="Cari no. transaksi...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    @if($recentTransactions->isEmpty())
                        <p class="text-muted text-center py-3">Belum ada transaksi.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Items</th>
                                        <th class="text-end">Total</th>
                                        <th>Metode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $trx)
                                    <tr>
                                        <td class="fw-medium">{{ $trx->transaction_number }}</td>
                                        <td>{{ $trx->created_at->format('d M Y, H:i') }}</td>
                                        <td class="text-center">{{ $trx->items_count }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $trx->payment_method === 'cash' ? 'success' : ($trx->payment_method === 'qris' ? 'info' : 'primary') }}-subtle text-{{ $trx->payment_method === 'cash' ? 'success' : ($trx->payment_method === 'qris' ? 'info' : 'primary') }}">
                                                {{ strtoupper($trx->payment_method) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $recentTransactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(collect($revenueChart)->pluck('month')),
                datasets: [{
                    label: 'Omzet',
                    data: @json(collect($revenueChart)->pluck('revenue')),
                    backgroundColor: 'rgba(64, 81, 137, 0.8)',
                    borderColor: 'rgba(64, 81, 137, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
