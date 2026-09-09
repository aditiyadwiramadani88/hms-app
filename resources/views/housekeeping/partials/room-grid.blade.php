{{-- resources/views/housekeeping/partials/room-grid.blade.php --}}
<div class="card mb-4" id="hkRoomGridContainer">
    <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0"><i class="ri-hotel-bed-line me-1 text-primary"></i> {{ __('housekeeping.all_rooms') }}</h5>
    </div>
    <div class="card-body">
        @php
            $floors = $allRooms->pluck('floor')->unique()->sort();
        @endphp

        @foreach($floors as $floor)
            <div class="hk-floor-group" data-floor="{{ $floor }}">
                <div class="hk-floor-divider">
                    🏨 {{ __('housekeeping.all_rooms') }} - {{ __('housekeeping.floor') }} {{ $floor ?? 'Unassigned' }}
                </div>
                <div class="row g-3">
                    @foreach($allRooms->where('floor', $floor) as $room)
                        @php
                            $cardBorder = 'border-light';
                            $icon = '';
                            $colorClass = 'text-dark';

                            if($room->status === 'Available') {
                                if (isset($room->is_reserved_today) && $room->is_reserved_today) {
                                    $cardBorder = 'border-info border-2 border-dashed';
                                    $icon = '📅';
                                    $colorClass = 'text-info';
                                } else {
                                    $cardBorder = 'border-clean';
                                    $icon = '✅';
                                    $colorClass = 'text-success';
                                }
                            }
                            elseif($room->status === 'Checkout') {
                                $cardBorder = 'border-dirty';
                                $icon = '🧹';
                                $colorClass = 'text-danger';
                            }
                            elseif($room->status === 'Room Refresh') {
                                $cardBorder = 'border-cleaning';
                                $icon = '🔄';
                                $colorClass = 'text-warning';
                            }
                            elseif($room->status === 'In-House') {
                                $cardBorder = 'border-occupied';
                                $icon = '🏠';
                                $colorClass = 'text-primary';
                            }
                            elseif($room->status === 'maintenance') {
                                $cardBorder = 'border-maintenance';
                                $icon = '⚠️';
                                $colorClass = 'text-warning';
                            }
                            elseif($room->status === 'Out of Order') {
                                $cardBorder = 'border-out-of-order';
                                $icon = '🚫';
                                $colorClass = 'text-secondary';
                            }
                        @endphp

                        <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                            {{-- Ubah status kamar dibatasi Admin & Kepala Housekeeping (rooms.status.change).
                                 Staf HK biasa lihat kartu ini read-only -- start/complete task tetap lewat My Tasks, tidak lewat sini. --}}
                            <div class="hk-room-grid-card {{ $cardBorder }} p-2 {{ auth()->user()->canAny(['manage system', 'rooms.status.change']) ? 'quick-manage-btn' : '' }}"
                                 @if(auth()->user()->canAny(['manage system', 'rooms.status.change']))
                                 data-id="{{ $room->id }}"
                                 data-number="{{ $room->room_number }}"
                                 data-status="{{ $room->status }}"
                                 data-staff="{{ $room->status !== 'Available' ? $room->assigned_staff_id : '' }}"
                                 @endif>

                                <div class="fs-14 fw-bold mb-1">{{ $room->room_number }}</div>
                                <div class="fs-4 mb-1 {{ $colorClass }}">{{ $icon }}</div>

                                @if($room->assignedStaff && $room->status !== 'Available')
                                    <div class="fs-10 text-truncate text-muted" title="{{ $room->assignedStaff->name }}">
                                        {{ Str::before($room->assignedStaff->name, ' ') }}
                                    </div>
                                @else
                                    <div class="fs-10 text-muted">&nbsp;</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="card mb-4" id="hkRoomListContainer" style="display: none;">
    <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i> {{ __('housekeeping.all_rooms') }} (List View)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('housekeeping.rooms') }}</th>
                        <th>{{ __('housekeeping.floor') }}</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allRooms as $room)
                    <tr>
                        <td class="fw-bold">{{ $room->room_number }}</td>
                        <td>{{ $room->floor }}</td>
                        <td>
                            @if($room->status === 'Available')
                                @if(isset($room->is_reserved_today) && $room->is_reserved_today)
                                    <span class="badge bg-info-subtle text-info"><i class="ri-calendar-check-fill align-middle me-1"></i> Reserved</span>
                                @else
                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill align-middle me-1"></i> Bersih</span>
                                @endif
                            @elseif($room->status === 'Checkout') <span class="badge bg-danger-subtle text-danger"><i class="ri-history-line align-middle me-1"></i> Kotor</span>
                            @elseif($room->status === 'Room Refresh') <span class="badge bg-warning-subtle text-warning"><i class="ri-broom-fill align-middle me-1"></i> Dibersihkan</span>
                            @elseif($room->status === 'In-House') <span class="badge" style="background-color: #7c3aed20; color: #7c3aed;"><i class="ri-user-follow-fill align-middle me-1"></i> Terisi</span>
                            @elseif($room->status === 'maintenance') <span class="badge bg-warning-subtle text-warning"><i class="ri-error-warning-fill align-middle me-1"></i> Maintenance</span>
                            @else <span class="badge bg-dark-subtle text-dark"><i class="ri-forbid-2-fill align-middle me-1"></i> Rusak</span>
                            @endif
                        </td>
                        <td>{{ $room->status !== 'Available' ? ($room->assignedStaff->name ?? '-') : '-' }}</td>
                        <td>
                            @canany(['manage system', 'rooms.status.change'])
                            <button class="btn btn-sm btn-primary quick-manage-btn"
                                    data-id="{{ $room->id }}"
                                    data-number="{{ $room->room_number }}"
                                    data-status="{{ $room->status }}"
                                    data-staff="{{ $room->status !== 'Available' ? $room->assigned_staff_id : '' }}">
                                {{ __('housekeeping.manage_room') }}
                            </button>
                            @else
                            <span class="text-muted fs-12">—</span>
                            @endcanany
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
