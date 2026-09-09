@extends('layouts.master')
@section('title')
    Laporan Transfer Online
@endsection
@section('content')
    <x-breadcrumb title="Laporan Transfer Online" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <h4 class="card-title mb-0">
                        <i class="ri-bank-card-2-line me-2 text-primary"></i>
                        Laporan Transaksi Transfer Bank
                    </h4>
                    <p class="text-muted mb-0 mt-1 fs-13">
                        Daftar transaksi pembayaran via transfer bank untuk rekonsiliasi rekening, dikelompokkan per bank. Beda dengan <a href="{{ route('reports.monthly') }}">Laporan Bulanan</a> yang merekap booking check-out per sumber (Agoda/Reddoorz/Traveloka/Lainnya).
                    </p>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Bulan / Tahun</label>
                                <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Transfer</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($grandTotal, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Jumlah Transaksi</p>
                                    <h4 class="mb-0 text-info">{{ $transactions->count() }} transaksi</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-primary fs-12 mb-1">Bulan</p>
                                    <h4 class="mb-0 text-primary">{{ $month->translatedFormat('F Y') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary Per Bank --}}
                    @if($summaryPerBank->count() > 0)
                    <div class="row mb-3">
                        @foreach($summaryPerBank as $bank)
                        <div class="col-md-3">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-muted fs-11 mb-1">{{ $bank['bank_name'] }}</p>
                                    <h5 class="mb-0">{{ number_format($bank['count'], 0, ',', '.') }}x — Rp {{ number_format($bank['total'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>NO</th>
                                    <th>TANGGAL</th>
                                    <th>BOOKING ID</th>
                                    <th>GUEST</th>
                                    <th>ROOM</th>
                                    <th>BANK ACCOUNT</th>
                                    <th>AMOUNT</th>
                                    <th>KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center text-nowrap">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        @if($trx->booking_id)
                                            <a href="{{ route('bookings.show', $trx->booking_id) }}">#{{ $trx->booking_id }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $trx->guest->name ?? ($trx->booking?->guest?->name ?? '-') }}</td>
                                    <td class="text-center">
                                        @if($trx->booking?->room)
                                            {{ $trx->booking->room->room_number }}
                                        @elseif($trx->booking?->custom_room_name)
                                            {{ $trx->booking->custom_room_name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $trx->bankAccount->name ?? '-' }}</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                    <td style="max-width: 200px; white-space: normal;">{{ $trx->description }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-bank-card-2-line fs-1 d-block mb-2"></i>
                                            Tidak ada transaksi transfer pada bulan ini.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($transactions->count() > 0)
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="6" class="text-end">TOTAL:</td>
                                    <td class="text-end text-success">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
