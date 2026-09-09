@extends('layouts.master')
@section('title')
    Task Cleaning - Kamar {{ $task->room->room_number ?? 'Detail' }}
@endsection
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
    .checklist-card { transition: all 0.3s ease; }
    .checklist-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .item-timeline { position: relative; padding-left: 20px; margin-top: 12px; }
    .item-timeline::before { content: ''; position: absolute; left: 7px; top: 4px; bottom: 4px; width: 2px; background: #e9ecef; border-radius: 1px; }
    .timeline-entry { position: relative; padding-bottom: 10px; }
    .timeline-entry:last-child { padding-bottom: 0; }
    .timeline-entry::before { content: ''; position: absolute; left: -16px; top: 5px; width: 8px; height: 8px; border-radius: 50%; }
    .timeline-entry.te-rejected::before { background: #dc3545; }
    .timeline-entry.te-approved::before { background: #198754; }
    .timeline-entry.te-photo::before { background: #0dcaf0; }
    .timeline-entry.te-submitted::before { background: #6c757d; }
    .timeline-photo { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; cursor: pointer; transition: transform 0.15s; }
    .timeline-photo:hover { transform: scale(1.08); opacity: 0.9; }
    .photo-thumb { cursor: pointer; transition: transform 0.15s; }
    .photo-thumb:hover { transform: scale(1.05); opacity: 0.9; }
    @media (max-width: 768px) {
        .timeline-photo { width: 50px; height: 50px; }
    }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('housekeeping.my-tasks') }}">My Tasks</a>
        @endslot
        @slot('title')
            Task Kamar {{ $task->room->room_number ?? '' }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center flex-wrap gap-2">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">
                            <i class="ri-home-line me-2"></i>Kamar {{ $task->room->room_number ?? '-' }}
                        </h5>
                        <span class="badge bg-light text-dark me-1"><i class="ri-building-2-line me-1"></i>Lantai {{ $task->room->floor ?? '-' }}</span>
                        <span class="badge bg-light text-dark"><i class="ri-hotel-line me-1"></i>{{ $task->room->roomType->name ?? '-' }}</span>
                    </div>
                    <div>
                        @if($task->status === 'belum_mulai')
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="ri-time-line me-1"></i> Belum Mulai</span>
                        @elseif($task->status === 'sedang_dikerjakan')
                            <span class="badge bg-info fs-6 px-3 py-2"><i class="ri-play-circle-line me-1"></i> Sedang Dikerjakan</span>
                        @elseif($task->status === 'menunggu_verifikasi')
                            <span class="badge bg-primary fs-6 px-3 py-2"><i class="ri-search-eye-line me-1"></i> Menunggu Verifikasi</span>
                        @elseif($task->status === 'revisi')
                            <span class="badge bg-danger fs-6 px-3 py-2"><i class="ri-error-warning-line me-1"></i> Perlu Revisi</span>
                        @else
                            <span class="badge bg-success fs-6 px-3 py-2"><i class="ri-checkbox-circle-line me-1"></i> Selesai</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Progress -->
                    @php $displayProgress = $task->status === 'selesai' ? 100 : $progress; @endphp
                    <div class="card mb-4 border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Progress Cleaning</h6>
                                <span class="fw-bold fs-5">{{ $displayProgress }}%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $displayProgress }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Before/After Photos -->
                    @php
                        $beforePhotos = $task->photos->where('type', 'before');
                        $afterPhotos = $task->photos->where('type', 'after');
                        $canEdit = in_array($task->status, ['sedang_dikerjakan', 'revisi']);
                    @endphp
                    <div class="card mb-4 border border-dashed">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="ri-image-2-line me-2"></i>Foto Before & After <span class="text-muted fw-normal fs-12">(Optional)</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Before -->
                                <div class="col-6">
                                    <label class="form-label fw-semibold fs-12 text-uppercase text-muted">Before</label>
                                    @if($beforePhotos->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            @foreach($beforePhotos as $photo)
                                            <div class="position-relative">
                                                <img loading="lazy" src="{{ $photo->url }}" class="rounded border photo-thumb" style="width:80px;height:80px;object-fit:cover;" onclick="showImageModal('{{ $photo->url }}', 'Before')">
                                                @if($canEdit)
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0" style="width:18px;height:18px;font-size:10px;line-height:1;" onclick="deleteTaskPhoto({{ $photo->id }})">×</button>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($canEdit)
                                    <input type="file" class="form-control form-control-sm" accept="image/jpeg,image/png" capture="environment" onchange="uploadBeforeAfter(this, 'before')">
                                    @endif
                                </div>
                                <!-- After -->
                                <div class="col-6">
                                    <label class="form-label fw-semibold fs-12 text-uppercase text-muted">After</label>
                                    @if($afterPhotos->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            @foreach($afterPhotos as $photo)
                                            <div class="position-relative">
                                                <img loading="lazy" src="{{ $photo->url }}" class="rounded border photo-thumb" style="width:80px;height:80px;object-fit:cover;" onclick="showImageModal('{{ $photo->url }}', 'After')">
                                                @if($canEdit)
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0" style="width:18px;height:18px;font-size:10px;line-height:1;" onclick="deleteTaskPhoto({{ $photo->id }})">×</button>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($canEdit)
                                    <input type="file" class="form-control form-control-sm" accept="image/jpeg,image/png" capture="environment" onchange="uploadBeforeAfter(this, 'after')">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Checklist Items with Timeline -->
                    <h6 class="mb-3"><i class="ri-checkbox-multiple-line me-2"></i>Checklist Cleaning</h6>

                    @foreach($task->checklistItems as $item)
                    @php
                        $itemLogs = $logsByItem->get($item->id, collect());
                        $isRejected = $item->verification_status === 'rejected';
                        $canEdit = in_array($task->status, ['sedang_dikerjakan', 'revisi']);
                        $needsRedo = $isRejected && !$item->is_done;
                        // If task is selesai, always show as done
                        $showAsDone = $task->status === 'selesai' || ($item->is_done && !$isRejected);
                    @endphp
                    <div class="checklist-card border rounded mb-3 {{ $showAsDone ? 'border-success bg-success-subtle' : ($needsRedo ? 'border-danger bg-danger-subtle' : 'bg-light') }}">
                        <div class="p-3">
                            <!-- Item Header -->
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    @if($showAsDone)
                                        <i class="ri-checkbox-circle-fill text-success fs-4 me-2"></i>
                                    @elseif($needsRedo)
                                        <i class="ri-error-warning-fill text-danger fs-4 me-2"></i>
                                    @else
                                        <i class="ri-checkbox-blank-circle-line text-muted fs-4 me-2"></i>
                                    @endif
                                    <div>
                                        <strong class="{{ $showAsDone ? 'text-success' : ($needsRedo ? 'text-danger' : '') }}">{{ $item->name }}</strong>
                                        @if($item->comment && ($item->is_done || $task->status === 'selesai'))
                                            <div class="text-muted fs-12 mt-1"><i class="ri-chat-3-line me-1"></i>{{ $item->comment }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($item->photo_url && ($item->is_done || $task->status === 'selesai'))
                                        <img loading="lazy" src="{{ $item->photo_url }}" class="timeline-photo photo-thumb" onclick="showImageModal('{{ $item->photo_url }}', '{{ $item->name }}')">
                                    @endif
                                    @if($canEdit && (!$item->is_done || $needsRedo))
                                        <button class="btn btn-sm btn-outline-success" onclick="quickCheck({{ $item->id }})" title="Centang selesai">
                                            <i class="ri-check-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="openEvidenceModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->photo_url }}', '{{ addslashes($item->comment ?? '') }}')">
                                            <i class="ri-camera-line me-1"></i> Foto
                                        </button>
                                    @elseif($canEdit && $item->is_done)
                                        <button class="btn btn-sm btn-outline-success" onclick="openEvidenceModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->photo_url }}', '{{ addslashes($item->comment ?? '') }}')">
                                            <i class="ri-edit-line me-1"></i> Edit
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Checker Rejection Alert -->
                            @if($item->checker_note && !$item->is_done)
                            <div class="alert alert-danger py-2 px-3 mt-2 mb-0 fs-13 d-flex align-items-start">
                                <i class="ri-error-warning-line me-2 mt-1"></i>
                                <div>
                                    <strong>Checker:</strong> {{ $item->checker_note }}
                                    <div class="text-muted fs-11 mt-1">Silakan foto ulang untuk item ini</div>
                                </div>
                            </div>
                            @endif

                            <!-- Per-item Timeline History -->
                            @if($itemLogs->isNotEmpty() || ($item->photo_url && $task->status === 'selesai' && !$showAsDone))
                            <div class="item-timeline">
                                @foreach($itemLogs->take(5) as $log)
                                @php
                                    $teClass = 'te-submitted';
                                    if (str_contains($log->action, 'approved')) $teClass = 'te-approved';
                                    elseif (str_contains($log->action, 'rejected')) $teClass = 'te-rejected';
                                    elseif (str_contains($log->action, 'photo') || str_contains($log->action, 'evidence')) $teClass = 'te-photo';
                                @endphp
                                <div class="timeline-entry {{ $teClass }}">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <div class="fs-12">
                                                <span class="badge {{ $log->role === 'checker' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }} fs-10">{{ ucfirst($log->role) }}</span>
                                                <small class="text-muted ms-1">{{ $log->created_at->diffForHumans() }}</small>
                                            </div>
                                            @if($log->note)
                                            <p class="mb-0 fs-12 mt-1">{{ Str::limit($log->note, 80) }}</p>
                                            @endif
                                        </div>
                                        @if($log->photo_url)
                                        <img loading="lazy" src="{{ $log->photo_url }}" class="photo-thumb" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;" onclick="showImageModal('{{ $log->photo_url }}', '{{ addslashes($log->note ?? 'Foto') }}')">
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                                @if($itemLogs->count() > 5)
                                <div class="text-muted fs-11 mt-1">+ {{ $itemLogs->count() - 5 }} riwayat lainnya</div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    @if(in_array($task->status, ['sedang_dikerjakan', 'revisi']))
                    <!-- Notes -->
                    <div class="card mb-4">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="ri-sticky-note-line me-2"></i>Catatan Tambahan</h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" id="taskNotes" rows="2" placeholder="Catatan opsional...">{{ $task->notes }}</textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    @php $allDone = $task->checklistItems->every(fn($i) => $i->is_done); @endphp
                    <div class="card border-success">
                        <div class="card-body text-center py-4">
                            <button type="button" class="btn btn-success btn-lg px-5 py-3 {{ !$allDone ? 'disabled' : '' }}" onclick="completeTask()" {{ !$allDone ? 'disabled' : '' }}>
                                <i class="ri-check-double-line me-2"></i>
                                <span class="fw-semibold">Tandai Sebagai Selesai</span>
                            </button>
                            @if(!$allDone)
                            <p class="text-danger mt-2 mb-0 fs-13">
                                <i class="ri-error-warning-line me-1"></i>
                                Selesaikan semua checklist terlebih dahulu ({{ $task->checklistItems->where('is_done', true)->count() }}/{{ $task->checklistItems->count() }})
                            </p>
                            @else
                            <p class="text-muted mt-2 mb-0 fs-13">Semua checklist sudah selesai, siap dikirim untuk verifikasi</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($task->status === 'selesai')
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="ri-checkbox-circle-line fs-3 me-3"></i>
                        <div>
                            <strong>Task Selesai!</strong>
                            <div>Diselesaikan pada {{ $task->completed_at?->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    @endif

                    @if($task->status === 'menunggu_verifikasi')
                    <div class="alert alert-primary d-flex align-items-center">
                        <i class="ri-time-line fs-3 me-3"></i>
                        <div>
                            <strong>Menunggu Verifikasi</strong>
                            <div>Task sedang direview oleh Checker/Supervisor.</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0"><i class="ri-information-line me-2"></i>Info Task</h6></div>
                <div class="card-body">
                    <div class="mb-2"><small class="text-muted">Dibuat Oleh</small><div class="fw-medium">{{ $task->assignedByUser->name ?? 'Admin' }}</div></div>
                    <div class="mb-2"><small class="text-muted">Mulai</small><div>{{ $task->started_at?->format('d/m/Y H:i') ?? '-' }}</div></div>
                    <div class="mb-2"><small class="text-muted">Selesai</small><div>{{ $task->completed_at?->format('d/m/Y H:i') ?? '-' }}</div></div>
                    <div><small class="text-muted">Checklist</small><div>{{ $task->checklistItems->where('is_done', true)->count() }} / {{ $task->checklistItems->count() }} selesai</div></div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('housekeeping.my-tasks') }}" class="btn btn-outline-primary w-100">
                        <i class="ri-arrow-left-line me-1"></i> Kembali ke My Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title text-white" id="imagePreviewTitle"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img loading="lazy" id="imagePreviewImg" src="" class="img-fluid rounded shadow" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- Evidence Modal -->
    <div class="modal fade" id="evidenceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="ri-camera-line me-2"></i><span id="evidenceModalTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="evidenceItemId">
                    <div id="evidenceCurrentPhoto" class="text-center mb-3" style="display:none;">
                        <img loading="lazy" id="evidenceCurrentImg" src="" class="img-fluid rounded border" style="max-height:180px;">
                        <small class="text-muted d-block mt-1">Foto saat ini</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">📷 Ambil Foto</label>
                        <input type="file" class="form-control" id="evidencePhotoInput" accept="image/jpeg,image/png" capture="environment">
                    </div>
                    <div id="evidencePhotoPreview" class="text-center mb-3" style="display:none;">
                        <img loading="lazy" id="evidencePreviewImg" src="" class="img-fluid rounded border" style="max-height:180px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">💬 Keterangan</label>
                        <textarea class="form-control" id="evidenceComment" rows="2" placeholder="Contoh: Sudah bersih..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="evidenceSaveBtn" onclick="saveEvidence()">
                        <i class="ri-check-line me-1"></i> Simpan & Centang
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    let evidenceModal, imagePreviewModal;
    document.addEventListener('DOMContentLoaded', function() {
        evidenceModal = new bootstrap.Modal(document.getElementById('evidenceModal'));
        imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        document.getElementById('evidencePhotoInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                try {
                    const compressed = await compressImage(file, 1200, 0.7);
                    document.getElementById('evidencePreviewImg').src = URL.createObjectURL(compressed);
                    document.getElementById('evidencePhotoPreview').style.display = 'block';
                } catch(err) {
                    const reader = new FileReader();
                    reader.onload = ev => { document.getElementById('evidencePreviewImg').src = ev.target.result; document.getElementById('evidencePhotoPreview').style.display = 'block'; };
                    reader.readAsDataURL(file);
                }
            } else { document.getElementById('evidencePhotoPreview').style.display = 'none'; }
        });
    });

    function compressImage(file, maxWidth = 1200, quality = 0.7) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round((h * maxWidth) / w); w = maxWidth; }
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(blob => blob ? resolve(new File([blob], file.name, { type: 'image/jpeg' })) : reject(), 'image/jpeg', quality);
                };
                img.onerror = reject;
                img.src = e.target.result;
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function openEvidenceModal(itemId, itemName, currentPhotoUrl, currentComment) {
        document.getElementById('evidenceItemId').value = itemId;
        document.getElementById('evidenceModalTitle').textContent = itemName;
        document.getElementById('evidenceComment').value = currentComment || '';
        document.getElementById('evidencePhotoInput').value = '';
        document.getElementById('evidencePhotoPreview').style.display = 'none';
        if (currentPhotoUrl && currentPhotoUrl !== '') {
            document.getElementById('evidenceCurrentImg').src = currentPhotoUrl;
            document.getElementById('evidenceCurrentPhoto').style.display = 'block';
        } else { document.getElementById('evidenceCurrentPhoto').style.display = 'none'; }
        evidenceModal.show();
    }

    async function saveEvidence() {
        const itemId = document.getElementById('evidenceItemId').value;
        const photoInput = document.getElementById('evidencePhotoInput');
        const comment = document.getElementById('evidenceComment').value;
        const btn = document.getElementById('evidenceSaveBtn');

        btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Menyimpan...';

        // If no photo selected, just mark as done with comment (photo optional)
        if (!photoInput.files[0]) {
            try {
                const url = `{{ route('housekeeping.my-tasks.checklist.update', ':item') }}`.replace(':item', itemId);
                await fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }, body: JSON.stringify({ comment, mark_done: true }) });
                showToast('Tersimpan!', 'success'); evidenceModal.hide(); setTimeout(() => location.reload(), 600);
            } catch(e) { showToast('Gagal', 'error'); }
            finally { btn.disabled = false; btn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan & Centang'; }
            return;
        }

        btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Mengkompress...';
        let fileToUpload;
        try { fileToUpload = await compressImage(photoInput.files[0], 1200, 0.7); } catch(e) { fileToUpload = photoInput.files[0]; }
        if (fileToUpload.size > 2 * 1024 * 1024) { try { fileToUpload = await compressImage(photoInput.files[0], 800, 0.5); } catch(e) { showToast('Foto terlalu besar', 'error'); btn.disabled = false; btn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan & Centang'; return; } }

        btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Mengupload...';
        const formData = new FormData();
        formData.append('photo', fileToUpload);
        if (comment) formData.append('comment', comment);

        try {
            const url = `{{ route('housekeeping.my-tasks.checklist.evidence', ':item') }}`.replace(':item', itemId);
            const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: formData });
            if (response.status === 413) { showToast('Foto masih terlalu besar', 'error'); return; }
            const data = await response.json();
            if (data.success) { showToast('✅ Tersimpan!', 'success'); evidenceModal.hide(); setTimeout(() => location.reload(), 600); }
            else { showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { showToast('Gagal upload', 'error'); }
        finally { btn.disabled = false; btn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan & Centang'; }
    }

    async function quickCheck(itemId) {
        try {
            const url = `{{ route('housekeeping.my-tasks.checklist.update', ':item') }}`.replace(':item', itemId);
            const response = await fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }, body: JSON.stringify({ mark_done: true }) });
            const data = await response.json();
            if (data.success) { showToast('✅ Dicentang!', 'success'); setTimeout(() => location.reload(), 500); }
            else { showToast('Gagal', 'error'); }
        } catch(e) { showToast('Gagal', 'error'); }
    }

    async function completeTask(force = false) {
        const notes = document.getElementById('taskNotes')?.value || '';
        const body = { notes }; if (force) body.force = true;
        const total = {{ $task->checklistItems->count() }};
        const done = {{ $task->checklistItems->where('is_done', true)->count() }};
        if (done < total && !force) { if (!confirm(`Baru ${done}/${total} selesai. Yakin kirim?`)) return; }

        try {
            const response = await fetch(`{{ route('housekeeping.my-tasks.complete', $task) }}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            const data = await response.json();
            if (!data.success && data.confirm_required) { if (confirm(data.message)) completeTask(true); return; }
            if (data.success) { showToast('✅ Dikirim untuk verifikasi!', 'success'); setTimeout(() => { window.location.href = '{{ route('housekeeping.my-tasks') }}'; }, 1200); }
            else { showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { showToast('Gagal', 'error'); }
    }

    function showToast(msg, type = 'info') {
        const c = { success: 'linear-gradient(to right,#00b09b,#96c93d)', error: 'linear-gradient(to right,#ff5f6d,#ffc371)', info: 'linear-gradient(to right,#2193b0,#6dd5ed)', warning: 'linear-gradient(to right,#f7971e,#ffd200)' };
        Toastify({ text: msg, duration: 3000, gravity: 'top', position: 'right', style: { background: c[type]||c.info, borderRadius: '8px', fontWeight: '500' } }).showToast();
    }

    function showImageModal(url, title) {
        document.getElementById('imagePreviewImg').src = url;
        document.getElementById('imagePreviewTitle').textContent = title || 'Preview';
        imagePreviewModal.show();
    }

    async function uploadBeforeAfter(input, type) {
        const file = input.files[0];
        if (!file) return;

        let fileToUpload;
        try { fileToUpload = await compressImage(file, 1200, 0.7); } catch(e) { fileToUpload = file; }

        const formData = new FormData();
        formData.append('photo', fileToUpload);
        formData.append('type', type);

        try {
            const url = '{{ route('housekeeping.my-tasks.photos.store', $task) }}';
            const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: formData });
            const data = await response.json();
            if (data.success) { showToast('Foto ' + type + ' berhasil diupload!', 'success'); setTimeout(() => location.reload(), 600); }
            else { showToast(data.message || 'Gagal upload', 'error'); }
        } catch(e) { showToast('Gagal upload foto', 'error'); }
        input.value = '';
    }

    async function deleteTaskPhoto(photoId) {
        if (!confirm('Hapus foto ini?')) return;
        try {
            const url = '{{ route('housekeeping.my-tasks.photos.destroy', ':id') }}'.replace(':id', photoId);
            const response = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            const data = await response.json();
            if (data.success) { showToast('Foto dihapus', 'success'); setTimeout(() => location.reload(), 600); }
            else { showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { showToast('Gagal hapus foto', 'error'); }
    }
</script>
@endsection
