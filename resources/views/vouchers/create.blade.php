@extends('layouts.master')
@section('title')
    Add New Voucher
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Vouchers
        @endslot
        @slot('title')
            Add New Voucher
        @endslot
    @endcomponent

    <form action="{{ route('vouchers.store') }}" method="POST" data-ajax="true">
        @csrf
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Voucher Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Voucher Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="code" name="code" value="{{ old('code') }}" 
                                       placeholder="e.g. SUMMER2024" required style="text-transform: uppercase;">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Voucher Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Fixed Amount (Rp)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('value') is-invalid @enderror" 
                                       id="value" name="value" value="{{ old('value') }}" step="0.01" required>
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_booking_amount" class="form-label">Min Booking Amount</label>
                                <input type="number" class="form-control" id="min_booking_amount" name="min_booking_amount" value="{{ old('min_booking_amount', 0) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_discount" class="form-label">Max Discount (for % type)</label>
                                <input type="number" class="form-control" id="max_discount" name="max_discount" value="{{ old('max_discount') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}" placeholder="∞">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="valid_from" class="form-label">Valid From</label>
                                <input type="text" class="form-control flatpickr-input" id="valid_from" name="valid_from" value="{{ old('valid_from') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="valid_until" class="form-label">Valid Until <span class="text-danger">*</span></label>
                                <input type="text" class="form-control flatpickr-input" id="valid_until" name="valid_until" value="{{ old('valid_until') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Applicable Room Types</label>
                            <div class="row">
                                @foreach($roomTypes as $type)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="applicable_room_types[]" value="{{ $type->id }}" id="rt_{{ $type->id }}">
                                            <label class="form-check-label" for="rt_{{ $type->id }}">{{ $type->name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">If none selected, it applies to all room types.</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Voucher is Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('vouchers.index') }}" class="btn btn-soft-secondary">Cancel</a>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Save Voucher</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        flatpickr('.flatpickr-input');
    </script>
@endsection
