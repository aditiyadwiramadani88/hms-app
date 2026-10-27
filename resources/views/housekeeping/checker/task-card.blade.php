<div class="col-md-6 col-lg-4 mb-3">
    <div class="card h-100 border-primary shadow-sm">
        <div class="card-header bg-primary-subtle d-flex align-items-center py-2 px-3">
            <h6 class="mb-0 flex-grow-1 fw-bold text-primary">🚪 Kamar {{ $task->room->room_number ?? '-' }}</h6>
            <span class="badge bg-primary">Menunggu</span>
        </div>
        <div class="card-body p-3">
            <div class="mb-2">
                <small class="text-muted">Tipe:</small> <span class="fw-semibold text-dark">{{ $task->room->roomType->name ?? '-' }}</span>
            </div>
            <div class="mb-2">
                <small class="text-muted">OB:</small> <span class="fw-semibold text-dark">{{ $task->assignedUser->name ?? '-' }}</span>
            </div>
            @php
                $submittedAt = $task->completed_at ?? $task->updated_at ?? $task->created_at;
                $isToday = $submittedAt ? $submittedAt->isToday() : true;
            @endphp
            <div class="mb-2 p-2 rounded {{ $isToday ? 'bg-light' : 'bg-danger-subtle' }}">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <small class="fw-semibold {{ $isToday ? 'text-muted' : 'text-danger' }}">
                        <i class="ri-time-line me-1"></i>Selesai Dibersihkan:
                    </small>
                    @if($submittedAt)
                        <span class="badge {{ $isToday ? 'bg-primary-subtle text-primary' : 'bg-danger text-white' }} fs-11">
                            {{ $submittedAt->diffForHumans() }}
                        </span>
                    @endif
                </div>
                <div class="fs-12 fw-medium {{ $isToday ? 'text-dark' : 'text-danger' }}">
                    {{ $submittedAt ? $submittedAt->translatedFormat('d M Y, H:i') : '-' }}
                </div>
            </div>
            <div class="mb-3">
                <small class="text-muted">Checklist:</small>
                <span class="fw-semibold text-dark">{{ $task->checklistItems->where('is_done', true)->count() }}/{{ $task->checklistItems->count() }}</span> selesai
            </div>
            <a href="{{ route('housekeeping.checker.tasks.show', $task) }}" class="btn btn-primary w-100 shadow-none">
                <i class="ri-search-eye-line me-1"></i> Review Task
            </a>
        </div>
    </div>
</div>
