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
                <div class="alert alert-info py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="ri-calendar-line me-1"></i> Menampilkan tugas tanggal <strong>{{ \Carbon\Carbon::parse(request('date'))->translatedFormat('d F Y') }}</strong> saja.
                    </div>
                    <a href="{{ route('reports.housekeeping', ['month' => $month]) }}" class="btn btn-sm btn-outline-info">Lihat 1 Bulan Penuh &raquo;</a>
                </div>
                @endif

                {{-- Filters --}}
                <form method="GET" class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label fw-semibold">Pilih Tanggal (Harian)</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label fw-semibold">Bulan</label>
                        <input type="month" name="month" class="form-control" value="{{ $month }}">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label fw-semibold">Anak yang Bertugas (Staff)</label>
                        <select name="staff_id" class="form-select">
                            <option value="">Semua Staff</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ $staffId == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label fw-semibold">Urutkan Tanggal</label>
                        <select name="sort_by" class="form-select">
                            <option value="date_desc" {{ request('sort_by', 'date_desc') == 'date_desc' ? 'selected' : '' }}>Tanggal Terbaru</option>
                            <option value="date_asc" {{ request('sort_by') == 'date_asc' ? 'selected' : '' }}>Tanggal Terlama</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12 d-flex gap-1">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line me-1"></i> Filter</button>
                        @if(request()->filled('date') || request()->filled('staff_id'))
                            <a href="{{ route('reports.housekeeping', ['month' => $month]) }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="ri-refresh-line"></i></a>
                        @endif
                    </div>
                </form>

                {{-- Laporan Per Anak Yang Bertugas --}}
                @if(!empty($reportData['grouped_by_staff']) && count($reportData['grouped_by_staff']) > 0)
                    @foreach($reportData['grouped_by_staff'] as $staffName => $tasks)
                    <div class="card border mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fs-14">
                                <i class="ri-user-star-line text-primary me-2"></i><strong>Staff: {{ $staffName }}</strong>
                            </h6>
                            <span class="badge bg-primary-subtle text-primary">{{ count($tasks) }} Kamar Selesai</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-nowrap table-sm mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">NO</th>
                                            <th>TANGGAL</th>
                                            <th>KAMAR YANG DIKERJAKAN</th>
                                            <th>NAMA STAFF</th>
                                            <th>SUMBER BOOKING</th>
                                            <th>KATEGORI BONUS</th>
                                            <th class="text-center">STATUS VERIFIKASI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tasks as $i => $d)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $d['tanggal'] }}</td>
                                            <td class="fw-semibold text-dark">{{ $d['kamar'] }}</td>
                                            <td>{{ $d['staff'] }}</td>
                                            <td><span class="badge bg-info-subtle text-info">{{ $d['sumber'] }}</span></td>
                                            <td><span class="badge bg-light text-dark border">{{ $d['kategori_bonus'] }}</span></td>
                                            <td class="text-center">
                                                <span class="badge bg-success">Selesai / Approved</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="card border">
                        <div class="card-body text-center text-muted py-5">
                            <i class="ri-inbox-line fs-1 text-muted d-block mb-2"></i>
                            Tidak ada data tugas housekeeping di periode ini.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
