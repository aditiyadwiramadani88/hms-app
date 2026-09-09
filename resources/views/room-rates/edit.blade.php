@extends('layouts.master')
@section('title')
    Edit Room Rate
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Room Rates
        @endslot
        @slot('title')
            Edit Rate
        @endslot
    @endcomponent

    <form action="{{ route('room-rates.update', $roomRate->id) }}" method="POST" data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Edit Rate Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Rate Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $roomRate->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_type_id" class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('room_type_id') is-invalid @enderror" name="room_type_id" data-choices required>
                                    <option value="">Select Room Type</option>
                                    @foreach($roomTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('room_type_id', $roomRate->room_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('room_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guest_category_id" class="form-label">Guest Category</label>
                                <select class="form-select @error('guest_category_id') is-invalid @enderror" name="guest_category_id" data-choices>
                                    <option value="">All Categories</option>
                                     @foreach($guestCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('guest_category_id', $roomRate->guest_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty to apply to all guest categories.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control flatpickr-input @error('start_date') is-invalid @enderror" 
                                       id="start_date" name="start_date" value="{{ old('start_date', $roomRate->start_date) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control flatpickr-input @error('end_date') is-invalid @enderror" 
                                       id="end_date" name="end_date" value="{{ old('end_date', $roomRate->end_date) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                       id="price" name="price" value="{{ old('price', $roomRate->price) }}" step="1000" min="0" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" id="is_locked" name="is_locked" value="1" {{ old('is_locked', $roomRate->is_locked) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_locked">
                                    Lock as Flash Sale (High Priority)
                                </label>
                            </div>
                             <small class="text-muted">If checked, this rate will override all other rates during its effective dates.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                             <a href="{{ route('room-rates.index') }}" class="btn btn-soft-secondary">Cancel</a>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Rate</button>
                        </div>
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
