@extends('layouts.master')
@section('title')
    Buat Work Order
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('work-orders.index') }}">Work Orders</a>
        @endslot
        @slot('title')
            Buat Work Order
        @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Form Work Order Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('work-orders.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kamar <span class="text-danger">*</span></label>
                            <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Kamar --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->room_number }} ({{ $room->status }})
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assign ke OB <span class="text-danger">*</span></label>
                            <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                                <option value="">-- Pilih OB --</option>
                                @foreach($obs as $ob)
                                    <option value="{{ $ob->id }}" {{ old('assigned_to') == $ob->id ? 'selected' : '' }}>
                                        {{ $ob->name }} ({{ $ob->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="cleaning" {{ old('type') == 'cleaning' ? 'selected' : '' }}>🧹 Cleaning</option>
                                <option value="maintenance" {{ old('type') == 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>📋 Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Deskripsi pekerjaan...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-check-line me-1"></i> Buat Work Order
                            </button>
                            <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
