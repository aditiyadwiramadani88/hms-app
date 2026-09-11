@extends('layouts.master')
@section('title')
    Room Availability Calendar
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css">
    <style>
        .fc-event { cursor: pointer; padding: 2px 5px; font-size: 0.78rem; border-radius: 4px; }
        .fc-toolbar-title { font-size: 1.2rem !important; font-weight: bold; }
        .room-available-item { border-left: 3px solid #0ab39c; transition: all 0.3s; }
        .room-available-item:hover { background-color: #f3f6f9; transform: translateX(5px); }
        .badge-room { font-size: 12px; width: 45px; text-align: center; }
        .fc-event-inhouse { background-color: #7c3aed !important; border-color: #7c3aed !important; color: white !important; }

        /* Badge +X more styling */
        .fc-more-link {
            font-weight: 600 !important;
            color: #405189 !important;
            font-size: 0.72rem !important;
            padding: 1px 5px !important;
            background: rgba(64, 81, 137, 0.12) !important;
            border-radius: 4px !important;
            display: inline-block !important;
            margin-top: 1px !important;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }
        .fc-more-link:hover {
            background: rgba(64, 81, 137, 0.25) !important;
            color: #2b3964 !important;
        }

        /* Styling untuk Modal Daftar Booking (+more) */
        #calendarMoreModal .modal-content {
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        #calendarMoreModal .fc-event {
            transition: all 0.15s ease;
        }
        #calendarMoreModal .fc-event:hover {
            opacity: 0.9;
            transform: scale(1.01);
        }

        /* Nonaktifkan popover bawaan FullCalendar agar tidak tampil ganda dengan Bootstrap Modal */
        .fc-popover {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
            opacity: 0 !important;
        }

        /* Mobile specific adjustments (< 768px) */
        @media (max-width: 767.98px) {
            .calendar-card .card-body {
                padding: 10px 4px !important;
            }
            .calendar-legend {
                font-size: 0.72rem !important;
                gap: 6px !important;
            }
            .calendar-legend .badge {
                width: 10px !important;
                height: 10px !important;
            }

            /* Responsive Toolbar: Row 1 = buttons, Row 2 = month title */
            .fc .fc-toolbar {
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: space-between !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 0.75rem !important;
            }
            .fc .fc-toolbar-chunk:nth-child(1) {
                order: 1 !important;
            }
            .fc .fc-toolbar-chunk:nth-child(3) {
                order: 2 !important;
            }
            .fc .fc-toolbar-chunk:nth-child(2) {
                order: 3 !important;
                width: 100% !important;
                text-align: center !important;
                margin-top: 4px !important;
            }
            .fc .fc-toolbar-title {
                font-size: 1rem !important;
                font-weight: 700 !important;
            }
            .fc .fc-button {
                padding: 0.22rem 0.5rem !important;
                font-size: 0.75rem !important;
            }

            /* Grid cells & events */
            .fc-scrollgrid {
                border-left: 0 !important;
                border-right: 0 !important;
                width: 100% !important;
                table-layout: fixed !important;
            }
            .fc-col-header-cell-cushion {
                font-size: 0.72rem !important;
                padding: 4px 1px !important;
                font-weight: 600 !important;
            }
            .fc-daygrid-day-frame {
                min-height: 55px !important;
                padding: 1px !important;
            }
            .fc-daygrid-day-top {
                padding: 1px 2px !important;
            }
            .fc-daygrid-day-number {
                font-size: 0.72rem !important;
                font-weight: 600 !important;
                padding: 1px 2px !important;
            }
            .fc-daygrid-event {
                font-size: 0.65rem !important;
                line-height: 1.15 !important;
                padding: 1px 2px !important;
                margin: 1px 0 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                border-radius: 2px !important;
            }
            .fc-daygrid-event .fc-event-title {
                font-size: 0.65rem !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            .fc-more-link {
                font-size: 0.65rem !important;
                padding: 1px 4px !important;
            }

        }
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
            <div class="card card-height-100 calendar-card">
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
                    <div class="d-flex flex-wrap gap-2 mb-3 justify-content-center calendar-legend">
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

    {{-- Bootstrap 5 Modal untuk Daftar Booking Lengkap (+more) --}}
    <div class="modal fade" id="calendarMoreModal" tabindex="-1" aria-labelledby="calendarMoreModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="modal-title fw-bold fs-14 text-dark mb-0" id="calendarMoreModalTitle">
                        <i class="ri-calendar-event-line me-1 text-primary"></i> Bookings
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="padding: 0.5rem;"></button>
                </div>
                <div class="modal-body p-3" id="calendarMoreModalBody" style="max-height: 65vh; overflow-y: auto;">
                    <!-- List booking dirender secara dinamis di sini -->
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
            const isMobile = window.innerWidth < 768;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                dayMaxEvents: isMobile ? 2 : 4, // Di mobile batasi 2 event per kotak tanggal, sisanya "+X more" modal
                dayMaxEventRows: isMobile ? 3 : 5,
                moreLinkClick: function(info) {
                    if (info.jsEvent) {
                        info.jsEvent.preventDefault();
                        info.jsEvent.stopPropagation();
                    }

                    const modalEl = document.getElementById('calendarMoreModal');
                    const modalTitle = document.getElementById('calendarMoreModalTitle');
                    const modalBody = document.getElementById('calendarMoreModalBody');

                    if (!modalEl || !modalTitle || !modalBody) return false;

                    // Format tanggal judul modal: e.g. "September 10, 2026"
                    const dateFormatted = info.date.toLocaleDateString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    modalTitle.innerHTML = '<i class="ri-calendar-event-line me-1 text-primary"></i> ' + dateFormatted;

                    // Render daftar booking dengan badge warna yang sesuai
                    let html = '';
                    const segs = info.allSegs || [];
                    if (segs.length === 0) {
                        html = '<p class="text-muted text-center py-3 mb-0 fs-12">No bookings on this day.</p>';
                    } else {
                        segs.forEach(function(seg) {
                            const event = seg.event;
                            const classes = (event.classNames || []).join(' ');
                            const url = event.url || 'javascript:void(0);';
                            html += '<a href="' + url + '" class="fc-daygrid-event fc-event d-block mb-2 p-2 rounded text-white text-decoration-none shadow-sm ' + classes + '">' +
                                        '<div class="fc-event-title fw-semibold fs-12">' + event.title + '</div>' +
                                    '</a>';
                        });
                    }
                    modalBody.innerHTML = html;

                    // Tampilkan Bootstrap 5 Modal resmi
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalInstance.show();

                    return false; // Mencegah popover bawaan FullCalendar yang bermasalah di mobile
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                buttonText: {
                    today: 'Today',
                    dayGridMonth: 'Month',
                    timeGridWeek: 'Week',
                    listMonth: 'List'
                },
                dayHeaderFormat: { weekday: 'short' },
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
                },
                windowResize: function(arg) {
                    calendar.updateSize();
                }
            });
            calendar.render();
        });
    </script>
@endsection
