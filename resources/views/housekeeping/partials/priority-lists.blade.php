{{-- resources/views/housekeeping/partials/priority-lists.blade.php --}}
<div class="card mb-4">
    <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0"><i class="ri-list-ordered me-1 text-primary"></i> {{ __('housekeeping.priority_list') }}</h5>
    </div>
    <div class="card-body p-3">
        <div class="row g-4">
            {{-- Perlu Cleaning (Checkout/Dirty) --}}
            <div class="col-lg-4">
                <div class="hk-priority-card">
                    <div class="hk-priority-header bg-danger-subtle text-danger d-flex justify-content-between align-items-center">
                        <span>🧹 {{ __('housekeeping.needs_cleaning') }}</span>
                        <span class="badge bg-danger rounded-pill">{{ $dirtyRooms->count() }}</span>
                    </div>
                    <div class="hk-priority-body" data-simplebar style="max-height: 400px;">
                        @forelse($dirtyRooms as $room)
                            <div class="hk-priority-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-bold fs-15">🚪 {{ $room->room_number }}</div>
                                    <div class="text-danger fs-12 fw-semibold">High Priority</div>
                                </div>
                                <div class="text-muted fs-13 mb-3">
                                    <div>{{ $room->roomType->name ?? 'Standard Room' }}</div>
                                    <div>{{ __('housekeeping.checkout_guest') }}: 10:30 WIB</div>
                                </div>
                                @canany(['manage system', 'rooms.status.change'])
                                <button class="btn btn-sm btn-primary w-100 quick-manage-btn"
                                        data-id="{{ $room->id }}"
                                        data-number="{{ $room->room_number }}"
                                        data-status="{{ $room->status }}">
                                    🎯 {{ __('housekeeping.assign_ob') }}
                                </button>
                                @endcanany
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">Tidak ada kamar yang perlu dibersihkan.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sedang Dikerjakan (Refresh/Cleaning) --}}
            <div class="col-lg-4">
                <div class="hk-priority-card">
                    <div class="hk-priority-header bg-warning-subtle text-warning-emphasis d-flex justify-content-between align-items-center">
                        <span>🔄 {{ __('housekeeping.being_cleaned') }}</span>
                        <span class="badge bg-warning rounded-pill">{{ $cleaningRooms->count() }}</span>
                    </div>
                    <div class="hk-priority-body" data-simplebar style="max-height: 400px;">
                        @forelse($cleaningRooms as $room)
                            <div class="hk-priority-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-bold fs-15">🚪 {{ $room->room_number }}</div>
                                </div>
                                <div class="text-muted fs-13 mb-2">
                                    <div>{{ $room->roomType->name ?? 'Standard Room' }}</div>
                                    <div class="text-info fw-medium">👤 {{ $room->assignedStaff->name ?? 'None' }}</div>
                                </div>
                                <div class="progress mb-3" style="height: 6px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 50%"></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-info flex-grow-1 view-task-btn"
                                            data-room-id="{{ $room->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#taskDetailModal">
                                        👁️ Detail
                                    </button>
                                    <form action="{{ route('housekeeping.mark-clean', $room->id) }}" method="POST" data-ajax="true" class="flex-grow-1">
                                        @csrf
                                        <button type="submit" data-submit-protect="true" class="btn btn-sm btn-success w-100">
                                            ✅ Selesai
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">Tidak ada kamar yang sedang dibersihkan.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Perlu Perhatian (Maintenance/OOO) --}}
            <div class="col-lg-4">
                <div class="hk-priority-card">
                    <div class="hk-priority-header bg-dark-subtle text-dark d-flex justify-content-between align-items-center">
                        <span>⚠️ {{ __('housekeeping.needs_attention') }}</span>
                        <span class="badge bg-dark rounded-pill">{{ $maintenanceRooms->count() }}</span>
                    </div>
                    <div class="hk-priority-body" data-simplebar style="max-height: 400px;">
                        @forelse($maintenanceRooms as $room)
                            <div class="hk-priority-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-bold fs-15">🚪 {{ $room->room_number }}</div>
                                    <div class="text-dark fs-12 fw-semibold">{{ $room->status }}</div>
                                </div>
                                <div class="text-muted fs-13 mb-3">
                                    <div>{{ $room->roomType->name ?? 'Standard Room' }}</div>
                                    <div class="text-danger">🔧 Perlu dicek teknisi</div>
                                </div>
                                @canany(['manage system', 'rooms.status.change'])
                                <button class="btn btn-sm btn-outline-dark w-100 quick-manage-btn"
                                        data-id="{{ $room->id }}"
                                        data-number="{{ $room->room_number }}"
                                        data-status="{{ $room->status }}">
                                    ⚙️ {{ __('housekeeping.manage_room') }}
                                </button>
                                @endcanany
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">Tidak ada kamar yang bermasalah.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
