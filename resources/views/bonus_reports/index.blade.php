@extends('layouts.master')
@section('title')
    Bonus Report
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Reports @endslot
        @slot('title') Bonus Report @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-funds-fill me-2 text-primary"></i>Monthly Bonus Report</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <form method="GET" action="{{ route('bonus-reports.index') }}" class="d-flex gap-2 align-items-center">
                                    <input type="month" class="form-control form-control-sm" name="month" value="{{ $month->format('Y-m') }}">
                                    <button type="submit" data-submit-protect="true" class="btn btn-primary btn-sm"><i class="ri-search-line align-bottom me-1"></i> View</button>
                                </form>
                                <a href="{{ route('bonus-reports.export.excel') }}?month={{ $month->format('Y-m') }}" class="btn btn-success btn-sm">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Export Excel
                                </a>
                                <a href="{{ route('bonus-reports.export.pdf') }}?month={{ $month->format('Y-m') }}" class="btn btn-danger btn-sm">
                                    <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Summary Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary-subtle border-0 mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Month</h6>
                                    <h4 class="mb-0">{{ $month->format('F Y') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success-subtle border-0 mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Active OB</h6>
                                    <h4 class="mb-0">{{ $report['active_ob_count'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning-subtle border-0 mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Total Bonus Pool</h6>
                                    <h4 class="mb-0">Rp {{ number_format($report['total_bonus_pool'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-info-subtle border-0 mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Report Date</h6>
                                    <h4 class="mb-0">{{ now()->format('d/m/Y') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bonus Detail Grid per OB --}}
                    <div class="row g-4">
                        @forelse($report['bonus_details'] as $ob)
                            <div class="col-md-6 col-xl-4">
                                <div class="card border border-dark shadow-none mb-0 h-100">
                                    <div class="card-header bg-light border-bottom border-dark py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="card-title mb-0 text-uppercase fw-bold">{{ $ob['ob_name'] }}</h6>
                                        <a href="{{ route('bonus-reports.show', $ob['ob_id']) }}?month={{ $month->format('Y-m') }}" class="btn btn-sm btn-link p-0 text-primary">Detail</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-bordered border-dark mb-0 text-center align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Room</th>
                                                    <th>Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(['sales', 'umum', 'online', 'kos', 'pk', 'kosong'] as $cat)
                                                    @php
                                                        $data = $ob['breakdown'][$cat];
                                                        $labels = ['sales'=>'Sales', 'umum'=>'Umum', 'online'=>'Online', 'kos'=>'kost', 'pk'=>'P.K', 'kosong'=>'Kosong'];
                                                    @endphp
                                                    @if($cat === 'online')
                                                        @foreach(['reddoors' => 'REDDOORS', 'traveloka' => 'Traveloka', 'agoda' => 'Agoda', 'lainnya' => 'Online Lainnya'] as $srcKey => $srcLabel)
                                                            @php $srcCount = $data['sources'][$srcKey] ?? 0; @endphp
                                                            <tr>
                                                                <td class="text-start ps-2 fw-medium">{{ $srcLabel }}</td>
                                                                <td class="text-primary">{{ $srcCount }}</td>
                                                                <td class="text-end pe-2">{{ number_format($srcCount * $data['rate'], 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td class="text-start ps-2 fw-medium">{{ $labels[$cat] }}</td>
                                                            <td class="text-primary">{{ $data['count'] > 0 ? $data['count'] : '0' }}</td>
                                                            <td class="text-end pe-2">{{ number_format($data['amount'], 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2" class="text-start ps-2">Total</td>
                                                    <td class="text-end pe-2">{{ number_format($ob['total_bonus'], 0, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px"></lord-icon>
                                    <h5 class="mt-3">No Data</h5>
                                    <p class="text-muted">No active OB found for this month.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Total Summary like bottom of paper --}}
                    @if(count($report['bonus_details']) > 0)
                    <div class="row mt-4">
                        <div class="col-md-5 col-xl-4 ms-auto">
                            <div class="card border-0 shadow-none mb-0">
                                <div class="card-body p-0">
                                    <table class="table table-sm table-borderless mb-0 fs-14">
                                        @foreach($report['bonus_details'] as $ob)
                                        <tr>
                                            <td class="text-uppercase fw-medium px-0 py-1">{{ $ob['ob_name'] }}</td>
                                            <td class="text-end px-0 py-1">{{ number_format($ob['total_bonus'], 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                        <tr class="fw-bold border-top border-dark border-2">
                                            <td class="px-0 py-2">TOTAL</td>
                                            <td class="text-end px-0 py-2 fs-15">{{ number_format($report['total_bonus_pool'], 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
