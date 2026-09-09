@extends('layouts.master')
@section('title') Ringkasan Absensi @endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Laporan Absensi @endslot
    @slot('title') Ringkasan Bulanan @endslot
@endcomponent

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h5 class="card-title mb-0">Ringkasan Absensi Karyawan</h5>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('attendance.report.summary') }}" class="d-flex gap-2 align-items-center">
                <select name="employee_id" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Semua Karyawan</option>
                    @foreach($allEmployees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
                <select name="month" class="form-select form-select-sm" style="width:auto;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
                <select name="year" class="form-select form-select-sm" style="width:auto;">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" data-submit-protect="true" class="btn btn-primary btn-sm"><i class="ri-filter-line"></i></button>
            </form>
            <a href="{{ route('attendance.report.index') }}" class="btn btn-soft-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-nowrap align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th>Karyawan</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Terlambat</th>
                        <th class="text-center">Pulang Awal</th>
                        <th class="text-center">Absen</th>
                        <th class="text-center">Total Hari</th>
                        <th class="text-center">Lembur (Jam)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $s)
                    <tr>
                        <td>{{ $s['employee_name'] }}</td>
                        <td class="text-center"><span class="badge bg-success">{{ $s['total_present'] }}</span></td>
                        <td class="text-center"><span class="badge bg-warning">{{ $s['total_late'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info">{{ $s['total_early_leave'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger">{{ $s['total_absent'] }}</span></td>
                        <td class="text-center">{{ $s['total_days'] }}</td>
                        <td class="text-center">{{ $s['total_overtime_minutes'] > 0 ? round($s['total_overtime_minutes'] / 60, 1) : 0 }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data absensi bulan ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
