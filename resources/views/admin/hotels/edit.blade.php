@extends('layouts.master')
@section('title')
    {{ isset($hotel) ? 'Edit Branch' : 'Create Branch' }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('li_2')
            Branch Management
        @endslot
        @slot('title')
            {{ isset($hotel) ? 'Edit Branch' : 'Create Branch' }}
        @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h5 class="card-title mb-0"><i class="ri-building-line me-2 text-primary"></i>Branch Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ isset($hotel) ? route('hotels.update', $hotel->id) : route('hotels.store') }}" method="POST" data-ajax="true">
                        @csrf
                        @if(isset($hotel))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $hotel->code ?? '') }}" placeholder="e.g. BDG-01" required {{ isset($hotel) && $hotel->code === 'DEFAULT' ? 'readonly' : '' }}>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $hotel->name ?? '') }}" placeholder="e.g. Hotel Boshe Bandung" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Full address...">{{ old('address', $hotel->address ?? '') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $hotel->phone ?? '') }}" placeholder="e.g. 022-123456">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', $hotel->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $hotel->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="{{ route('hotels.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary px-4">
                                <i class="ri-save-line me-1"></i> {{ isset($hotel) ? 'Update Branch' : 'Create Branch' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
