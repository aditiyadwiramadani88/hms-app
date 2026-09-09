@extends('layouts.master')
@section('title') Kendaraan Masuk @endsection
@section('css')
<style>
    .sg-entry-container { max-width: 480px; margin: 0 auto; }
    .sg-radio-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .sg-radio-group .btn-check:checked + .btn { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .sg-radio-group .btn { flex: 1; min-width: 80px; border-radius: 10px; padding: 0.625rem 0.5rem; font-size: 0.875rem; }
    .sg-auto-match { border-left: 4px solid #198754; background: #f0faf0; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; display: none; }
    .sg-photo-preview { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-top: 0.5rem; display: none; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Security Gate @endslot
    @slot('title') Kendaraan Masuk @endslot
@endcomponent

<div class="sg-entry-container">
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Auto-Match Result Card --}}
    <div class="sg-auto-match" id="autoMatchCard">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ri-checkbox-circle-fill text-success"></i>
            <strong>Kendaraan Terdaftar</strong>
        </div>
        <p class="mb-1" id="matchGuestName"></p>
        <p class="mb-0 small text-muted" id="matchBookingInfo"></p>
        <input type="hidden" name="guest_vehicle_id" id="matchedGuestVehicleId" value="">
        <input type="hidden" name="booking_id" id="matchedBookingId" value="">
    </div>

    <form method="POST" action="{{ route('security-gate.entry.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="ri-car-line me-1"></i> Data Kendaraan</h6>

                <div class="mb-3">
                    <label class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                    <input type="text" name="plate_number" id="plate_number" class="form-control form-control-lg @error('plate_number') is-invalid @enderror" value="{{ old('plate_number') }}" placeholder="Contoh: B 1234 ABC" maxlength="20" required autocomplete="off">
                    @error('plate_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div id="plateSearchStatus" class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Pengemudi <span class="text-danger">*</span></label>
                    <input type="text" name="driver_name" class="form-control form-control-lg @error('driver_name') is-invalid @enderror" value="{{ old('driver_name') }}" placeholder="Nama pengemudi" required>
                    @error('driver_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipe Kendaraan <span class="text-danger">*</span></label>
                    <div class="sg-radio-group">
                        @foreach(['motor' => 'Motor', 'mobil' => 'Mobil', 'truck' => 'Truck'] as $val => $label)
                        <input type="radio" class="btn-check" name="vehicle_type" id="vt_{{ $val }}" value="{{ $val }}" {{ old('vehicle_type') === $val ? 'checked' : '' }} required>
                        <label class="btn btn-outline-primary" for="vt_{{ $val }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    @error('vehicle_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Warna Kendaraan</label>
                    <input type="text" name="vehicle_color" class="form-control @error('vehicle_color') is-invalid @enderror" value="{{ old('vehicle_color') }}" placeholder="Contoh: Hitam">
                    @error('vehicle_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="ri-information-line me-1"></i> Detail Kunjungan</h6>

                <div class="mb-3">
                    <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                    <div class="sg-radio-group">
                        @foreach(['menginap' => 'Menginap', 'kunjungan' => 'Kunjungan', 'delivery' => 'Delivery', 'karyawan' => 'Karyawan', 'lainnya' => 'Lainnya'] as $val => $label)
                        <input type="radio" class="btn-check" name="purpose" id="p_{{ $val }}" value="{{ $val }}" {{ old('purpose') === $val ? 'checked' : '' }} required>
                        <label class="btn btn-outline-primary" for="p_{{ $val }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    @error('purpose') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kamar Tujuan</label>
                    <input type="text" name="destination_room" class="form-control @error('destination_room') is-invalid @enderror" value="{{ old('destination_room') }}" placeholder="Contoh: 113">
                    @error('destination_room') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="ri-camera-line me-1"></i> Foto Kendaraan</h6>

                <div class="mb-2">
                    <input type="file" name="photo_in[]" id="photo_in" accept="image/*" capture="environment" class="form-control @error('photo_in') is-invalid @enderror" onchange="previewPhoto(event)" multiple>
                    @error('photo_in') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div id="photoPreviewContainer" class="d-flex gap-2 flex-wrap"></div>
                <div class="form-text mt-1">Opsional. Anda bisa memilih lebih dari satu foto. Foto akan otomatis di-compress.</div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-4">
            <button type="submit" class="btn btn-success btn-lg py-3 fw-bold">
                <i class="ri-login-circle-line me-2"></i> Catat Masuk
            </button>
            <a href="{{ route('security-gate.dashboard') }}" class="btn btn-light">Kembali</a>
        </div>
    </form>
</div>
@endsection
@section('script')
<script>
let searchTimeout;

document.getElementById('plate_number').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const plate = this.value.trim();
    const status = document.getElementById('plateSearchStatus');

    if (plate.length < 3) {
        document.getElementById('autoMatchCard').style.display = 'none';
        document.getElementById('matchedGuestVehicleId').value = '';
        document.getElementById('matchedBookingId').value = '';
        status.textContent = '';
        return;
    }

    status.textContent = 'Mencari...';
    status.className = 'form-text text-muted';

    searchTimeout = setTimeout(() => {
        fetch('{{ route("security-gate.search-plate") }}?plate=' + encodeURIComponent(plate))
            .then(r => r.json())
            .then(d => {
                const card = document.getElementById('autoMatchCard');
                if (d.found) {
                    card.style.display = 'block';
                    document.getElementById('matchGuestName').textContent = '👤 ' + d.guest.name;
                    document.getElementById('matchedGuestVehicleId').value = d.guest_vehicle_id;
                    if (d.booking) {
                        document.getElementById('matchBookingInfo').innerHTML =
                            '🛏️ Kamar ' + d.booking.room_number +
                            ' (' + d.booking.check_in + ' - ' + d.booking.check_out + ')' +
                            ' <span class="badge bg-success">Booking Aktif</span>';
                        document.getElementById('matchedBookingId').value = d.booking.id;
                    } else {
                        document.getElementById('matchBookingInfo').textContent = '⚠️ Tidak ada booking aktif untuk tamu ini.';
                        document.getElementById('matchedBookingId').value = '';
                    }
                    status.textContent = '';
                } else {
                    card.style.display = 'none';
                    document.getElementById('matchedGuestVehicleId').value = '';
                    document.getElementById('matchedBookingId').value = '';
                    status.textContent = d.message;
                    status.className = 'form-text text-warning';
                }
            })
            .catch(() => {
                status.textContent = 'Gagal mencari data.';
                status.className = 'form-text text-danger';
            });
    }, 500);
});

function previewPhoto(event) {
    const container = document.getElementById('photoPreviewContainer');
    container.innerHTML = '';
    const input = event.target;
    if (input.files && input.files.length > 0) {
        const dt = new DataTransfer();
        let processedCount = 0;
        
        Array.from(input.files).forEach((file, index) => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'sg-photo-preview';
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.display = 'block';
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

function compressImage(file, maxWidth, quality) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Resize if larger than maxWidth
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    const compressedFile = new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    resolve(compressedFile);
                }, 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}
</script>
@endsection
