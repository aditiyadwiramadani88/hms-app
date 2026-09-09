@extends('layouts.master')
@section('title')
    Task {{ $task->room->room_number ?? 'Detail' }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('ob.dashboard') }}">Dashboard</a>
        @endslot
        @slot('title')
            Task {{ $task->room->room_number ?? '' }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">
                        <i class="ri-home-line me-1"></i>
                        Kamar {{ $task->room->room_number ?? '-' }}
                    </h5>
                    @if($task->status === 'belum_mulai')
                        <span class="badge bg-warning text-dark">Belum Mulai</span>
                    @elseif($task->status === 'sedang_dikerjakan')
                        <span class="badge bg-info">Sedang Dikerjakan</span>
                    @else
                        <span class="badge bg-success">Selesai</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-secondary me-1">Lantai {{ $task->room->floor ?? '-' }}</span>
                        <span class="badge bg-secondary">{{ $task->room->roomType->name ?? '-' }}</span>
                    </div>

                    <div class="mb-3">
                        <span>Progress: </span>
                        <strong>{{ $progress }}%</strong>
                        <div class="progress mt-1" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    <hr>

                    <h6>Checklist Cleaning</h6>
                    <div class="checklist-items">
                        @foreach($task->checklistItems as $item)
                        <div class="card mb-3 border shadow-sm cursor-pointer" 
                             onclick="{{ $item->is_done ? '' : 'openChecklistModal('.$item->id.', \''.$item->name.'\')' }}">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">{{ $item->name }}</h6>
                                @if($item->is_done)
                                    <span class="badge bg-success"><i class="ri-check-line"></i></span>
                                @else
                                    <span class="badge bg-danger">Wajib Foto</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="checklistModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTitle"></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="modalItemId">
                                    <div class="text-center mb-3">
                                        <button type="button" class="btn btn-outline-primary btn-lg w-100" 
                                                onclick="document.getElementById('modalFileInput').click()">
                                            <i class="ri-camera-line fs-20"></i><br>Ambil Foto
                                        </button>
                                        <input type="file" id="modalFileInput" class="d-none" accept="image/*" capture="environment">
                                        <img loading="lazy" id="modalPreview" class="img-fluid mt-2 d-none rounded" style="max-height: 200px;">
                                    </div>
                                    <textarea id="modalComment" class="form-control" placeholder="Komentar..."></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" onclick="saveChecklist()">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($task->status !== 'selesai')
                    <hr>

                    <h6>Catatan Keseluruhan</h6>
                    <div class="mb-3">
                        <textarea class="form-control" id="taskNotes" rows="2" placeholder="Tambahkan catatan (opsional)">{{ $task->notes }}</textarea>
                    </div>

                    <button type="button" class="btn btn-success btn-lg w-100" onclick="completeTask()">
                        <i class="ri-check-line me-1"></i> Selesai
                    </button>
                    @endif

                    @if($task->status === 'selesai')
                    <hr>
                    <div class="alert alert-success">
                        <i class="ri-check-line me-1"></i>
                        Task selesai pada {{ $task->completed_at->format('d/m/Y H:i') }}
                    </div>
                    @if($task->notes)
                    <p><strong>Catatan:</strong> {{ $task->notes }}</p>
                    @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if($task->photos->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Foto Bukti</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($task->photos as $photo)
                        <div class="col-6">
                            <a href="{{ $photo->url }}" data-lightbox="gallery" data-title="{{ $photo->original_name }}">
                                <img loading="lazy" src="{{ $photo->url }}" alt="{{ $photo->original_name }}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        const taskId = {{ $task->id }};
        let photoCount = {{ $task->photos->count() }};

        // Toggle checklist item
        document.querySelectorAll('.checklist-items input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', async function() {
                const itemId = this.dataset.itemId;
                const label = this.nextElementSibling;

                try {
                    const response = await fetch('{{ route("ob.checklist.update", ":id") }}'.replace(':id', itemId), {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (data.is_done) {
                        label.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        label.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                    // Update progress bar
                    location.reload();
                } catch (e) {
                    console.error(e);
                    this.checked = !this.checked;
                }
            });
        });

        // Upload photo
        async function uploadPhoto() {
            const input = document.getElementById('photoInput');
            if (!input.files[0]) return;

            if (photoCount >= 5) {
                alert('Maksimal 5 foto per task.');
                return;
            }

            const formData = new FormData();
            formData.append('photo', input.files[0]);

            try {
                const response = await fetch('{{ route("ob.tasks.photos.store", ":id") }}'.replace(':id', taskId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData,
                });
                const data = await response.json();
                if (data.success) {
                    const preview = document.getElementById('photoPreview');
                    const div = document.createElement('div');
                    div.className = 'position-relative';
                    div.innerHTML = `<img loading="lazy" src="${data.photo.url}" alt="" style="width: 80px; height: 80px; object-fit: cover;" class="rounded">`;
                    preview.appendChild(div);
                    photoCount++;
                    input.value = '';
                    if (photoCount >= 5) {
                        input.disabled = true;
                    }
                } else {
                    alert(data.message || 'Upload failed');
                }
            } catch (e) {
                console.error(e);
                alert('Upload failed');
            }
        }

        // Complete task
        async function completeTask(force = false) {
            const notes = document.getElementById('taskNotes')?.value || '';

            const body = { notes };
            if (force) body.force = true;

            try {
                const response = await fetch('{{ route("ob.tasks.complete", ":id") }}'.replace(':id', taskId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json();

                if (!data.success && data.confirm_required) {
                    if (confirm(data.message)) {
                        completeTask(true);
                    }
                    return;
                }

                if (data.success) {
                    window.location.href = '{{ route('ob.dashboard') }}';
                } else {
                    alert(data.message || 'Failed');
                }
            } catch (e) {
                console.error(e);
                alert('Failed');
            }
        }
    </script>
    @endpush
@endsection
