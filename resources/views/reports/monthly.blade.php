@extends('layouts.master')
@section('title')
    Laporan Bulanan - Keuangan Online & Transfer
@endsection
@section('content')
    <x-breadcrumb title="Laporan Bulanan" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title mb-0 flex-grow-1">
                            <i class="ri-book-2-line me-2 text-primary"></i>
                            LAPORAN KEUANGAN ONLINE DAN TRANSFER
                        </h4>
                        <div class="flex-shrink-0">
                            <a href="{{ route('reports.monthly.pdf', ['month' => $month->format('Y-m')]) }}" class="btn btn-danger btn-sm">
                                <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                            </a>
                        </div>
                    </div>
                    <p class="text-muted mb-0 mt-1 fs-13">
                        Rekap booking check-out per hari, dipecah per sumber (Agoda/Reddoorz/Traveloka/Lainnya). Beda dengan <a href="{{ route('reports.transfer-online') }}">Laporan Transfer Online</a> yang berisi daftar transaksi transfer bank untuk rekonsiliasi rekening.
                    </p>
                </div>

                {{-- Filter --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.monthly') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Bulan</label>
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
                    {{-- Main Table --}}
                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 35px;">NO</th>
                                    <th>TGL LAPORAN</th>
                                    <th>NAMA</th>
                                    <th>ROOM</th>
                                    <th>BOOKING ID</th>
                                    <th>CHECK IN</th>
                                    <th>CHECK OUT</th>
                                    <th>AGODA</th>
                                    <th>REDD</th>
                                    <th>TRAVEL</th>
                                    <th>ONLINE TF</th>
                                    <th>TGL TF</th>
                                    <th>JENIS ANGGOTA</th>
                                    <th>SALES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData['dateGroups'] as $dateGroup)
                                    {{-- Date Group Header --}}
                                    <tr class="table-primary" style="cursor: pointer;" onclick="window.location='{{ route('reports.daily', ['date' => $dateGroup['date']->format('Y-m-d')]) }}'">
                                        <td colspan="14" class="fw-bold">
                                            <i class="ri-calendar-line me-1"></i>
                                            Hari ke-{{ $dateGroup['day'] }} — {{ $dateGroup['formatted_date'] }}
                                            <i class="ri-external-link-line ms-1" style="font-size: 11px;"></i>
                                        </td>
                                    </tr>

                                    {{-- Data Rows --}}
                                    @foreach($dateGroup['rows'] as $row)
                                    <tr>
                                        <td class="text-center">{{ $row['no'] }}</td>
                                        <td class="text-center">{{ $row['tgl_laporan'] }}</td>
                                        <td class="text-nowrap">{{ $row['nama'] }}</td>
                                        <td class="text-center">{{ $row['room'] }}</td>
                                        <td class="text-center fw-medium">
                                            <a href="{{ route('bookings.show', $row['booking_id']) }}" class="text-dark">{{ $row['booking_id'] }}</a>
                                        </td>
                                        <td class="text-center">{{ $row['check_in'] }}</td>
                                        <td class="text-center">{{ $row['check_out'] }}</td>
                                        <td class="text-end">
                                            @if($row['agoda'] > 0)
                                                Rp {{ number_format($row['agoda'], 0, ',', '.') }}
                                            @else - @endif
                                        </td>
                                        <td class="text-end">
                                            @if($row['redd'] > 0)
                                                Rp {{ number_format($row['redd'], 0, ',', '.') }}
                                            @else - @endif
                                        </td>
                                        <td class="text-end">
                                            @if($row['travel'] > 0)
                                                Rp {{ number_format($row['travel'], 0, ',', '.') }}
                                            @else - @endif
                                        </td>
                                        <td class="text-end">
                                            @if($row['online_tf'] > 0)
                                                Rp {{ number_format($row['online_tf'], 0, ',', '.') }}
                                            @else - @endif
                                        </td>
                                        <td class="text-center">{{ $row['tgl_tf'] }}</td>
                                        <td class="text-center">{{ $row['jenis_anggota'] }}</td>
                                        <td class="text-center">{{ $row['sales'] }}</td>
                                    </tr>
                                    @endforeach

                                    {{-- Subtotal Row --}}
                                    <tr class="table-light fw-bold">
                                        <td colspan="7" class="text-end text-uppercase">Subtotal {{ $dateGroup['formatted_date'] }}</td>
                                        <td class="text-end">Rp {{ number_format($dateGroup['subtotal']['agoda'], 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($dateGroup['subtotal']['redd'], 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($dateGroup['subtotal']['travel'], 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($dateGroup['subtotal']['online_tf'], 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                                Tidak ada data checkout pada bulan {{ $month->translatedFormat('F Y') }}.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($reportData['dateGroups']) > 0)
                            <tfoot class="table-dark fw-bold">
                                <tr>
                                    <td colspan="7" class="text-end text-uppercase">GRAND TOTAL</td>
                                    <td class="text-end">Rp {{ number_format($reportData['grandTotals']['agoda'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($reportData['grandTotals']['redd'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($reportData['grandTotals']['travel'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($reportData['grandTotals']['online_tf'], 0, ',', '.') }}</td>
                                    <td colspan="3">Total: Rp {{ number_format($reportData['grandTotal'], 0, ',', '.') }}</td>
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
