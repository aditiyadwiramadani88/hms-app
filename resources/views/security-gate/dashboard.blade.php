@extends('layouts.master')
@section('title') Security Gate @endsection
@section('css')
<style>
    .sg-container { max-width: 480px; margin: 0 auto; }
    .sg-btn-big { height: 80px; font-size: 1.25rem; font-weight: 700; border-radius: 16px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
    .sg-stat-card { text-align: center; padding: 1rem 0.5rem; border-radius: 12px; }
    .sg-stat-card .value { font-size: 1.75rem; font-weight: 800; }
    .sg-stat-card .label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .sg-list-item { padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
    .sg-list-item:last-child { border-bottom: none; }
    .sg-vehicle-info { flex: 1; min-width: 0; }
    .sg-vehicle-info .plate { font-weight: 700; font-size: 1rem; }
    .sg-vehicle-info .meta { font-size: 0.8125rem; color: #6c757d; }
    .sg-badge-count { position: absolute; top: -6px; right: -6px; background: #dc3545; color: #fff; font-size: 0.6875rem; padding: 2px 6px; border-radius: 50px; font-weight: 700; }
    .sg-photo-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
    .sg-loading { opacity: 0.5; pointer-events: none; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Security @endslot
    @slot('title') Security Gate @endslot
@endcomponent

<div class="sg-container">
    {{-- Flash Messages (hidden for AJAX, shown for fallback) --}}
    <div id="flashContainer">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="sg-stat-card card border-primary bg-primary-subtle">
                <div class="value text-primary" id="statInsideCount">{{ $stats['inside_count'] }}</div>
                <div class="label">Kendaraan di Dalam</div>
            </div>
        </div>
        <div class="col-6">
            <div class="sg-stat-card card border-warning bg-warning-subtle">
                <div class="value text-warning" id="statNeedPhotoCount">{{ $stats['need_photo_count'] }}</div>
                <div class="label">Perlu Foto Keluar</div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="mb-3">
        <input type="date" class="form-control form-control-sm" id="filterDate" value="{{ $date ?? now()->toDateString() }}" onchange="changeDate(this.value)" max="{{ now()->toDateString() }}">
    </div>

    {{-- Action Buttons --}}
    <div class="row g-2 mb-4">
        <div class="col-6">
            <button type="button" class="btn btn-success sg-btn-big" onclick="openEntryModal()">
                <i class="ri-login-circle-line fs-2"></i> Masuk
            </button>
        </div>
        <div class="col-6">
            <button type="button" class="btn btn-danger sg-btn-big" onclick="openExitModal()">
                <i class="ri-logout-circle-line fs-2"></i> Keluar
            </button>
        </div>
    </div>

    {{-- Perlu Foto Keluar --}}
    <div id="needExitPhotoSection">
        @if($needExitPhoto->count() > 0)
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning-subtle d-flex align-items-center gap-2 py-2">
                <i class="ri-camera-line text-warning"></i>
                <strong>Perlu Foto Keluar ({{ $needExitPhoto->count() }})</strong>
            </div>
            <div class="card-body p-0">
                @foreach($needExitPhoto as $log)
                <div class="sg-list-item">
                    <div class="sg-vehicle-info">
                        <div class="plate">{{ $log->plate_number }}</div>
                        <div class="meta">{{ $log->driver_name }}@if($log->destination_room) &middot; R.{{ $log->destination_room }}@endif</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($log->security_in_id === auth()->id() && $log->created_at->diffInHours(now()) < 24)
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditModal({{ $log->id }}, '{{ addslashes($log->plate_number) }}', '{{ addslashes($log->destination_room) }}', '{{ addslashes($log->purpose) }}', '{{ addslashes($log->notes) }}')">
                                <i class="ri-edit-line"></i>
                            </button>
                        @endif
                        <form action="{{ route('security-gate.exit-photo', $log) }}" method="POST" enctype="multipart/form-data" class="d-inline">
                            @csrf
                            <input type="file" name="photo_out[]" accept="image/*" capture="environment" class="d-none" onchange="this.form.submit()" id="photo_{{$log->id}}" multiple>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="document.getElementById('photo_{{$log->id}}').click()">
                                <i class="ri-camera-line fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Kendaraan di Dalam --}}
    <div class="card" id="vehicleListCard">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <strong><i class="ri-car-parking-line me-1"></i> Kendaraan di Dalam</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshVehicleList()" title="Refresh">
                <i class="ri-refresh-line"></i>
            </button>
        </div>
        <div class="card-body p-0" id="vehicleListContainer">
            @include('security-gate._vehicle-list')
        </div>
    </div>
</div>

{{-- ========== ENTRY MODAL ========== --}}
<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="entryForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="ri-login-circle-line me-2 text-success"></i>Kendaraan Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Plat Nomor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg text-uppercase fw-bold" name="plate_number" id="entry_plate" required autofocus placeholder="Contoh: L 1234 AB">
                    </div>
                    <div class="mb-3" id="entrySearchResult" style="display:none;"></div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Nama Pengemudi</label>
                            <input type="text" class="form-control" name="driver_name" id="entry_driver_name" placeholder="Opsional">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Tipe Kendaraan</label>
                            <select class="form-select" name="vehicle_type">
                                <option value="mobil">Mobil</option>
                                <option value="motor">Motor</option>
                                <option value="truck">Truck</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Warna</label>
                            <input type="text" class="form-control" name="vehicle_color" placeholder="Contoh: Hitam">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Tujuan (Kamar)</label>
                            <input type="text" class="form-control" name="destination_room" id="entry_room" placeholder="Contoh: 101">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keperluan</label>
                        <select class="form-select" name="purpose">
                            <option value="menginap">Menginap</option>
                            <option value="kunjungan">Kunjungan</option>
                            <option value="delivery">Delivery / Paket</option>
                            <option value="karyawan">Karyawan / Staff</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Masuk (opsional)</label>
                        <input type="file" name="photo_in[]" accept="image/*" capture="environment" class="form-control" id="entryPhotoInput" multiple>
                        <div id="entryPhotoPreview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                        <small class="text-muted" id="entryPhotoInfo"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <input type="hidden" name="guest_vehicle_id" id="entry_guest_vehicle_id">
                    <input type="hidden" name="booking_id" id="entry_booking_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="entrySubmitBtn">
                        <i class="ri-login-circle-line me-1"></i> Catat Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== EXIT MODAL ========== --}}
<div class="modal fade" id="exitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="exitForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="ri-logout-circle-line me-2 text-danger"></i>Kendaraan Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cari Kendaraan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="exitSearchInput" placeholder="Ketik plat nomor atau nama pengemudi..." onkeyup="filterExitList(this.value)">
                    </div>
                    <div id="exitVehicleList" style="max-height: 300px; overflow-y: auto;">
                        @foreach($vehiclesInside as $log)
                        <div class="sg-list-item exit-select-item" data-id="{{ $log->id }}" data-plate="{{ strtolower($log->plate_number) }}" data-driver="{{ strtolower($log->driver_name) }}" onclick="selectExitVehicle({{ $log->id }}, '{{ addslashes($log->plate_number) }}')" style="cursor:pointer;">
                            <div class="sg-vehicle-info">
                                <div class="plate">{{ $log->plate_number }}</div>
                                <div class="meta">
                                    {{ $log->driver_name }}
                                    @if($log->destination_room) &middot; R.{{ $log->destination_room }} @endif
                                    &middot; {{ $log->time_in->format('H:i') }}
                                </div>
                            </div>
                            <i class="ri-arrow-right-line text-muted"></i>
                        </div>
                        @endforeach
                        @if($vehiclesInside->isEmpty())
                        <div class="text-center text-muted py-3">Tidak ada kendaraan di dalam</div>
                        @endif
                    </div>
                    <div id="exitSelectedInfo" style="display:none;" class="mt-3">
                        <div class="alert alert-info mb-2">
                            <strong id="exitSelectedPlate"></strong>
                        </div>
                        <input type="hidden" name="vehicle_log_id" id="exit_vehicle_log_id">
                    </div>
                    <div id="exitPhotoSection" style="display:none;" class="mt-3">
                        <label class="form-label">Foto Keluar (opsional)</label>
                        <input type="file" name="photo_out[]" accept="image/*" capture="environment" class="form-control" onchange="previewExitPhotos(event)" multiple>
                        <div id="exitPhotoPreview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="exitSubmitBtn" disabled>
                        <i class="ri-logout-circle-line me-1"></i> Catat Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== EDIT MODAL ========== --}}
<div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editVehicleForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Kendaraan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="plate_number" id="edit_plate_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tujuan (Kamar)</label>
                        <input type="text" class="form-control" name="destination_room" id="edit_destination_room">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keperluan</label>
                        <select class="form-select" name="purpose" id="edit_purpose">
                            <option value="">-- Pilih --</option>
                            <option value="menginap">Menginap</option>
                            <option value="kunjungan">Kunjungan</option>
                            <option value="delivery">Delivery / Paket</option>
                            <option value="karyawan">Karyawan / Staff</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tambahan Foto (opsional)</label>
                        <input type="file" name="photo_in[]" accept="image/*" capture="environment" class="form-control" onchange="previewEditPhoto(event)" multiple>
                        <div id="editPhotoPreviewContainer" class="d-flex gap-2 mt-2 flex-wrap"></div>
                        <div class="form-text">Bisa mengunggah lebih dari 1 foto. Foto baru akan dikompres dan digabungkan dengan foto yang sudah ada.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== PHOTO VIEWER MODAL ========== --}}
<div class="modal fade" id="photoViewerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Kendaraan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="photoModalContainer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const BASE_URL = '{{ url("admin/security-gate") }}';

// ========== FILTER ==========
function filterInsideList(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.inside-item').forEach(el => {
        el.style.display = el.dataset.plate.includes(q) || el.dataset.driver.includes(q) ? '' : 'none';
    });
}

function filterExitList(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.exit-select-item').forEach(el => {
        el.style.display = el.dataset.plate.includes(q) || el.dataset.driver.includes(q) ? '' : 'none';
    });
}

// ========== ENTRY MODAL ==========
function openEntryModal() {
    document.getElementById('entryForm').reset();
    document.getElementById('entrySearchResult').style.display = 'none';
    document.getElementById('entryPhotoPreview').innerHTML = '';
    document.getElementById('entry_guest_vehicle_id').value = '';
    document.getElementById('entry_booking_id').value = '';
    document.getElementById('entrySubmitBtn').disabled = false;
    compressedEntryPhotos = [];
    new bootstrap.Modal(document.getElementById('entryModal')).show();
}

let entrySearchTimeout;
document.getElementById('entry_plate').addEventListener('input', function() {
    clearTimeout(entrySearchTimeout);
    const plate = this.value.trim();
    const resultDiv = document.getElementById('entrySearchResult');
    
    if (plate.length < 3) {
        resultDiv.style.display = 'none';
        return;
    }

    entrySearchTimeout = setTimeout(() => {
        fetch(`${BASE_URL}/search-plate?plate=${encodeURIComponent(plate)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `
                    <div class="alert alert-success py-2 mb-2">
                        <i class="ri-check-line me-1"></i> <strong>${data.guest.name}</strong>
                        ${data.booking ? `<br><small>Kamar: ${data.booking.room_number} (${data.booking.check_in} - ${data.booking.check_out})</small>` : ''}
                    </div>`;
                document.getElementById('entry_guest_vehicle_id').value = data.guest_vehicle_id || '';
                document.getElementById('entry_booking_id').value = data.booking?.id || '';
                document.getElementById('entry_driver_name').value = data.guest.name;
                if (data.booking) {
                    document.getElementById('entry_room').value = data.booking.room_number;
                }
            } else {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `<div class="alert alert-warning py-2 mb-2"><i class="ri-information-line me-1"></i> ${data.message}</div>`;
                document.getElementById('entry_guest_vehicle_id').value = '';
                document.getElementById('entry_booking_id').value = '';
            }
        })
        .catch(() => { resultDiv.style.display = 'none'; });
    }, 400);
});

document.getElementById('entryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('entrySubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin me-1"></i> Menyimpan...';

    const formData = new FormData(this);
    // Replace raw photos with compressed versions
    if (compressedEntryPhotos.length > 0) {
        formData.delete('photo_in[]');
        compressedEntryPhotos.forEach((blob, i) => {
            formData.append('photo_in[]', blob, 'photo_' + i + '.jpg');
        });
    }

    fetch(`${BASE_URL}/entry`, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('entryModal')).hide();
            showToast(data.message, 'success');
            refreshDashboard();
        } else {
            showToast(data.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(err => showToast('Gagal menyimpan data', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-login-circle-line me-1"></i> Catat Masuk';
    });
});

// ========== EXIT MODAL ==========
function openExitModal(vehicleId, plate) {
    document.getElementById('exitForm').reset();
    document.getElementById('exitSearchInput').value = '';
    document.getElementById('exitPhotoPreview').innerHTML = '';
    document.getElementById('exitSelectedInfo').style.display = 'none';
    document.getElementById('exitPhotoSection').style.display = 'none';
    document.getElementById('exitSubmitBtn').disabled = true;
    compressedExitPhotos = [];
    
    // Reset all items visible
    document.querySelectorAll('.exit-select-item').forEach(el => el.style.display = '');

    if (vehicleId && plate) {
        selectExitVehicle(vehicleId, plate);
    }

    new bootstrap.Modal(document.getElementById('exitModal')).show();
}

function selectExitVehicle(id, plate) {
    document.getElementById('exit_vehicle_log_id').value = id;
    document.getElementById('exitSelectedPlate').textContent = plate;
    document.getElementById('exitSelectedInfo').style.display = 'block';
    document.getElementById('exitPhotoSection').style.display = 'block';
    document.getElementById('exitSubmitBtn').disabled = false;

    // Highlight selected
    document.querySelectorAll('.exit-select-item').forEach(el => {
        el.classList.toggle('bg-primary-subtle', el.dataset.id == id);
    });
}

document.getElementById('exitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const logId = document.getElementById('exit_vehicle_log_id').value;
    if (!logId) return;

    const btn = document.getElementById('exitSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin me-1"></i> Menyimpan...';

    const formData = new FormData(this);
    // Replace raw photos with compressed versions
    if (compressedExitPhotos.length > 0) {
        formData.delete('photo_out[]');
        compressedExitPhotos.forEach((blob, i) => {
            formData.append('photo_out[]', blob, 'photo_out_' + i + '.jpg');
        });
    }

    fetch(`${BASE_URL}/exit/${logId}`, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('exitModal')).hide();
            showToast(data.message, 'success');
            refreshDashboard();
        } else {
            showToast(data.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(err => showToast('Gagal menyimpan data', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-logout-circle-line me-1"></i> Catat Keluar';
    });
});

// ========== EDIT MODAL ==========
function openEditModal(id, plate, room, purpose, notes) {
    const form = document.getElementById('editVehicleForm');
    form.action = `${BASE_URL}/vehicle-logs/${id}`;
    document.getElementById('edit_plate_number').value = plate;
    document.getElementById('edit_destination_room').value = room;
    document.getElementById('edit_purpose').value = purpose;
    document.getElementById('edit_notes').value = notes;
    document.getElementById('editPhotoPreviewContainer').innerHTML = '';
    new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
}

document.getElementById('editVehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin me-1"></i> Menyimpan...';

    const form = this;
    const formData = new FormData(form);
    // Laravel: _method=PUT
    formData.set('_method', 'PUT');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editVehicleModal')).hide();
            showToast(data.message, 'success');
            refreshDashboard();
        } else {
            showToast(data.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(err => showToast('Gagal menyimpan data', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Simpan Perubahan';
    });
});

// ========== REFRESH ==========
function changeDate(date) {
    window.location.href = BASE_URL + '?date=' + date;
}

function refreshDashboard() {
    fetch(BASE_URL, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update stats
            const newInside = doc.getElementById('statInsideCount');
            const newPhoto = doc.getElementById('statNeedPhotoCount');
            if (newInside) document.getElementById('statInsideCount').textContent = newInside.textContent;
            if (newPhoto) document.getElementById('statNeedPhotoCount').textContent = newPhoto.textContent;
            
            // Update vehicle list
            const newList = doc.getElementById('vehicleListContainer');
            if (newList) document.getElementById('vehicleListContainer').innerHTML = newList.innerHTML;
            
            // Update need exit photo section
            const newSection = doc.getElementById('needExitPhotoSection');
            if (newSection) document.getElementById('needExitPhotoSection').innerHTML = newSection.innerHTML;
            
            // Update exit modal vehicle list
            const newExitList = doc.getElementById('exitVehicleList');
            if (newExitList) document.getElementById('exitVehicleList').innerHTML = newExitList.innerHTML;
        })
        .catch(() => {});
}

function refreshVehicleList() {
    fetch(BASE_URL, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newList = doc.getElementById('vehicleListContainer');
            if (newList) document.getElementById('vehicleListContainer').innerHTML = newList.innerHTML;
        })
        .catch(() => {});
}

// ========== PHOTO PREVIEW ==========
// Compress image helper
function compressImage(file, maxWidth, quality) {
    return new Promise(resolve => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                let w = img.width, h = img.height;
                if (w > maxWidth) { h = (h * maxWidth) / w; w = maxWidth; }
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(blob => {
                    const compressed = new File([blob], file.name || 'photo.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                    resolve(compressed);
                }, 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

var compressedEntryPhotos = [];
var compressedExitPhotos = [];
document.getElementById('entryPhotoInput').addEventListener('change', async function(e) {
    const container = document.getElementById('entryPhotoPreview');
    const info = document.getElementById('entryPhotoInfo');
    container.innerHTML = '';
    compressedEntryPhotos = [];
    const files = Array.from(e.target.files);
    for (const file of files) {
        const blob = await compressImage(file, 1200, 0.7);
        compressedEntryPhotos.push(blob);
        const img = document.createElement('img');
        img.src = URL.createObjectURL(blob);
        img.className = 'sg-photo-preview rounded';
        container.appendChild(img);
    }
    const totalKB = Math.round(compressedEntryPhotos.reduce((sum, b) => sum + b.size, 0) / 1024);
    info.textContent = files.length + ' foto (' + totalKB + ' KB total)';
});

function previewEntryPhotos(event) {} // kept for backward compat

function previewExitPhotos(event) {
    const container = document.getElementById('exitPhotoPreview');
    container.innerHTML = '';
    compressedExitPhotos = [];
    const files = Array.from(event.target.files);
    files.forEach(async (file) => {
        const blob = await compressImage(file, 1200, 0.7);
        compressedExitPhotos.push(blob);
        const img = document.createElement('img');
        img.src = URL.createObjectURL(blob);
        img.className = 'sg-photo-preview rounded';
        container.appendChild(img);
    });
}

function previewEditPhoto(event) {
    const container = document.getElementById('editPhotoPreviewContainer');
    container.innerHTML = '';
    const input = event.target;
    if (input.files && input.files.length > 0) {
        const dt = new DataTransfer();
        let processedCount = 0;
        
        Array.from(input.files).forEach((file, index) => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'sg-photo-preview rounded';
            container.appendChild(img);

            compressImage(file, 1200, 0.7).then(compressedFile => {
                dt.items.add(compressedFile);
                processedCount++;
                if (processedCount === input.files.length) {
                    input.files = dt.files;
                }
            });
        });
    }
}

// ========== DELETE VEHICLE ==========
function confirmDeleteVehicle(id, plate) {
    if (!confirm('Yakin ingin menghapus data kendaraan ' + plate + '?')) return;
    
    fetch(`${BASE_URL}/vehicle-logs/${id}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            refreshDashboard();
        } else {
            showToast(data.message || 'Gagal menghapus', 'error');
        }
    })
    .catch(() => showToast('Gagal menghapus data', 'error'));
}

// ========== PHOTO VIEWER ==========
function openPhotoModal(imgElement) {
    const photos = JSON.parse(imgElement.dataset.photos);
    const container = document.getElementById('photoModalContainer');
    container.innerHTML = '';
    
    photos.forEach(photo => {
        const img = document.createElement('img');
        img.src = photo;
        img.className = 'img-fluid mb-3 rounded shadow-sm';
        img.style.width = '100%';
        container.appendChild(img);
    });
    
    new bootstrap.Modal(document.getElementById('photoViewerModal')).show();
}

// ========== TOAST ==========
function showToast(message, type = 'success') {
    const container = document.getElementById('flashContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'ri-check-line' : 'ri-error-warning-line';
    
    const div = document.createElement('div');
    div.className = `alert ${alertClass} alert-dismissible fade show`;
    div.innerHTML = `<i class="${icon} me-2 align-middle"></i> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    container.innerHTML = '';
    container.appendChild(div);
    
    setTimeout(() => div.remove(), 5000);
}
</script>
<style>
    .spin { animation: spin 1s linear infinite; display: inline-block; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endsection
