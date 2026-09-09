@extends('layouts.master')
@section('title', 'Kelola Karyawan Jadwal')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Kelola Karyawan di Jadwal</h4>
            <a href="{{ route('employee-schedules.index') }}" class="btn btn-primary btn-sm">
                <i class="ri-arrow-left-line"></i> Kembali ke Jadwal
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-success-subtle border-0 mb-0">
            <div class="card-body p-3 text-center">
                <h4 class="mb-0 text-success">{{ $allStaff->where('show_in_schedule', true)->count() }}</h4>
                <small class="text-success">Ditampilkan</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger-subtle border-0 mb-0">
            <div class="card-body p-3 text-center">
                <h4 class="mb-0 text-danger">{{ $allStaff->where('show_in_schedule', false)->count() }}</h4>
                <small class="text-danger">Disembunyikan</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info-subtle border-0 mb-0">
            <div class="card-body p-3 text-center">
                <h4 class="mb-0 text-info">{{ $allStaff->count() }}</h4>
                <small class="text-info">Total Karyawan</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pilih karyawan yang ditampilkan di halaman Jadwal Bulanan</h5>
        <small class="text-muted">Karyawan yang di-Hide tidak akan muncul di grid jadwal, tapi data jadwalnya tetap tersimpan.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Status</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allStaff as $staff)
                    <tr class="{{ $staff->show_in_schedule ? '' : 'table-danger bg-opacity-10' }}">
                        <td class="text-center">
                            @if($staff->show_in_schedule)
                                <span class="badge bg-success"><i class="ri-eye-line me-1"></i>Tampil</span>
                            @else
                                <span class="badge bg-danger"><i class="ri-eye-off-line me-1"></i>Hidden</span>
                            @endif
                        </td>
                        <td class="{{ $staff->show_in_schedule ? 'fw-medium' : 'text-muted text-decoration-line-through' }}">{{ $staff->name }}</td>
                        <td class="{{ $staff->show_in_schedule ? '' : 'text-muted' }}">{{ $staff->email }}</td>
                        <td>
                            <form method="POST" action="{{ route('employee-schedules.toggle-visibility', $staff->id) }}" class="d-inline">
                                @csrf
                                @if($staff->show_in_schedule)
                                    <button type="submit" class="btn btn-sm btn-danger" title="Sembunyikan dari jadwal">
                                        <i class="ri-eye-off-line me-1"></i> Hide
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-success" title="Tampilkan di jadwal">
                                        <i class="ri-eye-line me-1"></i> Show
                                    </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
