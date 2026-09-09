@extends('layouts.master')
@section('title')
    Add New Room
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Rooms
        @endslot
        @slot('title')
            Add New Room
        @endslot
    @endcomponent

    <form action="{{ route('rooms.store') }}" method="POST" data-ajax="true" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-hotel-bed-fill me-2 text-primary"></i>Room Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('room_number') is-invalid @enderror"
                                   id="room_number" name="room_number" value="{{ old('room_number') }}"
                                   placeholder="e.g., 101, 205, 310" required>
                            @error('room_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room_type_id" class="form-label">Room Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('room_type_id') is-invalid @enderror"
                                            id="room_type_id" name="room_type_id" required>
                                        <option value="">Select Room Type</option>
                                        @foreach($roomTypes ?? [] as $type)
                                            <option value="{{ $type->id }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }} - {{ $type->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('room_type_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="floor" class="form-label">Floor <span class="text-danger">*</span></label>
                                    <select class="form-select @error('floor') is-invalid @enderror"
                                            id="floor" name="floor" required>
                                        <option value="">Select Floor</option>
                                        @for($i = 1; $i <= 20; $i++)
                                            <option value="{{ $i }}" {{ old('floor') == $i ? 'selected' : '' }}>Floor {{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('floor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="room_state" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="room_state" name="status" required>
                                <option value="" selected>Select Status</option>
                                @foreach($statuses ?? [] as $status)
                                <option value="{{ $status->name }}" {{ old('status') === $status->name ? 'selected' : '' }}>{{ trim($status->name) }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes" name="notes" rows="3"
                                      placeholder="Any special notes about this room...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Room Pricing Card --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-fill me-2 text-success"></i>Room Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price_public" class="form-label">Harga Umum <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_public') is-invalid @enderror"
                                           id="price_public" name="price_public"
                                           value="{{ old('price_public', 0) }}"
                                           placeholder="Harga Umum" step="1000" min="0" required>
                                    @error('price_public')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_public" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_public') is-invalid @enderror"
                                           id="price_breakfast_public" name="price_breakfast_public"
                                           value="{{ old('price_breakfast_public', 0) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_public')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price_sales" class="form-label">Harga Sales <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_sales') is-invalid @enderror"
                                           id="price_sales" name="price_sales"
                                           value="{{ old('price_sales', 0) }}"
                                           placeholder="Harga Sales" step="1000" min="0" required>
                                    @error('price_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_sales" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_sales') is-invalid @enderror"
                                           id="price_breakfast_sales" name="price_breakfast_sales"
                                           value="{{ old('price_breakfast_sales', 0) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price_high_season" class="form-label">Harga High Season <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_high_season') is-invalid @enderror"
                                           id="price_high_season" name="price_high_season"
                                           value="{{ old('price_high_season', 0) }}"
                                           placeholder="Harga High Season" step="1000" min="0" required>
                                    @error('price_high_season')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_high_season" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_high_season') is-invalid @enderror"
                                           id="price_breakfast_high_season" name="price_breakfast_high_season"
                                           value="{{ old('price_breakfast_high_season', 0) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_high_season')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_kos" name="is_kos" value="1"
                                       {{ old('is_kos') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kos">
                                    Bisa di kos kan?
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="price_kos_field" style="{{ old('is_kos') ? '' : 'display: none;' }}">
                            <label for="price_kos" class="form-label">Harga Kos / Bulan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('price_kos') is-invalid @enderror"
                                       id="price_kos" name="price_kos"
                                       value="{{ old('price_kos') }}"
                                       placeholder="Harga Kos" step="1000" min="0">
                                @error('price_kos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Harga bulanan jika kamar ini dijadikan kos.</small>
                        </div>
                        <div class="mb-3" id="yearly_price_field" style="{{ old('is_kos') ? '' : 'display: none;' }}">
                            <label for="yearly_price" class="form-label">Harga Tahunan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('yearly_price') is-invalid @enderror"
                                       id="yearly_price" name="yearly_price"
                                       value="{{ old('yearly_price') }}"
                                       placeholder="Harga per tahun" step="10000" min="0">
                                @error('yearly_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Harga tahunan (opsional). Kalau diset, booking 12+ bulan otomatis pakai harga ini.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-save-line me-1 align-bottom"></i> Save Room
                            </button>
                            <a href="{{ route('rooms.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-image-line me-2 text-warning"></i>Room Photo</h5>
                    </div>
                    <div class="card-body">
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Max 2MB. jpg, png, webp.</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Room Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            <i class="ri-information-fill me-1"></i>
                            Fill in all required fields to add a new room to the system.
                            The room number must be unique.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script>
        document.getElementById('is_kos').addEventListener('change', function() {
            document.getElementById('price_kos_field').style.display = this.checked ? '' : 'none';
            document.getElementById('price_kos').required = this.checked;
        });
    </script>
@endsection
