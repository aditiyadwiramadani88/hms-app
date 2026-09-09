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
        <div class="col-lg-8">
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
                            <p class="mb-1"><span class="text-muted">Status:</span> {!! $customInvoice->status_badge !!}</p>
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
                                    <th class="text-end">Sell Price</th>
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
                                    <td class="text-end">Rp {{ number_format($customInvoice->agent_commission, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($customInvoice->sell_price, 0, ',', '.') }}</td>
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

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="ri-settings-3-line me-2"></i>Status Management</h6>
                </div>
                <div class="card-body">
                    @if($customInvoice->status !== 'paid' && $customInvoice->status !== 'cancelled')
                        @can('custom-invoices.edit')
                        <div class="d-grid gap-2">
                            @if($customInvoice->status === 'draft')
                            <form action="{{ route('custom-invoices.mark-sent', $customInvoice->id) }}" method="POST" data-ajax="true" data-ajax-reload="true">
                                @csrf
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="ri-send-plane-line me-1"></i> Mark as Sent
                                </button>
                            </form>
                            @endif
                        </div>
                        @endcan

                        @can('custom-invoices.mark-paid')
                        <form action="{{ route('custom-invoices.mark-paid', $customInvoice->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" class="mt-2">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">-- Select --</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="qris">QRIS</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ri-check-double-line me-1"></i> Mark as Paid
                            </button>
                        </form>
                        @endcan

                        @can('custom-invoices.edit')
                        <form action="{{ route('custom-invoices.mark-cancelled', $customInvoice->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this invoice?')">
                                <i class="ri-close-circle-line me-1"></i> Cancel Invoice
                            </button>
                        </form>
                        @endcan
                    @else
                        <div class="alert alert-{{ $customInvoice->status === 'paid' ? 'success' : 'secondary' }} mb-0">
                            <i class="ri-{{ $customInvoice->status === 'paid' ? 'check' : 'close' }}-circle-line me-1"></i>
                            Invoice is <strong>{{ ucfirst($customInvoice->status) }}</strong>
                            @if($customInvoice->paid_at)
                                <br><small>Paid at: {{ $customInvoice->paid_at->format('d M Y H:i') }}</small>
                            @endif
                            @if($customInvoice->payment_method)
                                <br><small>Method: {{ ucfirst(str_replace('_', ' ', $customInvoice->payment_method)) }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @can('custom-invoices.delete')
            <div class="card border-danger">
                <div class="card-body">
                    <form action="{{ route('custom-invoices.destroy', $customInvoice->id) }}" method="POST" onsubmit="return confirm('Delete this invoice permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ri-delete-bin-line me-1"></i> Delete Invoice
                        </button>
                    </form>
                </div>
            </div>
            @endcan
        </div>
    </div>
@endsection
