@extends('layouts.master')
@section('title')
    Generate Billings
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenant-billings.index') }}">Billings</a>
        @endslot
        @slot('title')
            Generate Billings
        @endslot
    @endcomponent

    <form action="{{ route('admin.tenant-billings.generate.store') }}" method="POST" data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Generate Monthly Billings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="month" class="form-label">Billing Month *</label>
                            <input type="month" class="form-control" id="month" name="month" value="{{ old('month', now()->format('Y-m')) }}" required>
                            <small class="text-muted">Select the month for billing period (e.g., 2026-05 = May 2026)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Tenants</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                <label class="form-check-label" for="selectAll">Select All Active Tenants</label>
                            </div>
                            <hr>
                            @forelse($tenants as $tenant)
                                <div class="form-check">
                                    <input class="form-check-input tenant-checkbox" type="checkbox" name="tenant_ids[]" id="tenant_{{ $tenant->id }}" value="{{ $tenant->id }}">
                                    <label class="form-check-label" for="tenant_{{ $tenant->id }}">
                                        {{ $tenant->name }} - Rp {{ number_format($tenant->rent_amount, 0, ',', '.') }}/month
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No active tenants found.</p>
                            @endforelse
                        </div>

                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            Billings will be created with the amount based on each tenant's monthly rent.
                            Tenants that already have a billing for the selected month will be skipped.
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" data-submit-protect="true" class="btn btn-success">
                            <i class="ri-file-add-line align-bottom me-1"></i> Generate Billings
                        </button>
                        <a href="{{ route('admin.tenant-billings.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.tenant-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>
@endsection
