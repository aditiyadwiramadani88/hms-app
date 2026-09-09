@extends('layouts.master')
@section('title')
    Edit {{ $roomType->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('li_2')
            <a href="{{ route('room-types.index') }}">Room Types</a>
        @endslot
        @slot('title')
            Edit {{ $roomType->name }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Room Type</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('room-types.update', $roomType) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $roomType->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $roomType->description }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Price (Rp)</label>
                                <input type="number" name="base_price" class="form-control" value="{{ $roomType->base_price }}" min="0" step="1000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Guests</label>
                                <input type="number" name="max_guests" class="form-control" value="{{ $roomType->max_guests }}" min="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Size (m²)</label>
                                <input type="number" name="size_sqm" class="form-control" value="{{ $roomType->size_sqm }}" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $roomType->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Image</label>
                                @if($roomType->image)
                                    <div class="mb-1">
                                        <img src="{{ Storage::url($roomType->image) }}" alt="Current" style="max-height: 60px; border-radius: 4px;">
                                    </div>
                                @endif
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Max 2MB. jpg, png, webp.</small>
                            </div>
                        </div>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update</button>
                        <a href="{{ route('room-types.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection