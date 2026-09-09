@extends('layouts.master')
@section('title')
    Checker - Kamar {{ $task->room->room_number ?? '' }}
@endsection
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
    .log-timeline { position: relative; padding-left: 24px; }
    .log-timeline::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .log-entry { position: relative; margin-bottom: 1.5rem; }
    .log-entry::before { content: ''; position: absolute; left: -19px; top: 6px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid #fff; }
    .log-entry.log-approved::before { background: #198754; }
    .log-entry.log-rejected::before { background: #dc3545; }
    .log-entry.log-photo::before { background: #0dcaf0; }
    .log-entry.log-submitted::before { background: #6c757d; }
    .log-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; margin-top: 8px; }
    .log-photo-grid img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; transition: transform 0.2s; }
    .log-photo-grid img:hover { transform: scale(1.05); }
    .photo-thumb { cursor: pointer; transition: transform 0.15s; }
    .photo-thumb:hover { transform: scale(1.05); opacity: 0.9; }
    @media (max-width: 768px) {
        .log-photo-grid { grid-template-columns: repeat(3, 1fr); }
        .checker-actions .btn { font-size: 0.8rem; padding: 0.4rem 0.6rem; }
    }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('housekeeping.checker.dashboard') }}">Checker</a> @endslot
        @slot('title') Kamar {{ $task->room->room_number ?? '' }} @endslot
    @endcomponent

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Task Header -->
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">🚪 Kamar {{ $task->room->room_number ?? '-' }}</h5>
                            <span class="badge bg-light text-dark me-1">Lantai {{ $task->room->floor ?? '-' }}</span>
                            <span class="badge bg-light text-dark">{{ $task->room->roomType->name ?? '-' }}</span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6 px-3 py-2">⏳ Menunggu Verifikasi</span>
                            <div class="text-muted fs-12 mt-1">OB: <strong>{{ $task->assignedUser->name ?? '-' }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Before/After Photos from OB -->
            @php
                $beforePhotos = $task->photos->where('type', 'before');
                $afterPhotos = $task->photos->where('type', 'after');
            @endphp
            @if($beforePhotos->isNotEmpty() || $afterPhotos->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="ri-image-2-line me-2"></i> Foto Before & After</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold fs-12 text-uppercase text-muted">Before</label>
                            @if($beforePhotos->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($beforePhotos as $photo)
                                    <img loading="lazy" src="{{ $photo->url }}" class="rounded border photo-thumb" style="width:80px;height:80px;object-fit:cover;" onclick="showImageModal('{{ $photo->url }}', 'Before')">
                                    @endforeach
                                </div>
                            @else
                                <small class="text-muted">Tidak ada</small>
                            @endif
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold fs-12 text-uppercase text-muted">After</label>
                            @if($afterPhotos->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($afterPhotos as $photo)
                                    <img loading="lazy" src="{{ $photo->url }}" class="rounded border photo-thumb" style="width:80px;height:80px;object-fit:cover;" onclick="showImageModal('{{ $photo->url }}', 'After')">
                                    @endforeach
                                </div>
                            @else
                                <small class="text-muted">Tidak ada</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Checklist Items Review -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="ri-checkbox-multiple-line me-2"></i> Review Checklist ({{ $task->checklistItems->count() }} item)</h6>
                </div>
                <div class="card-body p-0">
                    @foreach($task->checklistItems as $item)
                    <div class="border-bottom p-3" id="item-card-{{ $item->id }}">
                        <!-- Item Header -->
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    @if($item->is_done)
                                        <i class="ri-checkbox-circle-fill text-success fs-5 me-2"></i>
                                    @else
                                        <i class="ri-checkbox-blank-circle-line text-muted fs-5 me-2"></i>
                                    @endif
                                    <strong>{{ $item->name }}</strong>
                                </div>
                                @if($item->comment)
                                <div class="ms-4 text-muted fs-13">
                                    <i class="ri-chat-3-line me-1"></i> "{{ $item->comment }}"
                                </div>
                                @endif
                            </div>
                            <div id="item-status-{{ $item->id }}">
                                @if($item->verification_status === 'approved')
                                    <span class="badge bg-success">✅ OK</span>
                                @elseif($item->verification_status === 'rejected')
                                    <span class="badge bg-danger">❌ Reject</span>
                                @else
                                    <span class="badge bg-warning text-dark">⏳</span>
                                @endif
                            </div>
                        </div>

                        <!-- OB Photo Evidence -->
                        @if($item->photo_url)
                        <div class="ms-4 mt-2">
                            <img loading="lazy" src="{{ $item->photo_url }}" alt="" class="rounded border photo-thumb" style="width: 100px; height: 75px; object-fit: cover;" onclick="showImageModal('{{ $item->photo_url }}', '{{ $item->name }} - Foto OB')">
                            <small class="text-muted d-block mt-1">Foto dari OB</small>
                        </div>
                        @else
                            @if($item->is_done)
                            <div class="ms-4 mt-1">
                                <small class="text-muted"><i class="ri-checkbox-circle-line me-1"></i>Dicentang tanpa foto</small>
                            </div>
                            @endif
                        @endif

                        <!-- Checker Note (if rejected) -->
                        @if($item->checker_note)
                        <div class="ms-4 mt-2 alert alert-danger py-2 mb-0 fs-12">
                            <i class="ri-user-star-line me-1"></i> <strong>Checker:</strong> {{ $item->checker_note }}
                        </div>
                        @endif

                        <!-- Actions -->
                        @if($item->verification_status === 'pending')
                        <div class="ms-4 mt-2 checker-actions">
                            <button class="btn btn-sm btn-success" onclick="verifyItem({{ $item->id }}, 'approved')">
                                <i class="ri-check-line"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showRejectModal({{ $item->id }}, '{{ addslashes($item->name) }}')">
                                <i class="ri-close-line"></i> Reject
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Final Actions -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-success btn-lg w-100" onclick="approveTask()">
                                <i class="ri-check-double-line me-1"></i> <span class="d-none d-sm-inline">Approve</span> Task
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-danger btn-lg w-100" onclick="showRejectTaskModal()">
                                <i class="ri-arrow-go-back-line me-1"></i> <span class="d-none d-sm-inline">Reject &</span> Revisi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: History Log -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header bg-light py-2 d-flex align-items-center">
                    <h6 class="mb-0 flex-grow-1"><i class="ri-history-line me-2"></i> History Log</h6>
                    <span class="badge bg-secondary">{{ $task->logs->count() }}</span>
                </div>
                <div class="card-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    @if($task->logs->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="ri-history-line fs-1 d-block mb-2"></i>
                            <p class="mb-0">Belum ada aktivitas</p>
                        </div>
                    @else
                    <div class="log-timeline">
                        @foreach($task->logs as $log)
                        @php
                            $logClass = 'log-submitted';
                            if (str_contains($log->action, 'approved')) $logClass = 'log-approved';
                            elseif (str_contains($log->action, 'rejected')) $logClass = 'log-rejected';
                            elseif (str_contains($log->action, 'photo')) $logClass = 'log-photo';
                        @endphp
                        <div class="log-entry {{ $logClass }}">
                            <div class="d-flex align-items-center mb-1">
                                <strong class="fs-13">{{ $log->user->name ?? 'System' }}</strong>
                                <span class="badge {{ $log->role === 'checker' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }} ms-2 fs-10">{{ ucfirst($log->role) }}</span>
                                <small class="text-muted ms-auto">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            
                            <!-- Action Label -->
                            <div class="mb-1">
                                @if($log->action === 'item_approved')
                                    <span class="text-success fs-12"><i class="ri-check-line me-1"></i>Approved item</span>
                                @elseif($log->action === 'item_rejected')
                                    <span class="text-danger fs-12"><i class="ri-close-line me-1"></i>Rejected item</span>
                                @elseif($log->action === 'checker_photo')
                                    <span class="text-info fs-12"><i class="ri-camera-line me-1"></i>Upload foto banding</span>
                                @elseif($log->action === 'approved')
                                    <span class="text-success fs-12"><i class="ri-check-double-line me-1"></i>Task approved</span>
                                @elseif($log->action === 'rejected')
                                    <span class="text-danger fs-12"><i class="ri-arrow-go-back-line me-1"></i>Task rejected</span>
                                @else
                                    <span class="text-muted fs-12"><i class="ri-file-list-line me-1"></i>{{ $log->action }}</span>
                                @endif
                            </div>

                            <!-- Note -->
                            @if($log->note)
                            <p class="mb-1 fs-12 text-dark bg-light rounded p-2">{{ $log->note }}</p>
                            @endif

                            <!-- Photo -->
                            @if($log->photo_url)
                            <div class="log-photo-grid">
                                <img loading="lazy" src="{{ $log->photo_url }}" alt="Foto banding" onclick="showImageModal('{{ $log->photo_url }}', '{{ addslashes($log->note ?? 'Foto banding') }}')">
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
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

    <!-- Reject Item Modal (with photo banding) -->
    <div class="modal fade" id="rejectItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="ri-close-circle-line me-2"></i> Reject: <span id="rejectItemName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rejectItemId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Reject <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectItemNote" rows="3" placeholder="Jelaskan apa yang perlu diperbaiki..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">📷 Foto Banding <small class="text-muted">(opsional, sebagai bukti)</small></label>
                        <input type="file" class="form-control" id="rejectPhotoInput" accept="image/jpeg,image/png" capture="environment">
                    </div>
                    <div id="rejectPhotoPreview" class="text-center mb-3" style="display:none;">
                        <img loading="lazy" id="rejectPreviewImg" src="" class="img-fluid rounded border" style="max-height: 150px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="rejectItemBtn" onclick="submitRejectItem()">
                        <i class="ri-close-line me-1"></i> Reject Item
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Task Modal -->
    <div class="modal fade" id="rejectTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="ri-arrow-go-back-line me-2"></i> Reject & Kirim Revisi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan untuk OB <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectTaskNote" rows="3" placeholder="Jelaskan apa yang perlu diperbaiki..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="submitRejectTask()">
                        <i class="ri-arrow-go-back-line me-1"></i> Reject & Kirim ke OB
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    let rejectItemModal, rejectTaskModal, imagePreviewModal;

    document.addEventListener('DOMContentLoaded', function() {
        rejectItemModal = new bootstrap.Modal(document.getElementById('rejectItemModal'));
        rejectTaskModal = new bootstrap.Modal(document.getElementById('rejectTaskModal'));
        imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));

        document.getElementById('rejectPhotoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('rejectPreviewImg').src = ev.target.result;
                    document.getElementById('rejectPhotoPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('rejectPhotoPreview').style.display = 'none';
            }
        });
    });

    async function verifyItem(itemId, status) {
        try {
            const url = `{{ route('housekeeping.checker.items.verify', ':item') }}`.replace(':item', itemId);
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status, note: null }),
            });
            const data = await response.json();
            if (data.success) {
                showToast('Item approved! ✅', 'success');
                document.getElementById(`item-status-${itemId}`).innerHTML = '<span class="badge bg-success">✅ OK</span>';
                // Hide action buttons
                const card = document.getElementById(`item-card-${itemId}`);
                const actions = card.querySelector('.checker-actions');
                if (actions) actions.remove();
            }
        } catch (e) {
            showToast('Gagal. Coba lagi.', 'error');
        }
    }

    function showRejectModal(itemId, itemName) {
        document.getElementById('rejectItemId').value = itemId;
        document.getElementById('rejectItemName').textContent = itemName;
        document.getElementById('rejectItemNote').value = '';
        document.getElementById('rejectPhotoInput').value = '';
        document.getElementById('rejectPhotoPreview').style.display = 'none';
        rejectItemModal.show();
    }

    async function submitRejectItem() {
        const itemId = document.getElementById('rejectItemId').value;
        const note = document.getElementById('rejectItemNote').value;
        const photoInput = document.getElementById('rejectPhotoInput');
        const btn = document.getElementById('rejectItemBtn');

        if (!note.trim()) { showToast('Alasan reject wajib diisi', 'error'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Memproses...';

        try {
            // 1. Reject the item
            const verifyUrl = `{{ route('housekeeping.checker.items.verify', ':item') }}`.replace(':item', itemId);
            await fetch(verifyUrl, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'rejected', note: note }),
            });

            // 2. Upload foto banding jika ada
            if (photoInput.files[0]) {
                const formData = new FormData();
                formData.append('photo', photoInput.files[0]);
                formData.append('note', note);

                const evidenceUrl = `{{ route('housekeeping.checker.items.evidence', ':item') }}`.replace(':item', itemId);
                await fetch(evidenceUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData,
                });
            }

            showToast('Item rejected ❌', 'warning');
            rejectItemModal.hide();

            // Update UI: badge + hide buttons
            document.getElementById(`item-status-${itemId}`).innerHTML = '<span class="badge bg-danger">❌ Reject</span>';
            const card = document.getElementById(`item-card-${itemId}`);
            const actions = card.querySelector('.checker-actions');
            if (actions) actions.remove();
            // Show checker note inline
            const noteDiv = document.createElement('div');
            noteDiv.className = 'ms-4 mt-2 alert alert-danger py-2 mb-0 fs-12';
            noteDiv.innerHTML = `<i class="ri-user-star-line me-1"></i> <strong>Checker:</strong> ${note}`;
            card.appendChild(noteDiv);

        } catch (e) {
            showToast('Gagal. Coba lagi.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-close-line me-1"></i> Reject Item';
        }
    }

    async function approveTask() {
        if (!confirm('Approve task ini? Kamar akan ditandai Available.')) return;
        try {
            const response = await fetch(`{{ route('housekeeping.checker.tasks.approve', $task) }}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ note: 'Approved by checker' }),
            });
            const data = await response.json();
            if (data.success) {
                showToast('✅ Task Approved! Kamar Available.', 'success');
                setTimeout(() => window.location.href = '{{ route('housekeeping.checker.dashboard') }}', 1500);
            } else {
                showToast(data.message || 'Gagal', 'error');
            }
        } catch (e) {
            showToast('Gagal. Coba lagi.', 'error');
        }
    }

    function showRejectTaskModal() {
        document.getElementById('rejectTaskNote').value = '';
        rejectTaskModal.show();
    }

    async function submitRejectTask() {
        const note = document.getElementById('rejectTaskNote').value;
        if (!note.trim()) { showToast('Catatan wajib diisi', 'error'); return; }

        try {
            const response = await fetch(`{{ route('housekeeping.checker.tasks.reject', $task) }}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ note: note }),
            });
            const data = await response.json();
            if (data.success) {
                showToast('Task dikembalikan ke OB untuk revisi', 'warning');
                rejectTaskModal.hide();
                setTimeout(() => window.location.href = '{{ route('housekeeping.checker.dashboard') }}', 1500);
            }
        } catch (e) {
            showToast('Gagal. Coba lagi.', 'error');
        }
    }

    function showToast(message, type = 'info') {
        const colors = {
            success: 'linear-gradient(to right, #00b09b, #96c93d)',
            error: 'linear-gradient(to right, #ff5f6d, #ffc371)',
            info: 'linear-gradient(to right, #2193b0, #6dd5ed)',
            warning: 'linear-gradient(to right, #f7971e, #ffd200)',
        };
        Toastify({ text: message, duration: 3000, gravity: 'top', position: 'right', style: { background: colors[type] || colors.info, borderRadius: '8px', fontWeight: '500' } }).showToast();
    }

    function showImageModal(url, title) {
        document.getElementById('imagePreviewImg').src = url;
        document.getElementById('imagePreviewTitle').textContent = title || 'Preview';
        imagePreviewModal.show();
    }
</script>
@endsection
