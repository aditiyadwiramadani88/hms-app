@extends('layouts.master')
@section('title')
    Data Kost Tenant List
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Reports
        @endslot
        @slot('title')
            Data Kost Tenant List
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-team-line me-2 text-primary"></i>
                        SIMPANG HOMESTAY & KOZZ - LAPORAN KOST
                    </h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ri-download-2-line align-bottom me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('reports.kost.tenant-list.export.excel') }}">
                                        <i class="ri-file-excel-2-line me-2 text-success"></i> Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('reports.kost.tenant-list.export.pdf') }}">
                                        <i class="ri-file-pdf-line me-2 text-danger"></i> PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <div class="card-header pt-0 pb-0 border-0">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('reports.kost') }}">
                                <i class="ri-home-4-line me-1"></i> Laporan Bulanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('reports.kost.tenant-list') }}">
                                <i class="ri-team-line me-1"></i> Data Kos
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th class="text-center" style="width: 80px;">Kamar</th>
                                    <th class="text-center" style="width: 50px;">Isi</th>
                                    <th>Nama Penghuni</th>
                                    <th>No Handphone</th>
                                    <th>Nama Ortu</th>
                                    <th>Handphone Ortu</th>
                                    <th class="text-end" style="width: 120px;">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tenantData as $row)
                                <tr>
                                    <td class="text-center">{{ $row['no'] }}</td>
                                    <td class="text-center fw-bold">{{ $row['kamar'] }}</td>
                                    <td class="text-center">{{ $row['isi'] }}</td>
                                    <td>{{ $row['nama_penghuni'] }}</td>
                                    <td>{{ $row['no_handphone'] }}</td>
                                    <td>{{ $row['nama_ortu'] }}</td>
                                    <td>{{ $row['handphone_ortu'] }}</td>
                                    <td class="text-end">
                                        @if($row['harga'] > 0)
                                            {{ number_format($row['harga'], 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-building-2-line fs-1 d-block mb-2"></i>
                                            Tidak ada data penghuni kost.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($tenantData) > 0)
                            @php
                                $totalIsi = collect($tenantData)->sum('isi');
                                $totalPenghuni = collect($tenantData)->filter(fn($r) => $r['nama_penghuni'] !== '-')->count();
                                $totalHarga = collect($tenantData)->sum('harga');
                            @endphp
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">TOTAL</td>
                                    <td class="text-center">{{ $totalIsi }}</td>
                                    <td>{{ $totalPenghuni }} penghuni</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
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
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection
