@extends('layouts.master')
@section('title')
    Invoice {{ $customInvoice->invoice_number }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Custom Invoices @endslot
        @slot('title') {{ $customInvoice->invoice_number }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-list-3-line me-2 text-primary"></i>{{ $customInvoice->invoice_number }}
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                @can('custom-invoices.print')
                                <a href="{{ route('custom-invoices.print', $customInvoice->id) }}" class="btn btn-soft-success btn-sm" target="_blank">
                                    <i class="ri-printer-line me-1"></i> Print
                                </a>
                                @endcan
                                @can('custom-invoices.edit')
                                <a href="{{ route('custom-invoices.edit', $customInvoice->id) }}" class="btn btn-soft-warning btn-sm">
                                    <i class="ri-edit-line me-1"></i> Edit
                                </a>
                                @endcan
                                @can('custom-invoices.delete')
                                <form action="{{ route('custom-invoices.destroy', $customInvoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this invoice permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-soft-danger btn-sm">
                                        <i class="ri-delete-bin-line me-1"></i> Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Guest Information</h6>
                            <p class="mb-1"><strong>{{ $customInvoice->guest->name ?? '-' }}</strong></p>
                            <p class="mb-1 text-muted">{{ $customInvoice->guest->email ?? '' }}</p>
                            <p class="mb-0 text-muted">{{ $customInvoice->guest->phone ?? '' }}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted mb-2">Invoice Details</h6>
                            <p class="mb-1"><span class="text-muted">Created:</span> {{ $customInvoice->created_at->format('d M Y H:i') }}</p>
                            <p class="mb-0"><span class="text-muted">By:</span> {{ $customInvoice->creator->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Source</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Nights</th>
                                    <th class="text-end">Tarif / Malam</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $customInvoice->room_name }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $customInvoice->source }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($customInvoice->check_in)->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($customInvoice->check_out)->format('d M Y') }}</td>
                                    <td>{{ $customInvoice->nights }}</td>
                                    <td class="text-end">Rp {{ number_format($customInvoice->sell_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold text-primary">Rp {{ number_format($customInvoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($customInvoice->agent_commission, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="6" class="text-end fw-bold">Total Tagihan:</td>
                                    <td class="text-end fw-bold text-primary">Rp {{ number_format($customInvoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($customInvoice->agent_commission, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($customInvoice->notes)
                    <div class="mt-3">
                        <h6 class="text-muted">Notes</h6>
                        <p class="mb-0">{{ $customInvoice->notes }}</p>
                    </div>
                    @endif

                    @if($customInvoice->booking_id)
                    <div class="mt-3">
                        <h6 class="text-muted">Linked Booking</h6>
                        <a href="{{ route('bookings.show', $customInvoice->booking_id) }}" class="btn btn-soft-primary btn-sm">
                            <i class="ri-link me-1"></i> Booking #{{ $customInvoice->booking_id }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
