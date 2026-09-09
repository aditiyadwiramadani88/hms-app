@extends('layouts.master')
@section('title') Tambah Record Maintenance @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('maintenance.records.index') }}">Maintenance</a> @endslot
        @slot('title') Tambah Record @endslot
    @endcomponent

    <form action="{{ route('maintenance.records.store') }}" method="POST">
        @csrf
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
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                    <option value="new" {{ old('category_id') == 'new' ? 'selected' : '' }}>+ Tambah Baru</option>
                                </select>
                                <input type="text" class="form-control form-control-sm mt-2 {{ old('category_id') == 'new' ? '' : 'd-none' }}" id="new_category_name" name="new_category_name" value="{{ old('new_category_name') }}" placeholder="Nama kategori baru" style="max-width: 250px;">
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="room_id" class="form-label">Kamar</label>
                                <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id">
                                    <option value="">Umum (tidak spesifik kamar)</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>{{ $room->room_number }}</option>
                                    @endforeach
                                </select>
                                @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('maintenance_date') is-invalid @enderror" id="maintenance_date" name="maintenance_date" value="{{ old('maintenance_date', date('Y-m-d')) }}" required>
                                @error('maintenance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="maintenance_status" name="status" required>
                                    <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="in_progress" {{ old('status', 'completed') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Deskripsi Masalah</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Jelaskan masalah atau kondisi...">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Actions Checklist --}}
                            <div class="col-12">
                                <label class="form-label">Tindakan yang Dilakukan</label>
                                <div class="row g-2" id="actions_checklist">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Service" id="act_service" {{ in_array('Service', old('actions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_service">Service</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Ganti Modul" id="act_replace_module" {{ in_array('Ganti Modul', old('actions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_replace_module">Ganti Modul</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Tambah Freon" id="act_add_freon" {{ in_array('Tambah Freon', old('actions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_add_freon">Tambah Freon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="actions[]" value="Penggantian Alat" id="act_replace_equipment" {{ in_array('Penggantian Alat', old('actions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="act_replace_equipment">Penggantian Alat</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="technician_name" class="form-label">Nama Teknisi</label>
                                <input type="text" class="form-control @error('technician_name') is-invalid @enderror" id="technician_name" name="technician_name" value="{{ old('technician_name') }}" placeholder="Nama teknisi">
                                @error('technician_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Biaya (Rp)</label>
                                <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost', 0) }}" min="0">
                                @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
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
                                <i class="ri-save-line me-1 align-bottom"></i> Simpan Record
                            </button>
                            <a href="{{ route('maintenance.records.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var catSelect = document.getElementById('category_id');
    var newCatInput = document.getElementById('new_category_name');
    catSelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newCatInput.classList.remove('d-none');
        } else {
            newCatInput.classList.add('d-none');
        }
    });
});
</script>
@endsection
