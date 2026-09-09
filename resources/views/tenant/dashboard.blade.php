@extends('layouts.master')
@section('title')
    Dashboard - {{ auth()->user()->tenant->name }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0 font-size-18">Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Tenant</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-white bg-opacity-25 text-white rounded fs-4">
                                    <i class="mdi mdi-cash-multiple"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-white-75 mb-1">Omzet Hari Ini</p>
                            <h3 class="mb-0 text-white">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-white bg-opacity-25 text-white rounded fs-4">
                                    <i class="mdi mdi-cash"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-white-75 mb-1">Omzet Bulan Ini</p>
                            <h3 class="mb-0 text-white">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-white bg-opacity-25 text-white rounded fs-4">
                                    <i class="mdi mdi-receipt-text-outline"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-white-75 mb-1">Transaksi Hari Ini</p>
                            <h3 class="mb-0 text-white">{{ $todayTransactions }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card {{ $outstandingBillings > 0 ? 'bg-danger' : 'bg-secondary' }} text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-white bg-opacity-25 text-white rounded fs-4">
                                    <i class="mdi mdi-file-document-outline"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-white-75 mb-1">Tagihan Belum Bayar</p>
                            <h3 class="mb-0 text-white">{{ $outstandingBillings }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">
                        <i class="mdi mdi-history me-1"></i> Transaksi Terakhir
                    </h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('tenant.transactions.index') }}" class="btn btn-primary btn-sm">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentTransactions->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="mdi mdi-receipt-text-outline display-4"></i>
                            <p class="mt-2 mb-0">Belum ada transaksi hari ini</p>
                            <a href="{{ route('tenant.transactions.create') }}" class="btn btn-success btn-sm mt-2">
                                <i class="mdi mdi-plus-circle-outline me-1"></i> Buat Transaksi
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Waktu</th>
                                        <th>Items</th>
                                        <th class="text-end">Total</th>
                                        <th>Pembayaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $tx)
                                    <tr>
                                        <td>
                                            <a href="{{ route('tenant.transactions.show', $tx) }}" class="text-body fw-semibold">
                                                {{ $tx->transaction_number }}
                                            </a>
                                        </td>
                                        <td>{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                                        <td>{{ $tx->items_count }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($tx->payment_method) }}</span></td>
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
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-lightning-bolt-outline me-1"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tenant.transactions.create') }}" class="btn btn-success">
                            <i class="mdi mdi-plus-circle-outline me-2"></i> Buat Transaksi
                        </a>
                        <a href="{{ route('tenant.products.index') }}" class="btn btn-primary">
                            <i class="mdi mdi-shopping-outline me-2"></i> Kelola Produk
                        </a>
                        <a href="{{ route('tenant.billings.index') }}" class="btn btn-warning">
                            <i class="mdi mdi-file-document-outline me-2"></i> Lihat Tagihan Sewa
                        </a>
                        @if($lowStockProducts > 0)
                        <a href="{{ route('tenant.products.index') }}?status=active" class="btn btn-danger">
                            <i class="mdi mdi-alert-outline me-2"></i> Stok Rendah ({{ $lowStockProducts }})
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            @if($outstandingBillings > 0)
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-alert-circle-outline me-1"></i> Tagihan Belum Lunas
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Anda memiliki <strong>{{ $outstandingBillings }}</strong> tagihan belum dibayar.</p>
                    <a href="{{ route('tenant.billings.index') }}" class="btn btn-danger btn-sm w-100">
                        Lihat Detail Tagihan
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
