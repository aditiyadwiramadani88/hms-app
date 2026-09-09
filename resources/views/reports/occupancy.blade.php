@extends('layouts.master')
@section('title')
    Occupancy Report
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
            Occupancy Report
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
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
            <div class="card" id="reportCard">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-bar-chart-fill me-2 text-primary"></i>Occupancy Report</h5>
                            <p class="text-muted mb-0">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <form action="{{ route('reports.occupancy') }}" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="date" name="start_date" class="form-control form-control-sm w-auto" value="{{ $startDate->format('Y-m-d') }}">
                                <input type="date" name="end_date" class="form-control form-control-sm w-auto" value="{{ $endDate->format('Y-m-d') }}">
                                <button type="submit" data-submit-protect="true" class="btn btn-sm btn-primary">Filter</button>
                                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="ri-arrow-left-line align-bottom me-1"></i> Back
                                </a>
                                <a href="{{ route('reports.export.excel', ['type' => 'occupancy', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-sm btn-success">
                                    <i class="ri-file-excel-line align-bottom me-1"></i> Excel
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Summary Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle">
                                <div class="card-body text-center">
                                    <h4 class="mb-1">{{ $report['total_rooms'] ?? 0 }}</h4>
                                    <p class="text-muted mb-0">Total Rooms</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success-subtle">
                                <div class="card-body text-center">
                                    <h4 class="mb-1">{{ $report['occupied_rooms'] ?? 0 }}</h4>
                                    <p class="text-muted mb-0">Occupied</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle">
                                <div class="card-body text-center">
                                    <h4 class="mb-1">{{ $report['available_rooms'] ?? 0 }}</h4>
                                    <p class="text-muted mb-0">Available</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle">
                                <div class="card-body text-center">
                                    <h4 class="mb-1">{{ number_format($report['occupancy_rate'] ?? 0, 1) }}%</h4>
                                    <p class="text-muted mb-0">Occupancy Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Room Type Breakdown --}}
                    <h5>Room Type Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Room Type</th>
                                    <th>Total Rooms</th>
                                    <th>Occupied</th>
                                    <th>Available</th>
                                    <th>Occupancy Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['room_types'] ?? [] as $type)
                                <tr>
                                    <td>{{ $type['name'] }}</td>
                                    <td>{{ $type['total'] }}</td>
                                    <td>{{ $type['occupied'] }}</td>
                                    <td>{{ $type['available'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-2">
                                                <div class="progress progress-sm" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $type['occupancy_rate'] }}%"></div>
                                                </div>
                                            </div>
                                            <span class="text-muted">{{ number_format($type['occupancy_rate'], 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No data available for the selected period.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
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