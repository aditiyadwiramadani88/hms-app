@extends('layouts.master')
@section('title')
    Edit Tenant - {{ $tenant->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('admin.tenants.index') }}">Tenants</a>
        @endslot
        @slot('li_2')
            <a href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->name }}</a>
        @endslot
        @slot('title')
            Edit
        @endslot
    @endcomponent

    <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tenant Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Business Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="owner_name" class="form-label">Owner Name *</label>
                            <input type="text" class="form-control" id="owner_name" name="owner_name" value="{{ old('owner_name', $tenant->owner_name) }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $tenant->email) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location_description" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location_description" name="location_description" value="{{ old('location_description', $tenant->location_description) }}">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $tenant->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Contract Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="rent_amount" class="form-label">Monthly Rent (Rp) *</label>
                            <input type="number" class="form-control" id="rent_amount" name="rent_amount" value="{{ old('rent_amount', $tenant->rent_amount) }}" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="rent_due_day" class="form-label">Due Day (1-28) *</label>
                            <input type="number" class="form-control" id="rent_due_day" name="rent_due_day" value="{{ old('rent_due_day', $tenant->rent_due_day) }}" min="1" max="28" required>
                        </div>
                        <div class="mb-3">
                            <label for="contract_start" class="form-label">Contract Start *</label>
                            <input type="date" class="form-control" id="contract_start" name="contract_start" value="{{ old('contract_start', $tenant->contract_start->format('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="contract_end" class="form-label">Contract End</label>
                            <input type="date" class="form-control" id="contract_end" name="contract_end" value="{{ old('contract_end', $tenant->contract_end?->format('Y-m-d')) }}">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" data-submit-protect="true" class="btn btn-primary flex-grow-1">Update Tenant</button>
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
