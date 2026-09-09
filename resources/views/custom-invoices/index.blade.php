@extends('layouts.master')
@section('title')
    Custom Invoices
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 0.25rem !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px !important; padding-left: 12px !important; color: #495057 !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Front Office @endslot
        @slot('title') Custom Invoices @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-primary"></i>Custom Invoices</h5>
                        </div>
                        <div class="col-md-8 text-end">
                            @can('custom-invoices.create')
                            <a href="{{ route('custom-invoices.create') }}" class="btn btn-primary">
                                <i class="ri-add-line me-1"></i> New Custom Invoice
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="search" placeholder="Search guest/invoice..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="source">
                                <option value="">All Sources</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source }}" {{ request('source') == $source ? 'selected' : '' }}>{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" placeholder="From">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" placeholder="To">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-centered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Guest</th>
                                    <th>Source</th>
                                    <th>Room</th>
                                    <th>Check-in/out</th>
                                    <th class="text-end">Sell Price</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('custom-invoices.show', $invoice->id) }}" class="fw-medium text-decoration-none">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->guest->name ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $invoice->source }}</span></td>
                                    <td>{{ $invoice->room_name }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($invoice->check_in)->format('d M Y') }} - {{ \Carbon\Carbon::parse($invoice->check_out)->format('d M Y') }}
                                            <br>{{ $invoice->nights }} night(s)
                                        </small>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($invoice->sell_price, 0, ',', '.') }}</td>
                                    <td class="text-center">{!! $invoice->status_badge !!}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('custom-invoices.show', $invoice->id) }}" class="btn btn-soft-primary" title="View"><i class="ri-eye-line"></i></a>
                                            @can('custom-invoices.edit')
                                            <a href="{{ route('custom-invoices.edit', $invoice->id) }}" class="btn btn-soft-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                            @endcan
                                            @can('custom-invoices.print')
                                            <a href="{{ route('custom-invoices.print', $invoice->id) }}" class="btn btn-soft-success" title="Print" target="_blank"><i class="ri-printer-line"></i></a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No custom invoices found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
