@extends('layouts.master')
@section('title')
    Mark Billing as Paid
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenant-billings.index') }}">Billings</a>
        @endslot
        @slot('li_2')
            <a href="{{ route('admin.tenant-billings.show', $billing) }}">{{ $billing->billing_period }}</a>
        @endslot
        @slot('title')
            Mark as Paid
        @endslot
    @endcomponent

    <form action="{{ route('admin.tenant-billings.mark-paid.store', $billing) }}" method="POST" data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mark Billing as Paid</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>{{ $billing->tenant->name }}</strong> - Period: {{ $billing->billing_period }}<br>
                            Amount Due: <strong>Rp {{ number_format($billing->amount, 0, ',', '.') }}</strong>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount Paid (Rp) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" value="{{ old('amount', $billing->amount) }}" min="0" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Payment method, bank transfer details, etc.">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" data-submit-protect="true" class="btn btn-success">
                            <i class="ri-check-line align-bottom me-1"></i> Confirm Payment
                        </button>
                        <a href="{{ route('admin.tenant-billings.show', $billing) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
