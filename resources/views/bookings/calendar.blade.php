@extends('layouts.master')
@section('title')
    Room Availability & Operational Calendar
@endsection

@section('css')
    {{-- FullCalendar CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css">

    <style>
        :root {
            --col-date-width: 90px;
        }

        /* FullCalendar Custom Styles (Persis Tampilan Dulu) */
        .fc-event { cursor: pointer; padding: 2px 5px; font-size: 0.78rem; border-radius: 4px; }
        .fc-toolbar-title { font-size: 1.2rem !important; font-weight: bold; }
        .badge-room { font-size: 12px; width: 45px; text-align: center; }
        .fc-event-inhouse { background-color: #7c3aed !important; border-color: #7c3aed !important; color: white !important; }

        /* FullCalendar List View row styling matching screenshot */
        .fc .fc-list-event.fc-event-inhouse td {
            background-color: #7c3aed !important;
            color: #ffffff !important;
            border-color: #6d28d9 !important;
        }
        .fc .fc-list-event.fc-event-inhouse:hover td {
            background-color: #6d28d9 !important;
            color: #ffffff !important;
        }
        .fc .fc-list-event.fc-event-inhouse .fc-list-event-title a,
        .fc .fc-list-event.fc-event-inhouse .fc-list-event-time {
            color: #ffffff !important;
            font-weight: 600;
        }

        .fc .fc-list-day-cushion {
            background-color: #f1f5f9 !important;
            padding: 8px 14px !important;
        }
        .fc .fc-list-day-text {
            color: #3b82f6 !important;
            font-weight: 700 !important;
            font-size: 13px !important;
        }
        .fc .fc-list-day-side-text {
            color: #3b82f6 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
        }

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

        .fc-popover {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
            opacity: 0 !important;
        }

        /* Timeline Matrix Styles */
        .matrix-scroll-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            max-width: 100%;
        }

        .matrix-table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            table-layout: fixed;
            margin-bottom: 0;
            width: max-content;
        }

        .col-room-num { width: 70px; min-width: 70px; max-width: 70px; }
        .col-room-type { width: 105px; min-width: 105px; max-width: 105px; }
        .col-room-status { width: 95px; min-width: 95px; max-width: 95px; }
        .col-date-cell {
            width: var(--col-date-width, 90px);
            min-width: var(--col-date-width, 90px);
            max-width: var(--col-date-width, 90px);
            text-align: center;
        }

        .matrix-table th {
            padding: 6px 4px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #cbd5e1;
            text-align: center;
            background-color: #f8fafc;
            user-select: none;
            box-sizing: border-box;
        }

        .sticky-col-header-group {
            position: sticky;
            left: 0;
            z-index: 32;
            background-color: #f1f5f9 !important;
            border-right: 2px solid #cbd5e1 !important;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.04);
            width: 270px;
        }

        th.col-room-num {
            position: sticky;
            left: 0;
            top: 0;
            z-index: 30;
            background-color: #f1f5f9 !important;
        }
        th.col-room-type {
            position: sticky;
            left: 70px;
            top: 0;
            z-index: 30;
            background-color: #f1f5f9 !important;
        }
        th.col-room-status {
            position: sticky;
            left: 175px;
            top: 0;
            z-index: 30;
            background-color: #f1f5f9 !important;
            border-right: 2px solid #cbd5e1 !important;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
        }

        .matrix-table td {
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 0;
            height: 52px;
            position: relative;
            vertical-align: middle;
            box-sizing: border-box;
        }

        .matrix-table tr.multi-lane-row td {
            vertical-align: top;
        }

        .matrix-table tr.multi-lane-row td.col-room-num,
        .matrix-table tr.multi-lane-row td.col-room-type,
        .matrix-table tr.multi-lane-row td.col-room-status {
            vertical-align: middle;
        }

        .booking-bar.multi-lane-bar {
            padding: 1px 6px;
            border-radius: 4px;
        }

        td.col-room-num {
            position: sticky;
            left: 0;
            z-index: 15;
            background-color: #ffffff;
        }
        td.col-room-type {
            position: sticky;
            left: 70px;
            z-index: 15;
            background-color: #ffffff;
        }
        td.col-room-status {
            position: sticky;
            left: 175px;
            z-index: 15;
            background-color: #ffffff;
            border-right: 2px solid #cbd5e1 !important;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
        }

        .room-item:nth-child(even) td.col-room-num,
        .room-item:nth-child(even) td.col-room-type,
        .room-item:nth-child(even) td.col-room-status {
            background-color: #fcfdfe;
        }

        .room-item:hover td.col-room-num,
        .room-item:hover td.col-room-type,
        .room-item:hover td.col-room-status {
            background-color: #f1f5f9 !important;
        }

        .weekend-col { background-color: #fff9db !important; }
        .today-col {
            background-color: #e0f2fe !important;
            border-left: 2px solid #0284c7 !important;
            border-right: 2px solid #0284c7 !important;
        }
        .today-header {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        .status-indicator-line {
            width: 4px;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
        }
        .status-ready { background-color: #10b981; }
        .status-cleanup { background-color: #f59e0b; }
        .status-dirty { background-color: #ef4444; }
        .status-ooo { background-color: #64748b; }

        .booking-bar {
            position: absolute;
            top: 5px;
            height: 42px;
            border-radius: 6px;
            color: #ffffff !important;
            padding: 3px 7px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-family: inherit;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
            text-decoration: none !important;
            border-left: 3px solid rgba(0,0,0,0.35);
            box-sizing: border-box;
        }

        .booking-bar:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.3);
            filter: brightness(1.06);
            z-index: 25;
        }

        .booking-bar-inhouse   { background-color: #7c3aed; }
        .booking-bar-confirmed { background-color: #0ab39c; }
        .booking-bar-pending,
        .booking-bar-new       { background-color: #f7b84b; }
        .booking-bar-checkout  { background-color: #f06548; }

        .bar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            width: 100%;
            line-height: 1.15;
        }

        .bar-title {
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            flex: 1;
        }

        .bar-status-badge {
            font-size: 8.5px;
            font-weight: 700;
            white-space: nowrap;
            opacity: 0.95;
            flex-shrink: 0;
            text-transform: uppercase;
            background: rgba(0, 0, 0, 0.22);
            padding: 1px 4px;
            border-radius: 3px;
        }

        .bar-subtitle {
            font-size: 9.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: 0.95;
            min-width: 0;
            flex: 1;
        }

        .bar-pay-pill {
            font-size: 8.5px;
            padding: 1px 4px;
            border-radius: 3px;
            background: rgba(0, 0, 0, 0.32);
            font-weight: 700;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .date-cell-empty:hover {
            background-color: #f1f5f9;
            cursor: pointer;
        }

        /* Mobile Responsive Matrix Styles */
        @media (max-width: 768px) {
            :root {
                --col-date-width: 65px;
            }

            .sticky-col-header-group {
                width: 65px !important;
                min-width: 65px !important;
                max-width: 65px !important;
                padding: 6px 2px !important;
                font-size: 11px !important;
                text-align: center !important;
            }

            .col-room-num {
                width: 65px !important;
                min-width: 65px !important;
                max-width: 65px !important;
                padding: 4px 2px !important;
            }

            th.col-room-num,
            td.col-room-num {
                border-right: 2px solid #cbd5e1 !important;
                box-shadow: 2px 0 4px rgba(0, 0, 0, 0.08) !important;
            }

            .col-date-cell {
                width: var(--col-date-width, 65px) !important;
                min-width: var(--col-date-width, 65px) !important;
                max-width: var(--col-date-width, 65px) !important;
            }

            .matrix-table td {
                height: 48px;
            }

            .booking-bar {
                height: 38px;
                padding: 2px 4px;
            }

            .bar-title {
                font-size: 10px;
            }

            .bar-subtitle {
                font-size: 8.5px;
            }

            .timeline-toolbar-wrap {
                gap: 8px !important;
            }
            .timeline-toolbar-wrap > div {
                width: 100%;
                justify-content: space-between;
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
    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-2">
            <div class="card card-animate mb-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Clean (Available)</p>
                            @php $availableRoomsCount = $rooms->whereIn('status', ['Available', 'available']); @endphp
                            <h4 class="mb-0 text-success">{{ $availableRoomsCount->count() }}</h4>
                            <small class="text-muted">{{ $availableRoomsCount->where('is_reserved_today', false)->count() }} ready to book</small>
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
        <div class="col-xl-3 col-md-6 mb-2">
            <div class="card card-animate mb-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Occupied / Check-in</p>
                            <h4 class="mb-0 text-danger">{{ $rooms->whereIn('status', ['In-House', 'Checkin'])->count() }}</h4>
                            <small class="text-muted">{{ $rooms->where('status', 'In-House')->count() }} in-house</small>
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
        <div class="col-xl-3 col-md-6 mb-2">
            <div class="card card-animate mb-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Dirty / Cleaning</p>
                            <h4 class="mb-0 text-warning">{{ $rooms->whereIn('status', ['Checkout', 'Room Refresh', 'dirty'])->count() }}</h4>
                            <small class="text-muted">Housekeeping required</small>
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
        <div class="col-xl-3 col-md-6 mb-2">
            <div class="card card-animate mb-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Maintenance / OOO</p>
                            <h4 class="mb-0 text-dark">{{ $rooms->whereIn('status', ['Out of Order', 'maintenance'])->count() }}</h4>
                            <small class="text-muted">Out of order</small>
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
        <div class="col-12 mt-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                @php
                    $cardCount = $rooms->whereIn('status', ['Available', 'available'])->count()
                        + $rooms->whereIn('status', ['In-House', 'Checkin'])->count()
                        + $rooms->whereIn('status', ['Checkout', 'Room Refresh', 'dirty'])->count()
                        + $rooms->whereIn('status', ['Out of Order', 'maintenance'])->count();
                    $totalRooms = $rooms->count();
                @endphp
                <div class="fs-12 text-muted">
                    <span>Total: <strong>{{ $cardCount }}</strong> / {{ $totalRooms }} rooms</span>
                    @if($cardCount !== $totalRooms)
                        <span class="text-danger ms-2">⚠️ {{ $totalRooms - $cardCount }} uncategorized</span>
                    @else
                        <span class="text-success ms-2">✅ All {{ $totalRooms }} rooms accounted</span>
                    @endif
                </div>
                <div>
                    <a href="{{ route('dashboard') }}" class="btn btn-soft-secondary btn-sm">
                        <i class="ri-dashboard-line align-bottom me-1"></i> Back to Statistics Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Workspace Row: Calendar (Left) + Room Readiness Sidebar (Right) --}}
    <div class="row">
        {{-- Left: The Main Calendar View Container --}}
        <div class="col-xl-9 col-xxl-9" id="timelineMainCol">
            <div class="card border shadow-sm">
                {{-- Card Header: Title & Controls --}}
                <div class="card-header bg-white py-2 py-md-3 border-bottom">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                            <h5 class="card-title mb-0 fw-bold text-dark fs-14 fs-md-16">
                                <i class="ri-calendar-2-line text-primary me-1"></i> Room Occupancy Schedule
                            </h5>

                            {{-- View Switcher: List vs Timeline Matrix --}}
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-primary" id="btnModeList">
                                    <i class="ri-list-check me-1"></i> List
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnModeTimeline">
                                    <i class="ri-table-line me-1"></i> Timeline
                                </button>
                            </div>

                            {{-- Lebar Kolom (Zoom) Controller -- Hanya aktif di mode Timeline --}}
                            <div class="align-items-center gap-1 ms-md-2" id="zoomControlWrapper" style="display: none;">
                                <span class="fs-11 text-muted fw-semibold">Lebar:</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary py-1 px-2 fs-11" id="btnZoomCompact" title="Kolom Rapat (65px)">Rapat</button>
                                    <button type="button" class="btn btn-primary py-1 px-2 fs-11" id="btnZoomNormal" title="Kolom Sedang (90px)">Sedang</button>
                                    <button type="button" class="btn btn-outline-secondary py-1 px-2 fs-11" id="btnZoomWide" title="Kolom Lega (120px)">Lega</button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 ms-auto ms-md-0">
                            {{-- Toggle Sidebar Button --}}
                            <button type="button" class="btn btn-sm btn-outline-secondary d-none d-xl-inline-flex align-items-center" id="btnToggleReadiness" title="Toggle Room Readiness Sidebar">
                                <i class="ri-layout-right-line me-1"></i> <span id="btnToggleReadinessText">Full View</span>
                            </button>
                            @if(!auth()->user()->hasRole('Front Page Only'))
                                <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm">
                                    <i class="ri-add-line align-bottom me-1"></i> New Reservation
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 1. FULLCALENDAR CONTAINER (Month, Week, List persis tampilan dulu) --}}
                <div id="viewFullCalendarContainer" class="p-3">
                    {{-- Legend Persis Tampilan Dulu --}}
                    <div class="calendar-legend d-flex flex-wrap align-items-center justify-content-center gap-3 my-2 fs-12">
                        <span class="d-inline-flex align-items-center"><span class="badge rounded-circle me-1" style="width: 12px; height: 12px; background-color: #7c3aed;">&nbsp;</span> In-House (Checked-In)</span>
                        <span class="d-inline-flex align-items-center"><span class="badge bg-success rounded-circle me-1" style="width: 12px; height: 12px;">&nbsp;</span> Confirmed (Expected)</span>
                    </div>

                    {{-- Target Element FullCalendar --}}
                    <div id="calendar"></div>
                </div>

                {{-- 2. TIMELINE MATRIX CONTAINER (DHTMLX Style Tape Chart) --}}
                <div id="viewTimelineContainer" style="display: none;">
                    {{-- Control Toolbar --}}
                    <div class="card-body bg-light py-2 px-3 border-bottom">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 timeline-toolbar-wrap">
                            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="roomTypeFilter" class="form-label mb-0 fs-12 fw-semibold text-muted text-nowrap">Show rooms:</label>
                                    <select id="roomTypeFilter" class="form-select form-select-sm bg-white" style="width: 160px;">
                                        <option value="all">All ({{ $rooms->count() }})</option>
                                        @foreach($roomTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="mb-0 fw-bold fs-13 fs-md-14 text-dark text-nowrap">
                                        1 {{ $startDate->format('M Y') }} – {{ $daysInMonth }} {{ $startDate->format('M Y') }}
                                    </h5>

                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('bookings.calendar', ['month' => now()->month, 'year' => now()->year]) }}" class="btn btn-outline-secondary px-2 fw-medium" id="btnScrollToday">Today</a>
                                        <a href="{{ route('bookings.calendar', ['month' => $prevDate->month, 'year' => $prevDate->year]) }}" class="btn btn-outline-secondary px-2" title="Previous Month">
                                            <i class="ri-arrow-left-s-line"></i>
                                        </a>
                                        <a href="{{ route('bookings.calendar', ['month' => $nextDate->month, 'year' => $nextDate->year]) }}" class="btn btn-outline-secondary px-2" title="Next Month">
                                            <i class="ri-arrow-right-s-line"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 fs-11 ms-auto">
                                {{-- Booking Status Legend --}}
                                <div class="d-flex align-items-center gap-2 bg-white px-2 py-1 rounded border">
                                    <span class="d-inline-flex align-items-center"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #7c3aed;"></span> In-House</span>
                                    <span class="d-inline-flex align-items-center"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #0ab39c;"></span> Confirmed</span>
                                </div>

                                <span class="text-muted opacity-50 d-none d-sm-inline">|</span>

                                {{-- Room Status Legend (Bentuk Bulat Sempurna) --}}
                                <div class="d-flex align-items-center flex-wrap gap-2 bg-white px-2 py-1 rounded border">
                                    <span class="text-muted fw-semibold" style="font-size: 10.5px;">Room:</span>
                                    <span class="d-inline-flex align-items-center" title="Ready / Clean"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #10b981;"></span> Ready</span>
                                    <span class="d-inline-flex align-items-center" title="Occupied / Clean up"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #f59e0b;"></span> Occupied</span>
                                    <span class="d-inline-flex align-items-center" title="Dirty / Checkout"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #ef4444;"></span> Dirty</span>
                                    <span class="d-inline-flex align-items-center" title="Maintenance / Out of Order"><span class="rounded-circle d-inline-block me-1" style="width: 9px; height: 9px; min-width: 9px; background-color: #64748b;"></span> OOO</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Scrollable Table --}}
                    <div class="matrix-scroll-wrapper" id="matrixScrollContainer">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="sticky-col-header-group d-none d-md-table-cell text-start px-3 py-2 bg-light text-muted">
                                        <i class="ri-hotel-bed-line me-1"></i> ROOMS ({{ $rooms->count() }})
                                    </th>
                                    <th colspan="1" class="sticky-col-header-group d-table-cell d-md-none text-center px-1 py-2 bg-light text-muted" style="width: 65px; min-width: 65px; max-width: 65px;">
                                        <i class="ri-hotel-bed-line"></i>
                                    </th>
                                    <th colspan="{{ $daysInMonth }}" class="py-2 bg-light text-center text-uppercase tracking-wider fw-bold text-dark">
                                        {{ $startDate->format('F Y') }}
                                    </th>
                                </tr>
                                <tr>
                                    <th class="col-room-num">Room</th>
                                    <th class="col-room-type d-none d-md-table-cell">Type</th>
                                    <th class="col-room-status d-none d-md-table-cell">Status</th>

                                    @foreach($days as $dayInfo)
                                        <th class="col-date-cell {{ $dayInfo['is_weekend'] ? 'weekend-col' : '' }} {{ $dayInfo['is_today'] ? 'today-col today-header' : '' }}"
                                            title="{{ $dayInfo['day_name'] }}, {{ $dayInfo['day'] }} {{ $startDate->format('M Y') }}">
                                            <div class="fw-bold fs-12">{{ $dayInfo['day_2digit'] }}</div>
                                            <div style="font-size: 9.5px; opacity: 0.85; font-weight: normal;">{{ substr($dayInfo['day_name'], 0, 2) }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($rooms as $room)
                                    @php
                                        $roomStatus = strtolower($room->status ?? 'available');
                                        $statusClass = 'status-ready';
                                        $statusLabel = 'Ready';

                                        if (in_array($roomStatus, ['available', 'clean', 'ready'])) {
                                            $statusClass = 'status-ready';
                                            $statusLabel = 'Ready';
                                        } elseif (in_array($roomStatus, ['room refresh', 'cleanup', 'clean up'])) {
                                            $statusClass = 'status-cleanup';
                                            $statusLabel = 'Clean up';
                                        } elseif (in_array($roomStatus, ['checkout', 'dirty'])) {
                                            $statusClass = 'status-dirty';
                                            $statusLabel = 'Dirty';
                                        } elseif (in_array($roomStatus, ['in-house', 'checkin', 'occupied'])) {
                                            $statusClass = 'status-cleanup';
                                            $statusLabel = 'Occupied';
                                        } else {
                                            $statusClass = 'status-ooo';
                                            $statusLabel = 'Maintenance';
                                        }

                                        $roomBookings = $bookingsByRoom->get($room->id, collect());

                                        // Multi-lane packing algorithm
                                        $lanes = [];
                                        $bookingLanes = [];

                                        $sortedBookings = $roomBookings->sort(function($a, $b) {
                                            if ($a->check_in === $b->check_in) {
                                                return strtotime($b->check_out) - strtotime($a->check_out);
                                            }
                                            return strtotime($a->check_in) - strtotime($b->check_in);
                                        });

                                        foreach ($sortedBookings as $bk) {
                                            $bkCheckIn = \Carbon\Carbon::parse($bk->check_in);
                                            $bkCheckOut = \Carbon\Carbon::parse($bk->check_out);

                                            $sDay = $bkCheckIn->lt($startDate) ? 1 : (int) $bkCheckIn->format('j');
                                            $eDay = $bkCheckOut->gt($endDate) ? ($daysInMonth + 1) : (int) $bkCheckOut->format('j');

                                            $freeDay = ($eDay > $sDay) ? $eDay : ($sDay + 1);

                                            $assignedLane = -1;
                                            foreach ($lanes as $lIdx => $lEnd) {
                                                if ($sDay >= $lEnd) {
                                                    $assignedLane = $lIdx;
                                                    $lanes[$lIdx] = $freeDay;
                                                    break;
                                                }
                                            }

                                            if ($assignedLane === -1) {
                                                $assignedLane = count($lanes);
                                                $lanes[] = $freeDay;
                                            }

                                            $bookingLanes[$bk->id] = $assignedLane;
                                        }

                                        $totalLanes = max(1, count($lanes));
                                        $rowHeight = $totalLanes > 1 ? (8 + ($totalLanes * 29)) : 52;
                                    @endphp

                                    <tr class="room-item {{ $totalLanes > 1 ? 'multi-lane-row' : '' }}" data-type-id="{{ $room->room_type_id }}" style="height: {{ $rowHeight }}px;">
                                        <td class="col-room-num text-center fw-bold fs-12 text-dark" style="height: {{ $rowHeight }}px;" title="Kamar {{ $room->room_number }}: {{ $statusLabel }} ({{ $room->roomType->name ?? 'Standard' }})">
                                            <div class="status-indicator-line {{ $statusClass }}"></div>
                                            <a href="{{ route('rooms.show', $room->id) }}" class="text-dark text-decoration-none hover-primary ps-1 d-block">
                                                {{ $room->room_number }}
                                            </a>
                                            <div class="d-md-none text-muted text-truncate px-1" style="font-size: 8px; line-height: 1.1; font-weight: normal;" title="{{ $room->roomType->name ?? 'Standard' }}">
                                                {{ Str::limit($room->roomType->name ?? 'Std', 7) }}
                                            </div>
                                        </td>

                                        <td class="col-room-type px-2 text-truncate fs-11 text-muted d-none d-md-table-cell" style="height: {{ $rowHeight }}px;" title="{{ $room->roomType->name ?? 'Standard' }}">
                                            {{ $room->roomType->name ?? 'Standard' }}
                                        </td>

                                        <td class="col-room-status px-1 text-center fs-11 d-none d-md-table-cell" style="height: {{ $rowHeight }}px;">
                                            <span class="badge {{ $statusClass === 'status-ready' ? 'bg-success-subtle text-success' : ($statusClass === 'status-cleanup' ? 'bg-warning-subtle text-warning' : ($statusClass === 'status-dirty' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary')) }}" style="font-size: 10px; padding: 2px 5px;">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>

                                        @foreach($days as $dayIndex => $dayInfo)
                                            <td class="col-date-cell date-cell-empty {{ $dayInfo['is_weekend'] ? 'weekend-col' : '' }} {{ $dayInfo['is_today'] ? 'today-col' : '' }}"
                                                style="height: {{ $rowHeight }}px;"
                                                @if($dayInfo['is_today']) id="col-today" @endif
                                                onclick="openQuickBooking('{{ $room->room_number }}', '{{ $dayInfo['date_string'] }}')"
                                                title="Klik untuk reservasi Kamar {{ $room->room_number }} tanggal {{ $dayInfo['date_string'] }}">

                                                @if($dayIndex === 0)
                                                    @foreach($sortedBookings as $bk)
                                                        @php
                                                            $bkCheckIn = \Carbon\Carbon::parse($bk->check_in);
                                                            $bkCheckOut = \Carbon\Carbon::parse($bk->check_out);

                                                            $startDay = $bkCheckIn->lt($startDate) ? 1 : (int) $bkCheckIn->format('j');
                                                            if ($bkCheckOut->gt($endDate)) {
                                                                $endDay = $daysInMonth + 1;
                                                            } else {
                                                                $endDay = (int) $bkCheckOut->format('j');
                                                            }

                                                            $spanDays = max(1, $endDay - $startDay);
                                                            if ($bkCheckIn->format('Y-m-d') === $bkCheckOut->format('Y-m-d')) {
                                                                $spanDays = 1;
                                                            }

                                                            $laneIdx = $bookingLanes[$bk->id] ?? 0;
                                                            if ($totalLanes > 1) {
                                                                $barTop = 4 + ($laneIdx * 29);
                                                                $barHeight = 26;
                                                            } else {
                                                                $barTop = 5;
                                                                $barHeight = 42;
                                                            }

                                                            $barColorClass = 'booking-bar-pending';
                                                            $statusDisplay = 'Pending';
                                                            if ($bk->status === 'confirmed') {
                                                                $barColorClass = 'booking-bar-confirmed';
                                                                $statusDisplay = 'Confirmed';
                                                            } elseif ($bk->status === 'checked_in') {
                                                                $barColorClass = 'booking-bar-inhouse';
                                                                $statusDisplay = 'In-House';
                                                            } elseif ($bk->status === 'checked_out') {
                                                                $barColorClass = 'booking-bar-checkout';
                                                                $statusDisplay = 'Checked-Out';
                                                            }

                                                            $payStatusText = 'unpaid';
                                                            if (isset($bk->remaining_balance)) {
                                                                if ($bk->remaining_balance <= 0) {
                                                                    $payStatusText = 'paid';
                                                                } elseif ($bk->remaining_balance < $bk->total_price) {
                                                                    $payStatusText = 'partial';
                                                                }
                                                            } else {
                                                                $paidTotal = $bk->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                                                                if ($paidTotal >= $bk->total_price && $bk->total_price > 0) {
                                                                    $payStatusText = 'paid';
                                                                } elseif ($paidTotal > 0) {
                                                                    $payStatusText = 'partial';
                                                                }
                                                            }

                                                            $guestDisplayName = $bk->guest->name ?? ('#' . $bk->id);
                                                            $fullTooltip = "Booking #{$bk->id} | {$guestDisplayName} | " . $bkCheckIn->format('d M') . " - " . $bkCheckOut->format('d M') . " (" . $spanDays . " malam) | Status: " . ucfirst($statusDisplay) . " | Bayar: " . strtoupper($payStatusText);
                                                        @endphp

                                                        <a href="{{ route('bookings.show', $bk->id) }}"
                                                           class="booking-bar {{ $barColorClass }} {{ $totalLanes > 1 ? 'multi-lane-bar' : '' }}"
                                                           style="left: calc((var(--col-date-width, 90px) * {{ $startDay - 1 }}) + 2px); width: calc((var(--col-date-width, 90px) * {{ $spanDays }}) - 4px); top: {{ $barTop }}px; height: {{ $barHeight }}px;"
                                                           title="{{ $fullTooltip }}"
                                                           onclick="event.stopPropagation();">

                                                            @if($totalLanes > 1)
                                                                <div class="d-flex align-items-center justify-content-between gap-1 w-100 h-100">
                                                                    <span class="bar-title text-truncate" style="font-size: 11px;">{{ $guestDisplayName }}</span>
                                                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                                        <span class="bar-subtitle d-none d-lg-inline" style="font-size: 9px; opacity: 0.9;">{{ $bkCheckIn->format('d/m') }}</span>
                                                                        <span class="bar-pay-pill" style="font-size: 8px; padding: 1px 3px;">{{ $payStatusText }}</span>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                @if($spanDays <= 1)
                                                                    <div class="bar-title text-truncate">{{ $guestDisplayName }}</div>
                                                                    <div class="bar-row mt-1">
                                                                        <span class="bar-subtitle">{{ $bkCheckIn->format('d/m') }}</span>
                                                                        <span class="bar-pay-pill">{{ $payStatusText }}</span>
                                                                    </div>
                                                                @elseif($spanDays == 2)
                                                                    <div class="bar-row">
                                                                        <span class="bar-title">{{ $guestDisplayName }}</span>
                                                                        <span class="bar-status-badge">{{ $statusDisplay }}</span>
                                                                    </div>
                                                                    <div class="bar-row mt-1">
                                                                        <span class="bar-subtitle">{{ $bkCheckIn->format('d M') }} - {{ $bkCheckOut->format('d M') }}</span>
                                                                        <span class="bar-pay-pill">{{ $payStatusText }}</span>
                                                                    </div>
                                                                @else
                                                                    <div class="bar-row">
                                                                        <span class="bar-title fs-12">{{ $guestDisplayName }}</span>
                                                                        <span class="bar-status-badge">{{ $statusDisplay }}</span>
                                                                    </div>
                                                                    <div class="bar-row mt-1">
                                                                        <span class="bar-subtitle fs-10">{{ $bkCheckIn->format('d M') }} - {{ $bkCheckOut->format('d M') }} ({{ $spanDays }} malam)</span>
                                                                        <span class="bar-pay-pill">{{ $payStatusText }}</span>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </a>
                                                    @endforeach
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 3 + $daysInMonth }}" class="text-center py-5 text-muted d-none d-md-table-cell">
                                            <i class="ri-hotel-line fs-3 d-block mb-2"></i>
                                            Tidak ada data kamar yang ditemukan.
                                        </td>
                                        <td colspan="{{ 1 + $daysInMonth }}" class="text-center py-5 text-muted d-table-cell d-md-none">
                                            <i class="ri-hotel-line fs-3 d-block mb-2"></i>
                                            Tidak ada data kamar yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center">
                        <span class="fs-12 text-muted">
                            <i class="ri-information-line me-1"></i> Klik pada kotak tanggal kosong untuk reservasi cepat, atau klik pada balok warna untuk detail reservasi.
                        </span>
                        <span class="fs-12 text-muted">
                            Total Kamar: <strong>{{ $rooms->count() }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Custom / Non-Room Bookings Section (If any) --}}
            @if($customBookings->isNotEmpty())
                <div class="card border shadow-sm mt-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="card-title mb-0 fs-13 text-dark">
                            <i class="ri-user-star-line me-1 text-primary"></i> Custom / Non-Room Bookings ({{ $customBookings->count() }})
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-12">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Guest</th>
                                        <th>Custom Name</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customBookings as $cb)
                                        <tr>
                                            <td class="fw-semibold">#{{ $cb->id }}</td>
                                            <td>{{ $cb->guest->name ?? 'Guest' }}</td>
                                            <td>{{ $cb->custom_room_name ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($cb->check_in)->format('d M Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($cb->check_out)->format('d M Y') }}</td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary">{{ ucfirst($cb->status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('bookings.show', $cb->id) }}" class="btn btn-sm btn-soft-primary">
                                                    <i class="ri-eye-line"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: Room Readiness Sidebar Widget --}}
        <div class="col-xl-3 col-xxl-3" id="readinessSidebarCol">
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fs-14">
                        <i class="ri-apps-2-line me-2 text-primary"></i>Room Readiness
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $rooms->count() }} Rooms</span>
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
                                <p class="text-muted fs-12 italic text-center py-2 mb-0">No rooms ready.</p>
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
                                <p class="text-muted fs-12 italic text-center py-2 mb-0">No reserved rooms.</p>
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
                                <p class="text-muted fs-12 italic text-center py-2 mb-0">No active guests in rooms.</p>
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
                                <p class="text-muted fs-12 italic text-center py-2 mb-0">No rooms waiting for check-in.</p>
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
                                <p class="text-muted fs-12 italic text-center py-2 mb-0">No rooms need cleaning.</p>
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
                                    <p class="text-muted fs-12 italic text-center py-2 w-100 mb-0">No rooms in maintenance.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-light border-top-0">
                    <div class="d-grid gap-1">
                        <a href="{{ route('housekeeping.index') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-brush-line me-1"></i> Go to Housekeeping
                        </a>
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

    {{-- Quick Booking Modal --}}
    <div class="modal fade" id="quickBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2 px-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="ri-calendar-check-line text-primary me-1"></i> Reservasi Kamar
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 text-center">
                    <p class="mb-2 fs-13">Buat reservasi baru untuk:</p>
                    <div class="p-3 bg-light rounded mb-3">
                        <div class="fw-bold fs-16 text-primary">Kamar <span id="quickModalRoomNumber"></span></div>
                        <div class="text-muted fs-12 mt-1">Tanggal Check In: <span id="quickModalDate" class="fw-medium text-dark"></span></div>
                    </div>
                    <a href="#" id="quickModalLink" class="btn btn-primary w-100">
                        <i class="ri-add-circle-line me-1"></i> Lanjutkan ke Form Booking
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $fcEvents = $bookings->map(function($b) {
        $colorClass = 'bg-warning border-warning';
        if ($b->status === 'checked_in') {
            $colorClass = 'fc-event-inhouse';
        } elseif ($b->status === 'confirmed') {
            $colorClass = 'bg-success border-success';
        } elseif ($b->status === 'checked_out') {
            $colorClass = 'bg-danger border-danger';
        }

        $roomLabel = $b->is_custom ? '[C]' : ('R-' . ($b->room->room_number ?? '?'));
        $guestLabel = $b->guest->name ?? 'Guest';

        return [
            'id' => (string) $b->id,
            'title' => $roomLabel . ': ' . $guestLabel,
            'start' => \Carbon\Carbon::parse($b->check_in)->format('Y-m-d'),
            'end' => \Carbon\Carbon::parse($b->check_out)->format('Y-m-d'),
            'url' => route('bookings.show', $b->id),
            'className' => $colorClass . ' text-white',
        ];
    });
@endphp

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isMobile = window.innerWidth < 768;

            // 1. Deklarasikan SEMUA elemen DOM di baris teratas (Hanya SATU KALI deklarasi)
            const btnModeTimeline = document.getElementById('btnModeTimeline');
            const btnModeList = document.getElementById('btnModeList');
            const viewTimelineContainer = document.getElementById('viewTimelineContainer');
            const viewFullCalendarContainer = document.getElementById('viewFullCalendarContainer');
            const zoomControlWrapper = document.getElementById('zoomControlWrapper');
            const matrixScrollContainer = document.getElementById('matrixScrollContainer');
            const btnZoomCompact = document.getElementById('btnZoomCompact');
            const btnZoomNormal = document.getElementById('btnZoomNormal');
            const btnZoomWide = document.getElementById('btnZoomWide');
            const btnToggleReadiness = document.getElementById('btnToggleReadiness');
            const timelineMainCol = document.getElementById('timelineMainCol');
            const readinessSidebarCol = document.getElementById('readinessSidebarCol');
            const btnToggleReadinessText = document.getElementById('btnToggleReadinessText');
            const roomTypeFilter = document.getElementById('roomTypeFilter');
            const roomRows = document.querySelectorAll('.room-item');
            const todayCol = document.getElementById('col-today');
            const btnScrollToday = document.getElementById('btnScrollToday');
            const calendarEl = document.getElementById('calendar');

            let calendar = null;

            // 2. Normalisasi nama view (Hanya List dan Timeline)
            function normalizeViewName(viewName) {
                if (viewName === 'timeline') return 'timeline';
                return 'listMonth';
            }

            // 3. Fungsi sinkronisasi tombol switcher
            function syncActiveSwitcher(rawName) {
                const isTimeline = (normalizeViewName(rawName) === 'timeline');
                if (btnModeTimeline) btnModeTimeline.className = isTimeline ? 'btn btn-primary' : 'btn btn-outline-secondary';
                if (btnModeList) btnModeList.className = !isTimeline ? 'btn btn-primary' : 'btn btn-outline-secondary';
            }

            // 4. Fungsi pindah view (Timeline vs List)
            function switchView(rawName) {
                const isTimeline = (normalizeViewName(rawName) === 'timeline');
                if (isTimeline) {
                    if (viewFullCalendarContainer) viewFullCalendarContainer.style.setProperty('display', 'none', 'important');
                    if (viewTimelineContainer) viewTimelineContainer.style.setProperty('display', 'block', 'important');
                    if (zoomControlWrapper) zoomControlWrapper.style.setProperty('display', 'flex', 'important');
                    syncActiveSwitcher('timeline');
                    localStorage.setItem('hms_cal_active_view', 'timeline');
                } else {
                    if (viewTimelineContainer) viewTimelineContainer.style.setProperty('display', 'none', 'important');
                    if (viewFullCalendarContainer) viewFullCalendarContainer.style.setProperty('display', 'block', 'important');
                    if (zoomControlWrapper) zoomControlWrapper.style.setProperty('display', 'none', 'important');

                    if (calendar) {
                        if (!calendar.view || calendar.view.type !== 'listMonth') {
                            calendar.changeView('listMonth');
                        }
                        setTimeout(() => calendar.updateSize(), 50);
                    }
                    syncActiveSwitcher('listMonth');
                    localStorage.setItem('hms_cal_active_view', 'listMonth');
                }
            }

            // 5. Inisialisasi FullCalendar (Mode ListMonth Murni)
            if (calendarEl) {
                const fcEvents = @json($fcEvents);

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'listMonth',
                    dayMaxEvents: isMobile ? 2 : 4,
                    dayMaxEventRows: isMobile ? 3 : 5,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: ''
                    },
                    buttonText: {
                        today: 'Today',
                        listMonth: 'List'
                    },
                    dayHeaderFormat: { weekday: 'short' },
                    themeSystem: 'bootstrap5',
                    events: fcEvents,
                    eventClick: function(info) {
                        if (info.event.url) {
                            window.location.href = info.event.url;
                            info.jsEvent.preventDefault();
                        }
                    },
                    moreLinkClick: function(info) {
                        if (info.jsEvent) {
                            info.jsEvent.preventDefault();
                            info.jsEvent.stopPropagation();
                        }

                        const modalEl = document.getElementById('calendarMoreModal');
                        const modalTitle = document.getElementById('calendarMoreModalTitle');
                        const modalBody = document.getElementById('calendarMoreModalBody');

                        if (!modalEl || !modalTitle || !modalBody) return false;

                        const dateFormatted = info.date.toLocaleDateString('en-US', {
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric'
                        });
                        modalTitle.innerHTML = '<i class="ri-calendar-event-line me-1 text-primary"></i> ' + dateFormatted;

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

                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalInstance.show();

                        return false;
                    },
                    datesSet: function(info) {
                        syncActiveSwitcher(info.view.type);
                    }
                });

                calendar.render();
            }

            // 6. Pasang Event Listener Tombol Switcher
            if (btnModeTimeline) btnModeTimeline.addEventListener('click', () => switchView('timeline'));
            if (btnModeList) btnModeList.addEventListener('click', () => switchView('listMonth'));

            // Aktifkan view yang tersimpan atau default ke listMonth
            const savedActiveView = localStorage.getItem('hms_cal_active_view') || 'listMonth';
            switchView(savedActiveView);

            // 7. Zoom Controller Timeline Matrix
            function setZoom(width) {
                if (matrixScrollContainer) {
                    matrixScrollContainer.style.setProperty('--col-date-width', width + 'px');
                }
                localStorage.setItem('hms_cal_zoom', width);

                if (btnZoomCompact) btnZoomCompact.className = (width <= 70) ? 'btn btn-primary py-1 px-2 fs-11' : 'btn btn-outline-secondary py-1 px-2 fs-11';
                if (btnZoomNormal) btnZoomNormal.className = (width > 70 && width < 110) ? 'btn btn-primary py-1 px-2 fs-11' : 'btn btn-outline-secondary py-1 px-2 fs-11';
                if (btnZoomWide) btnZoomWide.className = (width >= 110) ? 'btn btn-primary py-1 px-2 fs-11' : 'btn btn-outline-secondary py-1 px-2 fs-11';
            }

            if (btnZoomCompact) btnZoomCompact.addEventListener('click', () => setZoom(65));
            if (btnZoomNormal) btnZoomNormal.addEventListener('click', () => setZoom(90));
            if (btnZoomWide) btnZoomWide.addEventListener('click', () => setZoom(120));

            const defaultZoom = isMobile ? '65' : '90';
            const savedZoom = localStorage.getItem('hms_cal_zoom') || defaultZoom;
            setZoom(parseInt(savedZoom));

            // 8. Filter Tipe Kamar secara Real-time pada Timeline Matrix
            if (roomTypeFilter) {
                roomTypeFilter.addEventListener('change', function() {
                    const selectedType = this.value;
                    roomRows.forEach(row => {
                        const typeId = row.getAttribute('data-type-id');
                        if (selectedType === 'all' || typeId === selectedType) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // 9. Auto-scroll ke Tanggal Hari Ini jika di mode timeline
            function scrollToToday() {
                if (todayCol && matrixScrollContainer) {
                    const colLeft = todayCol.offsetLeft;
                    matrixScrollContainer.scrollLeft = Math.max(0, colLeft - 320);
                }
            }

            if (todayCol) {
                setTimeout(scrollToToday, 250);
            }

            if (btnScrollToday) {
                btnScrollToday.addEventListener('click', function(e) {
                    if (todayCol) {
                        e.preventDefault();
                        scrollToToday();
                    }
                });
            }

            // 10. Toggle Room Readiness Sidebar
            if (btnToggleReadiness && timelineMainCol && readinessSidebarCol) {
                btnToggleReadiness.addEventListener('click', function() {
                    const isHidden = readinessSidebarCol.style.display === 'none';
                    if (isHidden) {
                        readinessSidebarCol.style.display = '';
                        timelineMainCol.className = 'col-xl-9 col-xxl-9';
                        if (btnToggleReadinessText) btnToggleReadinessText.textContent = 'Full View';
                    } else {
                        readinessSidebarCol.style.display = 'none';
                        timelineMainCol.className = 'col-12';
                        if (btnToggleReadinessText) btnToggleReadinessText.textContent = 'Split View';
                    }
                    if (calendar) {
                        setTimeout(() => calendar.updateSize(), 50);
                    }
                });
            }
        });

        // 11. Quick Booking Popup saat klik slot tanggal kamar kosong
        function openQuickBooking(roomNumber, dateString) {
            const modalEl = document.getElementById('quickBookingModal');
            const roomNumberEl = document.getElementById('quickModalRoomNumber');
            const dateEl = document.getElementById('quickModalDate');
            const linkEl = document.getElementById('quickModalLink');

            if (!modalEl || !linkEl) return;

            roomNumberEl.textContent = roomNumber;
            dateEl.textContent = dateString;
            linkEl.href = "{{ route('bookings.create') }}?room_number=" + encodeURIComponent(roomNumber) + "&check_in=" + encodeURIComponent(dateString);

            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
    </script>
@endsection
