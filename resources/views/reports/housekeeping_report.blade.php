@extends('layouts.master')

@section('title', 'Laporan Housekeeping')

@section('content')
<x-breadcrumb title="Laporan Housekeeping" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Laporan Tugas Housekeeping</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.housekeeping.pdf', array_merge(request()->query(), ['month' => $month, 'staff_id' => $staffId])) }}" class="btn btn-sm btn-danger">
                        <i class="ri-file-pdf-line me-1"></i> Export PDF
                    </a>
                    <a href="{{ route('reports.housekeeping.excel', array_merge(request()->query(), ['month' => $month, 'staff_id' => $staffId])) }}" class="btn btn-sm btn-success">
                        <i class="ri-file-excel-line me-1"></i> Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(request()->filled('date'))
                <div class="alert alert-info py-2">
                    <i class="ri-calendar-line me-1"></i> Menampilkan tugas tanggal <strong>{{ \Carbon\Carbon::parse(request('date'))->translatedFormat('d F Y') }}</strong> saja.
                    <a href="{{ route('reports.housekeeping') }}" class="ms-2">Lihat per bulan &raquo;</a>
                </div>
                @endif
                {{-- Filters --}}
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-auto">
                        <label class="form-label">Bulan</label>
                        <input type="month" name="month" class="form-control" value="{{ $month }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">Semua Staff</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ $staffId == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i> Filter</button>
                    </div>
                </form>

                {{-- Summary --}}
                @if(count($reportData['summary']) > 0)
                <div class="card border mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="ri-bar-chart-line me-1"></i> Rekap Per Staff ({{ $reportData['total_tasks'] }} tugas)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>NO</th>
                                        <th>NAMA STAFF</th>
                                        <th class="text-center">JUMLAH TUGAS</th>
                                        <th>PER SUMBER BOOKING</th>
                                        <th>PER JENIS KAMAR</th>
                                        <th>PER KATEGORI BONUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['summary'] as $i => $row)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $row['nama'] }}</td>
                                        <td class="text-center fw-bold">{{ $row['jumlah'] }}</td>
                                        <td>
                                            @foreach($row['by_source'] as $label => $count)
                                                <span class="badge bg-info-subtle text-info me-1 mb-1">{{ $label }}: {{ $count }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach($row['by_room_type'] as $label => $count)
                                                <span class="badge bg-secondary-subtle text-secondary me-1 mb-1">{{ $label }}: {{ $count }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach($row['by_bonus_category'] as $label => $count)
                                                <span class="badge bg-success-subtle text-success me-1 mb-1">{{ $label }}: {{ $count }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Detail Table --}}
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="ri-list-check-2 me-1"></i> Detail Tugas ({{ $reportData['total_tasks'] }} tugas)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-nowrap table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 40px;">NO</th>
                                        <th>TANGGAL</th>
                                        <th>KAMAR</th>
                                        <th>STAFF</th>
                                        <th>SUMBER BOOKING</th>
                                        <th>KATEGORI</th>
                                        <th>KATEGORI BONUS</th>
                                        <th>STATUS VERIFIKASI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['details'] as $i => $d)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $d['tanggal'] }}</td>
                                        <td>{{ $d['kamar'] }}</td>
                                        <td>{{ $d['staff'] }}</td>
                                        <td>{{ $d['sumber'] }}</td>
                                        <td class="text-center">{{ $d['kategori'] }}</td>
                                        <td class="text-center">{{ $d['kategori_bonus'] }}</td>
                                        <td class="text-center">
                                            @php
                                                $badgeClass = match($d['status_verifikasi']) {
                                                    'Selesai' => 'bg-success',
                                                    'Menunggu verifikasi' => 'bg-warning text-dark',
                                                    'Revisi' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $d['status_verifikasi'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Tidak ada data tugas housekeeping di periode ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
