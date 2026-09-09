@extends('layouts.master')
@section('title')
    Tagihan {{ $billing->billing_period }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('tenant.billings.index') }}">Tagihan Sewa</a>
        @endslot
        @slot('title')
            Detail Tagihan
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Detail Tagihan Sewa</h5>
                    <div class="flex-shrink-0">
                        @if($billing->status !== 'paid')
                            <span class="badge bg-danger">Segera Bayar</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-medium" style="width: 160px;">Periode</td>
                                <td class="fw-semibold">{{ $billing->billing_period }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Jumlah Tagihan</td>
                                <td class="fs-5 fw-bold text-danger">Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Jatuh Tempo</td>
                                <td>{{ $billing->due_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Status</td>
                                <td>
                                    @if($billing->status === 'paid')
                                        <span class="badge bg-success">LUNAS</span>
                                        <span class="ms-2 text-muted small">Dibayar pada {{ $billing->paid_at->format('d/m/Y') }}</span>
                                    @elseif($billing->status === 'overdue')
                                        <span class="badge bg-danger">TERLAMBAT</span>
                                    @else
                                        <span class="badge bg-warning text-dark">BELUM BAYAR</span>
                                    @endif
                                </td>
                            </tr>
                            @if($billing->paid_at)
                            <tr>
                                <td class="fw-medium">Tanggal Bayar</td>
                                <td>{{ $billing->paid_at->format('d M Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Nominal Bayar</td>
                                <td>Rp {{ number_format($billing->paid_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($billing->notes)
                            <tr>
                                <td class="fw-medium">Catatan</td>
                                <td>{{ $billing->notes }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="fw-medium">Dibuat</td>
                                <td>{{ $billing->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('tenant.billings.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection
