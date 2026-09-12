@extends('layouts.master')
@section('title')
    {{ __('housekeeping.dashboard_title') }}
@endsection
@section('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    <link href="{{ asset('css/housekeeping-custom.css') }}" rel="stylesheet">
    <style>
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            color: #495057 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-modal + .select2-container {
            z-index: 1060;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Housekeeping
        @endslot
        @slot('title')
            {{ __('housekeeping.dashboard_title') }}
        @endslot
    @endcomponent

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

    {{-- Quick Actions Toolbar --}}
    <div class="card mb-4 border-0 shadow-sm bg-primary-subtle">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2 overflow-auto" style="white-space: nowrap;">
                <span class="fw-semibold me-2 text-primary">⚡ {{ __('housekeeping.quick_actions') }}:</span>
                <button class="btn btn-sm btn-light border shadow-none" data-bs-toggle="modal" data-bs-target="#quickAssignModal" onclick="setTimeout(toggleAllDirty, 300);"><i class="ri-checkbox-multiple-line text-success"></i> {{ __('housekeeping.select_all_dirty') }}</button>
                <button class="btn btn-sm btn-light border shadow-none" data-bs-toggle="modal" data-bs-target="#quickAssignModal"><i class="ri-group-line text-primary"></i> {{ __('housekeeping.assign_bulk') }}</button>
                <button class="btn btn-sm btn-light border shadow-none" data-bs-toggle="modal" data-bs-target="#autoAssignModal"><i class="ri-user-shared-line text-success"></i> Auto Assign</button>
                <a href="{{ route('reports.housekeeping', ['date' => now()->toDateString()]) }}" class="btn btn-sm btn-light border shadow-none"><i class="ri-file-chart-line text-info"></i> {{ __('housekeeping.today_report') }}</a>
                <a href="{{ route('housekeeping.checklist-templates.index') }}" class="btn btn-sm btn-light border shadow-none"><i class="ri-list-check text-secondary"></i> {{ __('housekeeping.checklist_template') }}</a>
            </div>
        </div>
    </div>

    {{-- Status Cards --}}
    @include('housekeeping.partials.status-cards')

    {{-- Filter & Search --}}
    @include('housekeeping.partials.filter-search')

    {{-- Priority Lists --}}
    @include('housekeeping.partials.priority-lists')

    {{-- Room Grid --}}
    @include('housekeeping.partials.room-grid')

    {{-- Modals --}}
    @include('housekeeping.partials.manage-modal')

    <!-- Quick Assign Modal -->
    <div class="modal fade" id="quickAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title">⚡ Quick Assign — Cleaning Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('housekeeping.quick-assign') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">👤 Pilih OB / Cleaning Staff</label>
                            <select name="assigned_to" id="quickAssignStaff" class="form-select select2-modal" required>
                                <option value="">-- Pilih Staff --</option>
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">🧹 Kamar Kotor ({{ $dirtyRooms->count() }})</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllDirty()">Select All</button>
                        </div>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            @forelse($dirtyRooms as $room)
                            <div class="col-4">
                                <div class="form-check">
                                    <input class="form-check-input dirty-check" type="checkbox" name="room_ids[]" value="{{ $room->id }}" id="dr_{{ $room->id }}">
                                    <label class="form-check-label" for="dr_{{ $room->id }}">
                                        🚪 {{ $room->room_number }}
                                    </label>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-muted">Tidak ada kamar kotor.</div>
                            @endforelse
                        </div>
                        @if($cleanRooms->count() > 0)
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">🟢 Kamar Kosong ({{ $cleanRooms->count() }})</label>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleAllVacant()">Select All</button>
                        </div>
                        <div class="row g-2" style="max-height: 200px; overflow-y: auto;">
                            @foreach($cleanRooms as $room)
                            <div class="col-4">
                                <div class="form-check">
                                    <input class="form-check-input vacant-check" type="checkbox" name="room_ids[]" value="{{ $room->id }}" id="vc_{{ $room->id }}">
                                    <label class="form-check-label" for="vc_{{ $room->id }}">
                                        🚪 {{ $room->room_number }} <span class="badge bg-success-subtle text-success">Kosong</span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">✅ Assign Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Auto Assign Modal (Round-Robin) -->
    <div class="modal fade" id="autoAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title"><i class="ri-user-shared-line me-1"></i> Auto Assign — Merata ke Semua OB</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('housekeeping.auto-assign') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">👥 Pilih OB / Cleaning Staff (default: semua yang aktif)</label>
                            <select name="staff_ids[]" id="autoAssignStaff" class="form-select select2-modal" multiple required>
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}" selected>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">🧹 Pilih Kamar (default: semua Checkout/Dirty/Kosong)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllAuto()">Select All</button>
                        </div>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            @php
                                $assignableRooms = $allRooms->whereIn('status', ['Checkout', 'Dirty', 'dirty', 'Available', 'Clean', 'clean']);
                            @endphp
                            @forelse($assignableRooms as $room)
                            <div class="col-4">
                                <div class="form-check">
                                    <input class="form-check-input auto-check" type="checkbox" name="room_ids[]" value="{{ $room->id }}" id="ar_{{ $room->id }}" checked>
                                    <label class="form-check-label" for="ar_{{ $room->id }}">
                                        🚪 {{ $room->room_number }} 
                                        @if(in_array($room->status, ['Available', 'Clean', 'clean']))
                                            <span class="badge bg-success-subtle text-success">Kosong</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">{{ $room->status }}</span>
                                        @endif
                                    </label>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-muted">Tidak ada kamar untuk di-assign.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">✅ Assign Merata (Round-Robin)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Task Detail Modal for Admin -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title">Task Detail - Kamar <span id="taskDetailRoom" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="taskDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="{{ asset('js/housekeeping-custom.js') }}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('quickManageModal');
            let quickManageModal;
            if (modalElement) {
                quickManageModal = new bootstrap.Modal(modalElement);
            }
            const form = document.getElementById('quickManageForm');
            const roomNumSpan = document.getElementById('modalRoomNumber');
            const staffSelect = document.getElementById('modalStaffSelect');
            
            // UI elements for status
            const modalCurrentIcon = document.getElementById('modalCurrentIcon');
            const modalCurrentStatusText = document.getElementById('modalCurrentStatusText');

            // Initialize Select2 for staff dropdown
            if ($(staffSelect).length) {
                $(staffSelect).select2({
                    placeholder: '{{ __('housekeeping.choose_ob') }}',
                    allowClear: true,
                    dropdownParent: $('#quickManageModal'),
                    width: '100%'
                });
            }

            // Initialize Select2 for quick assign staff dropdown
            if ($('#quickAssignStaff').length) {
                $('#quickAssignStaff').select2({
                    placeholder: 'Pilih OB / Staff',
                    allowClear: false,
                    dropdownParent: $('#quickAssignModal'),
                    width: '100%'
                });
            }

            // Initialize Select2 for auto assign staff dropdown
            if ($('#autoAssignStaff').length) {
                $('#autoAssignStaff').select2({
                    placeholder: 'Pilih OB / Staff',
                    allowClear: true,
                    dropdownParent: $('#autoAssignModal'),
                    width: '100%'
                });
            }

            document.querySelectorAll('.quick-manage-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.dataset.id;
                    const roomNumber = this.dataset.number;
                    const currentStatus = this.dataset.status;
                    const staffId = this.dataset.staff;
                    
                    // Set room number
                    if (roomNumSpan) roomNumSpan.textContent = roomNumber;
                    
                    // Setup current status UI
                    let icon = '';
                    let text = '';
                    if (currentStatus === 'Available') { icon = '✅'; text = '{{ __('housekeeping.status.clean') }}'; }
                    else if (currentStatus === 'Checkout') { icon = '🧹'; text = '{{ __('housekeeping.status.dirty') }}'; }
                    else if (currentStatus === 'Room Refresh') { icon = '🔄'; text = '{{ __('housekeeping.status.cleaning') }}'; }
                    else if (currentStatus === 'In-House') { icon = '🏠'; text = '{{ __('housekeeping.status.occupied') }}'; }
                    else if (currentStatus === 'maintenance') { icon = '⚠️'; text = '{{ __('housekeeping.status.maintenance') }}'; }
                    else if (currentStatus === 'Out of Order') { icon = '🚫'; text = '{{ __('housekeeping.status.out_of_order') }}'; }
                    
                    if (modalCurrentIcon) modalCurrentIcon.textContent = icon;
                    if (modalCurrentStatusText) modalCurrentStatusText.textContent = text;
                    
                    // Check the correct radio button
                    const statusRadio = document.querySelector(`input[name="status"][value="${currentStatus}"]`);
                    if (statusRadio) {
                        statusRadio.checked = true;
                    } else {
                        // Uncheck all if unknown
                        document.querySelectorAll('input[name="status"]').forEach(r => r.checked = false);
                    }
                    
                    // Set selected staff
                    if (staffId && staffId !== 'undefined' && staffId !== '') {
                        $(staffSelect).val(staffId).trigger('change');
                    } else {
                        $(staffSelect).val('').trigger('change');
                    }
                    
                    // Set form action
                    if (form) {
                        form.action = '{{ route("housekeeping.update-status", ":id") }}'.replace(':id', id);
                    }
                    
                    // Show modal
                    if (quickManageModal) quickManageModal.show();
                });
            });

            // Task detail modal handler
            document.querySelectorAll('.view-task-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const roomId = this.dataset.roomId;
                    const taskDetailRoom = document.getElementById('taskDetailRoom');
                    const taskDetailContent = document.getElementById('taskDetailContent');

                    // Show loading
                    taskDetailContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
                    taskDetailRoom.textContent = '...';

                    try {
                        const response = await fetch('{{ route("housekeeping.task-details", ":id") }}'.replace(':id', roomId));
                        const data = await response.json();

                        if (!data.task) {
                            taskDetailContent.innerHTML = '<div class="alert alert-info">Tidak ada task cleaning untuk kamar ini.</div>';
                            return;
                        }

                        const task = data.task;
                        taskDetailRoom.textContent = roomId;

                        let statusBadge = '';
                        if (task.status === 'belum_mulai') statusBadge = '<span class="badge bg-warning text-dark">Belum Mulai</span>';
                        else if (task.status === 'sedang_dikerjakan') statusBadge = '<span class="badge bg-info">Sedang Dikerjakan</span>';
                        else statusBadge = '<span class="badge bg-success">Selesai</span>';

                        let checklistHtml = task.checklist.map(item => `
                            <li class="list-group-item ${item.is_done ? 'text-decoration-line-through text-muted' : ''}">
                                <i class="${item.is_done ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-muted'} me-2"></i>
                                ${item.name}
                            </li>
                        `).join('');

                        let photosHtml = '';
                        if (task.photos && task.photos.length > 0) {
                            photosHtml = '<div class="mt-3"><h6>Foto Bukti:</h6><div class="row g-2">' +
                                task.photos.map(photo => `
                                    <div class="col-3">
                                        <a href="${photo.url}" data-lightbox="task-photos" data-title="${photo.original_name}">
                                            <img loading="lazy" src="${photo.url}" alt="${photo.original_name}" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover;">
                                        </a>
                                    </div>
                                `).join('') + '</div></div>';
                        }

                        taskDetailContent.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> ${statusBadge}</p>
                                    <p><strong>Assigned OB:</strong> ${task.assigned_name}</p>
                                    <p><strong>Progress:</strong> ${task.progress}%</p>
                                    <div class="progress mb-3" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${task.progress}%"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    ${task.started_at ? '<p><strong>Mulai:</strong> ' + task.started_at + '</p>' : ''}
                                    ${task.completed_at ? '<p><strong>Selesai:</strong> ' + task.completed_at + '</p>' : ''}
                                    ${task.notes ? '<p><strong>Catatan:</strong><br>' + task.notes + '</p>' : ''}
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6>Checklist:</h6>
                                <ul class="list-group">${checklistHtml}</ul>
                            </div>
                            ${photosHtml}
                        `;
                    } catch (e) {
                        taskDetailContent.innerHTML = '<div class="alert alert-danger">Gagal memuat detail task.</div>';
                    }
                });
            });
        });

    // Function to toggle all dirty room checkboxes (used in Quick Assign modal)
    window.toggleAllDirty = function() {
        const checks = document.querySelectorAll('.dirty-check');
        const allChecked = Array.from(checks).every(c => c.checked);
        checks.forEach(c => c.checked = !allChecked);
    };

    window.toggleAllAuto = function() {
        const checks = document.querySelectorAll('.auto-check');
        const allChecked = Array.from(checks).every(c => c.checked);
        checks.forEach(c => c.checked = !allChecked);
    };

    window.toggleAllVacant = function() {
        const checks = document.querySelectorAll('.vacant-check');
        const allChecked = Array.from(checks).every(c => c.checked);
        checks.forEach(c => c.checked = !allChecked);
    };
</script>
@endsection
