@extends('layouts.master')
@section('title') Edit Record Maintenance @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('maintenance.records.index') }}">Maintenance</a> @endslot
        @slot('title') Edit Record @endslot
    @endcomponent

    <form action="{{ route('maintenance.records.update', $record->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-tools-fill me-2 text-warning"></i>Detail Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $record->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="room_id" class="form-label">Kamar</label>
                                <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id">
                                    <option value="">Umum (tidak spesifik kamar)</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id', $record->room_id) == $room->id ? 'selected' : '' }}>{{ $room->room_number }}</option>
                                    @endforeach
                                </select>
                                @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('maintenance_date') is-invalid @enderror" id="maintenance_date" name="maintenance_date" value="{{ old('maintenance_date', $record->maintenance_date->format('Y-m-d')) }}" required>
                                @error('maintenance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="maintenance_status" name="status" required>
                                    <option value="scheduled" {{ old('status', $record->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="in_progress" {{ old('status', $record->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status', $record->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Deskripsi Masalah</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Jelaskan masalah atau kondisi...">{{ old('description', $record->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Actions Checklist --}}
                            <div class="col-12">
                                <label class="form-label">Tindakan yang Dilakukan</label>
                                <div class="row g-2">
                                    @php $oldActions = old('actions', $record->actions ?? []); @endphp
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Service" id="act_service" {{ in_array('Service', $oldActions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_service">Service</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Ganti Modul" id="act_replace_module" {{ in_array('Ganti Modul', $oldActions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_replace_module">Ganti Modul</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Tambah Freon" id="act_add_freon" {{ in_array('Tambah Freon', $oldActions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_add_freon">Tambah Freon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Penggantian Alat" id="act_replace_equipment" {{ in_array('Penggantian Alat', $oldActions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_replace_equipment">Penggantian Alat</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="technician_name" class="form-label">Nama Teknisi</label>
                                <input type="text" class="form-control @error('technician_name') is-invalid @enderror" id="technician_name" name="technician_name" value="{{ old('technician_name', $record->technician_name) }}" placeholder="Nama teknisi">
                                @error('technician_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Biaya (Rp)</label>
                                <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost', $record->cost) }}" min="0">
                                @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Catatan tambahan...">{{ old('notes', $record->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
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
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1 align-bottom"></i> Update Record
                            </button>
                            <a href="{{ route('maintenance.records.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Info</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="text-muted">Dibuat oleh:</span><br>
                                <span class="fw-medium">{{ $record->creator?->name ?? 'N/A' }}</span>
                            </li>
                            <li>
                                <span class="text-muted">Dibuat:</span><br>
                                <span class="fw-medium">{{ $record->created_at->format('d M Y, H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
