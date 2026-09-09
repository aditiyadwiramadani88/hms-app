@extends('layouts.master')
@section('title') Kendaraan Keluar @endsection
@section('css')
<style>
    .sg-exit-container { max-width: 480px; margin: 0 auto; }
    .sg-exit-item { padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; cursor: pointer; transition: background 0.15s; }
    .sg-exit-item:hover { background: #f8f9fa; }
    .sg-exit-item:last-child { border-bottom: none; }
    .sg-exit-info { flex: 1; min-width: 0; }
    .sg-exit-info .plate { font-weight: 700; font-size: 1rem; }
    .sg-exit-info .meta { font-size: 0.8125rem; color: #6c757d; }
    .sg-photo-preview { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-top: 0.5rem; display: none; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Security Gate @endslot
    @slot('title') Kendaraan Keluar @endslot
@endcomponent

<div class="sg-exit-container">
    {{-- Flash Messages --}}
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

    <div class="card mb-3">
        <div class="card-header py-2">
            <strong><i class="ri-car-parking-line me-1"></i> Pilih Kendaraan Keluar</strong>
        </div>
        <div class="card-body p-0">
            <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm" id="searchExit" placeholder="Cari plat nomor..." onkeyup="filterExitList(this.value)">
            </div>

            <div id="exitList">
                @forelse($vehicles as $log)
                <div class="sg-exit-item exit-item" data-plate="{{ strtolower($log->plate_number) }}" data-driver="{{ strtolower($log->driver_name) }}">
                    <div class="sg-exit-info">
                        <div class="plate">{{ $log->plate_number }}</div>
                        <div class="meta">
                            {{ $log->driver_name }}
                            @if($log->destination_room) &middot; R.{{ $log->destination_room }} @endif
                            &middot; {{ $log->time_in->format('H:i') }}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#exitModal" data-id="{{ $log->id }}" data-plate="{{ $log->plate_number }}" data-driver="{{ $log->driver_name }}" data-room="{{ $log->destination_room }}" data-type="{{ $log->vehicle_type }}" data-purpose="{{ $log->purpose }}">
                        <i class="ri-logout-circle-line"></i> Keluar
                    </button>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="ri-car-line fs-1 d-block mb-2"></i>
                    Tidak ada kendaraan di dalam
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mb-4">
        <a href="{{ route('security-gate.dashboard') }}" class="btn btn-light w-100">Kembali ke Dashboard</a>
    </div>
</div>

{{-- Exit Confirmation Modal --}}
<div class="modal fade" id="exitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data" id="exitForm">
                @csrf
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title"><i class="ri-logout-circle-line me-1"></i> Konfirmasi Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-semibold">Plat Nomor</label>
                        <p class="fs-5 fw-bold mb-0" id="modalPlate"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold">Pengemudi</label>
                        <p class="mb-0" id="modalDriver"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold">Kamar Tujuan</label>
                        <p class="mb-0" id="modalRoom">-</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Keluar (opsional)</label>
                        <input type="file" name="photo_out[]" accept="image/*" capture="environment" class="form-control" onchange="previewExitPhoto(event)" multiple>
                        <div id="exitPhotoPreviewContainer" class="d-flex gap-2 mt-2 flex-wrap"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-logout-circle-line me-1"></i> Konfirmasi Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function filterExitList(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.exit-item').forEach(el => {
        el.style.display = el.dataset.plate.includes(q) || el.dataset.driver.includes(q) ? '' : 'none';
    });
}

document.getElementById('exitModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const logId = btn.dataset.id;
    document.getElementById('exitForm').action = '{{ route("security-gate.exit.process", ":id") }}'.replace(':id', logId);
    document.getElementById('modalPlate').textContent = btn.dataset.plate;
    document.getElementById('modalDriver').textContent = btn.dataset.driver;
    document.getElementById('modalRoom').textContent = btn.dataset.room || '-';
    document.getElementById('exitPhotoPreviewContainer').innerHTML = '';
});

function previewExitPhoto(event) {
    const container = document.getElementById('exitPhotoPreviewContainer');
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
