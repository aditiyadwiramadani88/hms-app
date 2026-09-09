@extends('layouts.master')
@section('title') Laporan Maintenance @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('maintenance.records.index') }}">Maintenance</a> @endslot
        @slot('title') Laporan @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-info"></i>Laporan Maintenance per Kamar</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('maintenance.report.pdf') }}?{{ http_build_query(request()->query()) }}" class="btn btn-danger btn-sm" target="_blank">
                                <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('maintenance.report') }}" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted">Kategori</label>
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">Semua</option>
                                @php
                                    $cats = \App\Models\MaintenanceCategory::where('hotel_id', active_hotel_id())->orderBy('sort_order')->get();
                                @endphp
                                @foreach($cats as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Tampilkan</button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @if($records->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th style="min-width:90px;">TGL</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>SERVICE</th>
                                    <th>GANTI MODUL</th>
                                    <th>TAMBAH FREON</th>
                                    <th>PENGGANTIAN ALAT</th>
                                    <th>TUKANG SERVIS</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $rec)
                                <tr class="text-center">
                                    <td class="fs-12">{{ $rec->maintenance_date->format('d/m/Y') }}</td>
                                    <td class="fw-medium">{{ $rec->room?->room_number ?? 'Umum' }}</td>
                                    <td>{{ $rec->category?->name ?? '-' }}</td>
                                    <td>{!! in_array('Service', $rec->actions ?? []) ? '<i class="ri-checkbox-circle-fill text-success fs-18"></i>' : '<i class="ri-close-circle-line text-muted fs-18"></i>' !!}</td>
                                    <td>{!! in_array('Ganti Modul', $rec->actions ?? []) ? '<i class="ri-checkbox-circle-fill text-success fs-18"></i>' : '<i class="ri-close-circle-line text-muted fs-18"></i>' !!}</td>
                                    <td>{!! in_array('Tambah Freon', $rec->actions ?? []) ? '<i class="ri-checkbox-circle-fill text-success fs-18"></i>' : '<i class="ri-close-circle-line text-muted fs-18"></i>' !!}</td>
                                    <td>{!! in_array('Penggantian Alat', $rec->actions ?? []) ? '<i class="ri-checkbox-circle-fill text-success fs-18"></i>' : '<i class="ri-close-circle-line text-muted fs-18"></i>' !!}</td>
                                    <td>{{ $rec->technician_name ?? '-' }}</td>
                                    <td class="fs-12">{{ $rec->cost > 0 ? 'Rp '.number_format($rec->cost, 0, ',', '.') : '-' }}</td>
                                    <td>
                                        @if($rec->status === 'completed')
                                            <span class="badge bg-success-subtle text-success fs-10">Selesai</span>
                                        @elseif($rec->status === 'in_progress')
                                            <span class="badge bg-warning-subtle text-warning fs-10">Proses</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary fs-10">Jadwal</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="ri-inbox-line fs-48 d-block mb-2"></i>
                        <p>Tidak ada record maintenance. Silakan filter atau tambahkan data.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
