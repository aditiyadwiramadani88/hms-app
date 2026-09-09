@extends('layouts.master')
@section('title')
    {{ __('translation.revenue_financial_report') }}
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.reports') }}
        @endslot
        @slot('title')
            {{ __('translation.revenue_financial_report') }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <form action="{{ route('reports.generate') }}" method="GET">
                        <input type="hidden" name="report_type" value="revenue">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm">
                                <h5 class="card-title mb-0"><i class="ri-funds-box-line me-2 text-primary"></i>{{ __('translation.revenue_financial_report') }}</h5>
                                <p class="text-muted mb-0">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex gap-2">
                                    <div class="input-group">
                                        <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                                        <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Filter</button>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-download-2-line align-bottom me-1"></i> Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-menu-item dropdown-item" href="{{ route('reports.export.excel', ['type' => 'revenue', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}">
                                                    <i class="ri-file-excel-2-line align-bottom me-2 text-success"></i> Excel (.xlsx)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-menu-item dropdown-item" href="{{ route('reports.export.pdf', ['type' => 'revenue', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}">
                                                    <i class="ri-file-pdf-line align-bottom me-2 text-danger"></i> PDF (.pdf)
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    {{-- Financial Summary Row --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="text-muted text-uppercase fw-semibold fs-11 mb-2">{{ __('translation.room_revenue') }}</p>
                                            <h4 class="mb-0">Rp {{ number_format($report['room_revenue']['total'] ?? 0, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-hotel-bed-line fs-24 text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="text-muted text-uppercase fw-semibold fs-11 mb-2">{{ __('translation.pos_revenue') }}</p>
                                            <h4 class="mb-0">Rp {{ number_format($report['pos_revenue']['total'] ?? 0, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-shopping-cart-2-line fs-24 text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="text-muted text-uppercase fw-semibold fs-11 mb-2">{{ __('translation.total_amount') }} Expenses</p>
                                            <h4 class="mb-0">Rp {{ number_format($report['expenses'] ?? 0, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-shopping-bag-3-line fs-24 text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 text-white">
                                            <p class="text-white-50 text-uppercase fw-semibold fs-11 mb-2">{{ __('translation.net_profit_realized') }}</p>
                                            <h4 class="mb-0 text-white">Rp {{ number_format($report['net_profit'] ?? 0, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-line-chart-line fs-24 text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Left Column: Revenue Breakdown --}}
                        <div class="col-lg-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">{{ __('translation.revenue_category_method') }}</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-borderless table-nowrap align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Category/Source</th>
                                                <th class="text-end">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="fw-bold">
                                                <td colspan="2" class="text-primary">{{ __('translation.room_revenue') }}</td>
                                            </tr>
                                            @foreach($report['room_revenue']['by_source'] ?? [] as $source => $data)
                                            <tr>
                                                <td class="ps-4">{{ ucfirst($source) }}</td>
                                                <td class="text-end">Rp {{ number_format($data['total_invoice_value'], 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                            
                                            <tr class="fw-bold border-top">
                                                <td colspan="2" class="text-info">{{ __('translation.pos_revenue') }}</td>
                                            </tr>
                                            @foreach($report['pos_revenue']['by_method'] ?? [] as $method => $data)
                                            <tr>
                                                <td class="ps-4">{{ ucfirst($method) }}</td>
                                                <td class="text-end">Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light border-top">
                                            <tr>
                                                <th class="fw-bold">{{ __('translation.total_gross_revenue') }}</th>
                                                <th class="text-end fw-bold">Rp {{ number_format($report['gross_revenue'] ?? 0, 0, ',', '.') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- New Section: Uang yang Disetor (By Account) --}}
                            <div class="card border mt-4">
                                <div class="card-header bg-success-subtle">
                                    <h6 class="card-title mb-0 text-success">{{ __('translation.deposited_money_by_account') }}</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-borderless table-nowrap align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('translation.account_name') }}</th>
                                                <th class="text-center">{{ __('translation.trans_count') }}</th>
                                                <th class="text-end">{{ __('translation.total_amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($report['payments']['by_account'] ?? [] as $acc)
                                            <tr>
                                                <td>{{ $acc->account_name }}</td>
                                                <td class="text-center">{{ $acc->transaction_count }}</td>
                                                <td class="text-end fw-medium">Rp {{ number_format($acc->total_amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">No payments recorded.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light border-top">
                                            <tr>
                                                <th colspan="2" class="fw-bold">{{ __('translation.total_realized_revenue') }}</th>
                                                <th class="text-end fw-bold text-success">Rp {{ number_format($report['payments']['total'] ?? 0, 0, ',', '.') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <div class="p-2 bg-light-subtle">
                                        <small class="text-muted italic">{{ __('translation.realized_revenue_info') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Source & Room Performance --}}
                        <div class="col-lg-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0"><i class="ri-share-forward-line me-1 text-primary"></i>Sumber Booking</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-borderless table-nowrap align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th>Sumber</th>
                                                <th class="text-end">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($sourcePerformance ?? [] as $source)
                                            <tr>
                                                @php $textColor = in_array($source->source_color, ['light', 'white']) ? 'dark' : $source->source_color; @endphp
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $source->source_name }}</span></td>
                                                <td class="text-end">Rp {{ number_format($source->total_revenue, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-muted">No data.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card border mt-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">{{ __('translation.room_type_performance') }}</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-borderless table-nowrap align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th>{{ __('translation.room_types') }}</th>
                                                <th class="text-center">{{ __('translation.bookings') }}</th>
                                                <th class="text-end">{{ __('translation.revenue_report') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($roomTypePerformance ?? [] as $type)
                                            <tr>
                                                <td>{{ $type->room_type_name }}</td>
                                                <td class="text-center">{{ $type->total_bookings }}</td>
                                                <td class="text-end">Rp {{ number_format($type->total_revenue, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4">No data.</td>
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
    </div>
@endsection
@section('script')
@endsection
