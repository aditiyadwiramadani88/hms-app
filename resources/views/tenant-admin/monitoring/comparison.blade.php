@extends('layouts.master')
@section('title')
    Tenant Comparison
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenant-monitoring.index') }}">Monitoring</a>
        @endslot
        @slot('title')
            Comparison
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tenant Comparison</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tenant-monitoring.comparison') }}" method="GET">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Start Month</label>
                                <input type="month" class="form-control" name="start_month" value="{{ request('start_month', now()->startOfMonth()->format('Y-m')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Month</label>
                                <input type="month" class="form-control" name="end_month" value="{{ request('end_month', now()->format('Y-m')) }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                    <i class="ri-bar-chart-line me-1"></i> Compare
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($comparisonData->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tenant</th>
                                        <th class="text-center">Revenue</th>
                                        <th class="text-center">Transactions</th>
                                        <th class="text-center">Avg/Trans</th>
                                        <th class="text-center">Products</th>
                                        <th class="text-center">Top Product</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($comparisonData as $data)
                                    <tr>
                                        <td>{{ $data['tenant']->name }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $data['transaction_count'] }}</td>
                                        <td class="text-end">Rp {{ number_format($data['average_transaction'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $data['products_count'] }}</td>
                                        <td>{{ $data['top_product'] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">No comparison data available for selected period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
