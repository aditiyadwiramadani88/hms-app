@extends('layouts.master')
@section('title')
    Room Availability Calendar
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css">
    <style>
        .fc-event { cursor: pointer; padding: 2px 5px; }
        .fc-toolbar-title { font-size: 1.2rem !important; font-weight: bold; }
        .room-available-item { border-left: 3px solid #0ab39c; transition: all 0.3s; }
        .room-available-item:hover { background-color: #f3f6f9; transform: translateX(5px); }
        .badge-room { font-size: 12px; width: 45px; text-align: center; }
        .fc-event-inhouse { background-color: #7c3aed !important; border-color: #7c3aed !important; color: white !important; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Bookings
        @endslot
        @slot('title')
            {{ auth()->user()->hasRole('Front Page Only') ? 'Schedule' : 'Operational Calendar' }}
        @endslot
    @endcomponent

    {{-- Top Status Summary Bar --}}
    @if(!auth()->user()->hasRole('Front Page Only'))
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Clean (Available)</p>
                            @php $availableRooms = $rooms->whereIn('status', ['Available', 'available']); @endphp
                            <h4 class="mb-0 text-success">{{ $availableRooms->count() }}</h4>
                            <small class="text-muted">{{ $availableRooms->where('is_reserved_today', false)->count() }} ready to book</small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded-circle fs-3">
                                <i class="ri-checkbox-circle-fill text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Occupied / Check-in</p>
                            <h4 class="mb-0 text-danger">{{ $rooms->whereIn('status', ['In-House', 'Checkin'])->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-3">
                                <i class="ri-user-follow-fill text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Dirty / Cleaning</p>
                            <h4 class="mb-0 text-warning">{{ $rooms->whereIn('status', ['Checkout', 'Room Refresh', 'dirty'])->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded-circle fs-3">
                                <i class="ri-brush-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Maintenance / OOO</p>
                            <h4 class="mb-0 text-dark">{{ $rooms->whereIn('status', ['Out of Order', 'maintenance'])->count() }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-dark-subtle rounded-circle fs-3">
                                <i class="ri-settings-line text-dark"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                @php
                    $cardCount = $rooms->whereIn('status', ['Available', 'available'])->count()
                        + $rooms->whereIn('status', ['In-House', 'Checkin'])->count()
                        + $rooms->whereIn('status', ['Checkout', 'Room Refresh', 'dirty'])->count()
                        + $rooms->whereIn('status', ['Out of Order', 'maintenance'])->count();
                    $totalRooms = $rooms->count();
                @endphp
                <div class="d-flex justify-content-center gap-2 fs-12 text-muted">
                    <span>Total: <strong>{{ $cardCount }}</strong> / {{ $totalRooms }} rooms</span>
                    @if($cardCount !== $totalRooms)
                    <span class="text-danger">⚠️ {{ $totalRooms - $cardCount }} uncategorized</span>
                    @else
                    <span class="text-success">✅ All {{ $totalRooms }} rooms accounted</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!auth()->user()->hasRole('Front Page Only'))
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="{{ route('dashboard') }}" class="btn btn-soft-secondary btn-sm">
                <i class="ri-dashboard-line align-bottom me-1"></i> Back to Statistics Dashboard
            </a>
        </div>
    </div>
    @endif

    <div class="row">
        {{-- Left: The Main Calendar --}}
        <div class="col-xl-9">
            <div class="card card-height-100">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0"><i class="ri-calendar-todo-fill me-2 text-primary"></i>Room Occupancy Schedule</h5>
                        @if(!auth()->user()->hasRole('Front Page Only'))
                        <div class="flex-shrink-0">
                            <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm">
                                <i class="ri-add-line align-middle"></i> New Booking
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3 justify-content-center">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge" style="width: 15px; height: 15px; background-color: #7c3aed;">&nbsp;</span>
                            <span class="fs-12">In-House (Checked-In)</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-success" style="width: 15px; height: 15px;">&nbsp;</span>
                            <span class="fs-12">Confirmed (Expected)</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-warning" style="width: 15px; height: 15px;">&nbsp;</span>
                            <span class="fs-12">Pending / Other</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-danger" style="width: 15px; height: 15px;">&nbsp;</span>
                            <span class="fs-12">Checked-Out</span>
                        </div>
                    </div>
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        {{-- Right: Detailed Readiness Panel --}}
        <div class="col-xl-3">
            <div class="card card-height-100">
                <div class="card-header bg-light-subtle">
                    <h5 class="card-title mb-0"><i class="ri-apps-2-line me-2 text-primary"></i>Room Readiness</h5>
                </div>
                <div class="card-body p-0">
                    <div data-simplebar style="max-height: 750px;" class="p-3">
                        
                        {{-- Available Section --}}
                        <div class="mb-4">
                            <h6 class="text-success text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2">
                                <i class="ri-checkbox-circle-line me-1"></i> Ready to Book (Clean)
                            </h6>
                            @php $cleanRooms = $rooms->whereIn('status', ['Available', 'available'])->where('is_reserved_today', false)->groupBy('roomType.name'); @endphp
                            @forelse($cleanRooms as $typeName => $roomGroup)
                                <div class="mb-2">
                                    <small class="text-muted fw-semibold fs-10">{{ $typeName }}</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roomGroup as $room)
                                            <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" 
                                               class="badge bg-white border border-success text-success p-2 badge-room" 
                                               title="Ready: Room {{ $room->room_number }}">
                                                {{ $room->room_number }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fs-12 italic text-center py-2">No rooms ready.</p>
                            @endforelse
                        </div>

                        {{-- Reserved Section --}}
                        <div class="mb-4">
                            <h6 class="text-info text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2">
                                <i class="ri-calendar-check-line me-1"></i> Reserved Today (Clean)
                            </h6>
                            @php $reservedRooms = $rooms->whereIn('status', ['Available', 'available'])->where('is_reserved_today', true)->groupBy('roomType.name'); @endphp
                            @forelse($reservedRooms as $typeName => $roomGroup)
                                <div class="mb-2">
                                    <small class="text-muted fw-semibold fs-10">{{ $typeName }}</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roomGroup as $room)
                                            <span class="badge bg-white border border-info text-info p-2 badge-room border-dashed" 
                                               title="Reserved: Room {{ $room->room_number }}">
                                                {{ $room->room_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fs-12 italic text-center py-2">No reserved rooms.</p>
                            @endforelse
                        </div>

                        {{-- In-House Section --}}
                        <div class="mb-4">
                            <h6 class="text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2" style="color: #7c3aed;">
                                <i class="ri-user-follow-line me-1"></i> In-House (Occupied)
                            </h6>
                            @php $inHouseRooms = $rooms->where('status', 'In-House')->groupBy('roomType.name'); @endphp
                            @forelse($inHouseRooms as $typeName => $roomGroup)
                                <div class="mb-2">
                                    <small class="text-muted fw-semibold fs-10">{{ $typeName }}</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roomGroup as $room)
                                            <span class="badge p-2 badge-room text-white" 
                                               style="background-color: #7c3aed;"
                                               title="Occupied: Room {{ $room->room_number }}">
                                                {{ $room->room_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fs-12 italic text-center py-2">No active guests in rooms.</p>
                            @endforelse
                        </div>

                        {{-- Checkin (Expected) Section --}}
                        <div class="mb-4">
                            <h6 class="text-info text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2">
                                <i class="ri-login-box-line me-1"></i> Check-in (Expected Today)
                            </h6>
                            @php $checkinRooms = $rooms->where('status', 'Checkin')->groupBy('roomType.name'); @endphp
                            @forelse($checkinRooms as $typeName => $roomGroup)
                                <div class="mb-2">
                                    <small class="text-muted fw-semibold fs-10">{{ $typeName }}</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roomGroup as $room)
                                            <span class="badge bg-info text-white p-2 badge-room" 
                                               title="Arriving: Room {{ $room->room_number }}">
                                                {{ $room->room_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fs-12 italic text-center py-2">No rooms waiting for check-in.</p>
                            @endforelse
                        </div>

                        {{-- Dirty / Checkout Section --}}
                        <div class="mb-4">
                            <h6 class="text-danger text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2">
                                <i class="ri-brush-line me-1"></i> Dirty (Checked-out)
                            </h6>
                            @php $dirtyRoomsList = $rooms->whereIn('status', ['Checkout', 'Room Refresh', 'dirty'])->groupBy('roomType.name'); @endphp
                            @forelse($dirtyRoomsList as $typeName => $roomGroup)
                                <div class="mb-2">
                                    <small class="text-muted fw-semibold fs-10">{{ $typeName }}</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roomGroup as $room)
                                            <a href="{{ route('housekeeping.index') }}" 
                                               class="badge bg-danger text-white p-2 badge-room" 
                                               title="{{ $room->status }}: Room {{ $room->room_number }}">
                                                {{ $room->room_number }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fs-12 italic text-center py-2">No rooms need cleaning.</p>
                            @endforelse
                        </div>

                        {{-- Maintenance Section --}}
                        <div class="mb-0">
                            <h6 class="text-dark text-uppercase fw-bold fs-11 mb-3 border-bottom pb-2">
                                <i class="ri-settings-line me-1"></i> Maintenance / OOO
                            </h6>
                            @php $maintenanceRoomsList = $rooms->whereIn('status', ['Out of Order', 'maintenance']); @endphp
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($maintenanceRoomsList as $room)
                                    <span class="badge bg-dark text-white border border-dark p-2 badge-room" title="{{ $room->status }}">
                                        {{ $room->room_number }}
                                    </span>
                                @empty
                                    <p class="text-muted fs-12 italic text-center py-2 w-100">No rooms in maintenance.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-light border-top-0">
                    <div class="d-grid gap-1">
                        <a href="{{ route('housekeeping.index') }}" class="btn btn-soft-secondary btn-sm">Go to Housekeeping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                themeSystem: 'bootstrap5',
                events: [
                    @foreach($bookings as $booking)
                    {
                        id: '{{ $booking->id }}',
                        title: '{{ $booking->is_custom ? "[C]" : "R-" . ($booking->room->room_number ?? "?") }}: {{ $booking->guest->name ?? "Guest" }}',
                        start: '{{ $booking->check_in->format("Y-m-d") }}',
                        end: '{{ $booking->check_out->format("Y-m-d") }}',
                        url: '{{ route("bookings.show", $booking->id) }}',
                        @php
                            $colorClass = 'bg-warning border-warning'; // Default Pending
                            if($booking->status === 'checked_in') $colorClass = 'fc-event-inhouse';
                            elseif($booking->status === 'confirmed') $colorClass = 'bg-success border-success';
                            elseif($booking->status === 'checked_out') $colorClass = 'bg-danger border-danger';
                        @endphp
                        className: '{{ $colorClass }} text-white',
                    },
                    @endforeach
                ],
                eventClick: function(info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                }
            });
            calendar.render();
        });
    </script>
@endsection
