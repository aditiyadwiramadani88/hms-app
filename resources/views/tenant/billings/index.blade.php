@extends('layouts.master')
@section('title')
    Tagihan Sewa
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Tenant
        @endslot
        @slot('title')
            Tagihan Sewa
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Tagihan Sewa Saya</h5>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-{{ $summary['total_outstanding'] > 0 ? 'warning' : 'secondary' }} text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Total Belum Bayar</p>
                                    <h4 class="mb-0">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Total Sudah Bayar</p>
                                    <h4 class="mb-0">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-{{ $summary['overdue_count'] > 0 ? 'danger' : 'info' }} text-white">
                                <div class="card-body py-2">
                                    <p class="mb-1 small opacity-75">Tagihan Terlambat</p>
                                    <h4 class="mb-0">{{ $summary['overdue_count'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Periode</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($billings as $billing)
                                <tr>
                                    <td class="fw-semibold">{{ $billing->billing_period }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
                                    <td>{{ $billing->due_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($billing->status === 'paid')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($billing->status === 'overdue')
                                            <span class="badge bg-danger">Terlambat</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td>{{ $billing->paid_at ? $billing->paid_at->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <a href="{{ route('tenant.billings.show', $billing) }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada tagihan.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $billings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
