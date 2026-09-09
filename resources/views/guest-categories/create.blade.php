@extends('layouts.master')
@section('title')
    Add New Guest Category
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Guest Categories
        @endslot
        @slot('title')
            Add New Category
        @endslot
    @endcomponent

    <form action="{{ route('guest-categories.store') }}" method="POST" data-ajax="true">
        @csrf
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Category Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="e.g., General, Corporate, VIP" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="2"
                                      placeholder="e.g., Special rate for partner companies">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="breakfast_price" class="form-label">Breakfast Price (Flat) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('breakfast_price') is-invalid @enderror"
                                       id="breakfast_price" name="breakfast_price" value="{{ old('breakfast_price', 0) }}"
                                       required>
                            </div>
                            <small class="text-muted">Harga sarapan per orang per malam untuk kategori ini.</small>
                            @error('breakfast_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Category Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code') }}"
                                   placeholder="e.g., GEN, CORP, VIP" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                             <a href="{{ route('guest-categories.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                <i class="ri-save-line me-1 align-bottom"></i> Save Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
