@extends('layouts.master')
@section('title')
    Bonus Detail - {{ $ob->name }}
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('bonus-reports.index') }}?month={{ $month->format('Y-m') }}">Bonus Report</a> @endslot
        @slot('title') {{ $ob->name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-user-heart-fill me-2 text-primary"></i>
                                Bonus Detail: {{ $ob->name }}
                                <span class="badge bg-info ms-2">{{ $month->format('F Y') }}</span>
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('bonus-reports.index') }}?month={{ $month->format('Y-m') }}" class="btn btn-soft-secondary btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Summary Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-success-subtle border-0 mb-0 h-100">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Total Bonus</h6>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($detail['total_bonus'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-info-subtle border-0 mb-0 h-100">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <h6 class="text-muted text-uppercase fs-12 mb-2">Total Tasks Completed</h6>
                                    <h4 class="mb-0">{{ count($detail['task_list']) }} Tasks</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Completed Tasks --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card border shadow-none mb-0">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="ri-task-line me-1"></i>
                                        Completed Work Orders
                                        <span class="badge bg-success ms-2">{{ count($detail['task_list']) }} tasks</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive table-card">
                                        <table class="table table-nowrap align-middle mb-0">
                                            <thead class="table-light text-muted">
                                                <tr>
                                                    <th class="text-uppercase">#</th>
                                                    <th class="text-uppercase">Work Order ID</th>
                                                    <th class="text-uppercase">Room</th>
                                                    <th class="text-uppercase">Category</th>
                                                    <th class="text-uppercase">Completed At</th>
                                                    <th class="text-uppercase">Keterangan</th>
                                                    <th class="text-uppercase text-end">Bonus Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($detail['task_list'] as $index => $task)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>#{{ $task['work_order_id'] }}</td>
                                                        <td>{{ $task['room_number'] }}</td>
                                                        <td>
                                                            <span class="badge bg-info-subtle text-info text-uppercase">{{ $task['category'] }}</span>
                                                        </td>
                                                        <td>{{ $task['completed_at'] }}</td>
                                                        <td>{{ Str::limit($task['keterangan'] ?? '-', 60) }}</td>
                                                        <td class="text-end fw-medium">Rp {{ number_format($task['bonus_amount'], 0, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center py-4">
                                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:50px;height:50px"></lord-icon>
                                                            <h5 class="mt-3">No Completed Tasks</h5>
                                                            <p class="text-muted">No work orders completed this month.</p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-light fw-bold">
                                                    <td colspan="6" class="text-end">Total Bonus</td>
                                                    <td class="text-end">Rp {{ number_format($detail['total_bonus'], 0, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
