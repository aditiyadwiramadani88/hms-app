@extends('layouts.master')
@section('title')
    Laporan Kost Bulanan
@endsection
@section('content')
    <x-breadcrumb title="Laporan Kost Bulanan" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-home-4-line me-2 text-primary"></i>
                        SIMPANG HOMESTAY & KOZZ - LAPORAN KOST
                    </h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        @if(request()->routeIs('reports.kost'))
                        <a href="{{ route('reports.kost.export.excel', ['month' => $month->format('Y-m')]) }}" class="btn btn-success btn-sm">
                            <i class="ri-file-excel-2-line align-bottom me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('reports.kost.export.pdf', ['month' => $month->format('Y-m')]) }}" class="btn btn-danger btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <div class="card-header pt-0 pb-0 border-0">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.kost') ? 'active' : '' }}" href="{{ route('reports.kost', ['month' => $month->format('Y-m')]) }}">
                                <i class="ri-home-4-line me-1"></i> Laporan Bulanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.kost.tenant-list') ? 'active' : '' }}" href="{{ route('reports.kost.tenant-list') }}">
                                <i class="ri-team-line me-1"></i> Data Kos
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.kost') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Periode (Bulan)</label>
                                <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body pt-0">
                    {{-- Summary Widgets --}}
                    <div class="row mt-4 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-primary fs-12 mb-1">Periode</p>
                                    <h4 class="mb-0 text-primary">{{ $month->translatedFormat('F Y') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Total Penghuni</p>
                                    <h4 class="mb-0 text-info">{{ count($reportData) }} orang</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Harga Kost</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($totals['harga_kost'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-warning fs-12 mb-1">Total Jaminan</p>
                                    <h4 class="mb-0 text-warning">Rp {{ number_format($totals['jaminan'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Summary per Account --}}
                    <div class="row mb-3">
                        @foreach($bankAccounts as $account)
                        <div class="col-md-{{ max(2, intval(12 / max(count($bankAccounts), 1))) }}">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-dark fs-12 mb-1">{{ $account->name }}</p>
                                    <h5 class="mb-0">Rp {{ number_format($totals['payments'][$account->id] ?? 0, 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Main Table --}}
                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle" style="width: 40px;">NO</th>
                                    <th rowspan="2" class="align-middle">NAMA</th>
                                    <th rowspan="2" class="align-middle">TYPE</th>
                                    <th rowspan="2" class="align-middle">ROOM</th>
                                    <th rowspan="2" class="align-middle" style="width: 40px;">QTY</th>
                                    <th colspan="2" class="text-center">PERIODE</th>
                                    <th rowspan="2" class="align-middle">HARGA KOST</th>
                                    <th rowspan="2" class="align-middle">JAMINAN</th>
                                    <th rowspan="2" class="align-middle">TGL BAYAR</th>
                                    <th colspan="{{ count($bankAccounts) }}" class="text-center">PAYMENT</th>
                                    <th rowspan="2" class="align-middle">STATUS</th>
                                </tr>
                                <tr>
                                    <th>IN</th>
                                    <th>OUT</th>
                                    @foreach($bankAccounts as $account)
                                        <th>{{ strtoupper($account->name) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData as $row)
                                <tr>
                                    <td class="text-center">{{ $row['no'] }}</td>
                                    <td>
                                        <a href="{{ route('reports.kost.detail', $row['booking_id']) }}" class="text-dark fw-medium" title="Lihat rincian pembayaran">
                                            {{ $row['nama'] }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $row['type'] }}</span>
                                    </td>
                                    <td class="text-center fw-medium">{{ $row['room'] }}</td>
                                    <td class="text-center">{{ $row['qty'] }}</td>
                                    <td class="text-center text-nowrap">{{ $row['check_in'] }}</td>
                                    <td class="text-center text-nowrap">{{ $row['check_out'] }}</td>
                                    <td class="text-end">Rp {{ number_format($row['harga_kost'], 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if($row['jaminan'] > 0)
                                            Rp {{ number_format($row['jaminan'], 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">{{ $row['tgl_bayar'] ?: '-' }}</td>
                                    @foreach($bankAccounts as $account)
                                        <td class="text-end">
                                            @if(($row['payments_by_account'][$account->id] ?? 0) > 0)
                                                <span class="text-success fw-medium">Rp {{ number_format($row['payments_by_account'][$account->id], 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        @if($row['payment_status'] === 'paid')
                                            <span class="badge bg-success">LUNAS</span>
                                        @elseif($row['payment_status'] === 'partial')
                                            <span class="badge bg-warning">SEBAGIAN</span>
                                        @else
                                            <span class="badge bg-danger">BELUM</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 10 + count($bankAccounts) + 1 }}" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-home-4-line fs-1 d-block mb-2"></i>
                                            Tidak ada data penghuni kost untuk periode ini.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($reportData) > 0)
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="7" class="text-end">TOTAL:</td>
                                    <td class="text-end">Rp {{ number_format($totals['harga_kost'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($totals['jaminan'], 0, ',', '.') }}</td>
                                    <td></td>
                                    @foreach($bankAccounts as $account)
                                        <td class="text-end">
                                            @if(($totals['payments'][$account->id] ?? 0) > 0)
                                                Rp {{ number_format($totals['payments'][$account->id], 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endforeach
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
