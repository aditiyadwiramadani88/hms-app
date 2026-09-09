<header id="page-topbar" style="{{ current_hotel() && current_hotel()->navbar_color ? 'background-color: ' . current_hotel()->navbar_color . ';' : '' }}">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index" class="logo logo-dark">
                        <span class="logo-sm">
                            <img loading="lazy" src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img loading="lazy" src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="index" class="logo logo-light">
                        <span class="logo-sm">
                            <img loading="lazy" src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img loading="lazy" src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- App Search-->
                @can('bookings.search')
                <form class="app-search d-none d-md-block" action="{{ route('bookings.global-search') }}" method="GET">
                    <div class="position-relative">
                        <input type="text" class="form-control" name="q" placeholder="Search Booking Code or ID..." autocomplete="off" id="search-options-simple" value="">
                        <span class="mdi mdi-magnify search-widget-icon"></span>
                    </div>
                </form>
                @endcan
            </div>

            <div class="d-flex align-items-center">

                <!-- Hotel/Branch Switcher (only show if user has multiple hotels) -->
                @php
                    $availableHotels = auth()->user()->hasRole('Admin') ? \App\Models\Hotel::all() : auth()->user()->hotels;
                    $hasMultipleHotels = $availableHotels->count() > 1;
                @endphp
                
                @if($hasMultipleHotels)
                <div class="dropdown topbar-head-dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class='ri-hotel-line fs-22'></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 280px;">
                        <div class="dropdown-header bg-primary-subtle">
                            <h6 class="text-overflow mb-1 text-uppercase fw-semibold">Switch Branch</h6>
                            <p class="text-muted fs-13 mb-0">{{ current_hotel() ? current_hotel()->name : 'Select Branch' }}</p>
                        </div>
                        <div class="dropdown-divider mt-0"></div>
                        @foreach($availableHotels as $h)
                            <form action="{{ route('branch.switch') }}" method="POST" data-ajax="true" data-ajax-reload="true" class="d-inline">
                                @csrf
                                <input type="hidden" name="hotel_id" value="{{ $h->id }}">
                                <button type="submit" data-submit-protect="true" class="dropdown-item notify-item d-flex align-items-center {{ active_hotel_id() == $h->id ? 'active' : '' }}">
                                    <i class="ri-hotel-line align-middle fs-16 text-muted me-2"></i>
                                    <span class="flex-grow-1">{{ $h->name }}</span>
                                    @if(active_hotel_id() == $h->id)
                                        <i class="ri-check-line text-success ms-2 fs-16"></i>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                        
                        @can('manage system')
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('hotels.index') }}" class="dropdown-item notify-item">
                                <i class="ri-settings-3-line align-middle fs-16 text-muted me-2"></i>
                                <span>Manage All Branches</span>
                            </a>
                        @endcan
                    </div>
                </div>
                @endif

                <div class="dropdown ms-1 topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @switch(Session::get('lang'))
                        @case('id')
                            <img loading="lazy" src="{{ URL::asset('build/images/flags/id.svg') }}" alt="user-image" class="rounded-circle" height="20">
                        @break
                        @default
                            <img loading="lazy" src="{{ URL::asset('build/images/flags/us.svg') }}" alt="user-image" class="rounded-circle" height="20">
                        @endswitch
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <a href="{{ url('index/en') }}" class="dropdown-item notify-item language py-2" data-lang="en" title="English">
                            <img loading="lazy" src="{{ URL::asset('build/images/flags/us.svg') }}" alt="user-image" class="me-2 rounded-circle" height="18">
                            <span class="align-middle">English</span>
                        </a>

                        <!-- item-->
                        <a href="{{ url('index/id') }}" class="dropdown-item notify-item language py-2" data-lang="id" title="Indonesia">
                            <img loading="lazy" src="{{ URL::asset('build/images/flags/id.svg') }}" alt="user-image" class="me-2 rounded-circle" height="18">
                            <span class="align-middle">Indonesia</span>
                        </a>
                    </div>
                </div>

                <div class="dropdown d-md-none topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-search fs-22"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-search-dropdown">
                        <form class="p-3">
                            <div class="form-group m-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                                    <button class="btn btn-primary" type="submit" data-submit-protect="true"><i class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex align-items-center">
                    <div class="px-2 d-flex align-items-center gap-2">
                        <i class="ri-time-line fs-18 text-muted"></i>
                        <span id="liveClock" class="fw-semibold fs-14 text-white">--:--:--</span>
                    </div>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-bell fs-22'></i>
                        @if(($navbarNotifications['totalCount'] ?? 0) > 0)
                        <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">{{ $navbarNotifications['totalCount'] }}<span class="visually-hidden">unread notifications</span></span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">

                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Notifications </h6>
                                    </div>
                                    <div class="col-auto dropdown-tabs">
                                        <span class="badge bg-light-subtle text-body fs-13"> {{ $navbarNotifications['totalCount'] ?? 0 }} New</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-2 pt-2">
                                <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true" id="notificationItemsTab" role="tablist">
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab" aria-selected="true">
                                            All ({{ $navbarNotifications['totalCount'] ?? 0 }})
                                        </a>
                                    </li>
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link" data-bs-toggle="tab" href="#alerts-tab" role="tab" aria-selected="false">
                                            Alerts
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </div>

                        <div class="tab-content position-relative" id="notificationItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    {{-- 1. Unpaid Checkouts --}}
                                    @foreach($navbarNotifications['unpaidDepartures'] ?? [] as $unpaid)
                                        @php
                                            $manualExtraTotal = $unpaid->transactions->where('type', 'charge')->where('status', 'success')->where('reference_id', null)->where('is_deposit', false)->sum('amount');
                                            $posTotal = $unpaid->posOrders->sum('total_amount');
                                            $gTotal = $unpaid->total_price + $manualExtraTotal + $posTotal;
                                            $tPaid = $unpaid->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                                            $rem = $gTotal - $tPaid;
                                        @endphp
                                        <div class="text-reset notification-item d-block dropdown-item position-relative" id="noti-booking-{{ $unpaid->id }}">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-16">
                                                        <i class="ri-money-dollar-circle-line"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('bookings.show', $unpaid->id) }}" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Belum Lunas: Kamar {{ $unpaid->room->room_number ?? 'N/A' }}</h6>
                                                    </a>
                                                    <div class="fs-13 text-muted">
                                                        <p class="mb-1">{{ $unpaid->guest->name ?? 'Tamu' }} sisa tagihan: <b>Rp {{ number_format($rem, 0, ',', '.') }}</b></p>
                                                    </div>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> Checkout Hari Ini</span>
                                                    </p>
                                                </div>
                                                <div class="px-2 fs-15">
                                                    <a href="javascript:void(0);" class="text-muted" onclick="markAsRead('booking', {{ $unpaid->id }}, this)">
                                                        <i class="ri-close-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 2. Check-ins Today --}}
                                    @foreach($navbarNotifications['todayArrivals'] ?? [] as $arrival)
                                        <div class="text-reset notification-item d-block dropdown-item position-relative" id="noti-booking-{{ $arrival->id }}">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                        <i class="ri-login-circle-line"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('bookings.show', $arrival->id) }}" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Check-in: Kamar {{ $arrival->room->room_number ?? 'N/A' }}</h6>
                                                    </a>
                                                    <div class="fs-13 text-muted">
                                                        <p class="mb-1">Tamu: {{ $arrival->guest->name ?? 'Tamu' }} dijadwalkan tiba hari ini.</p>
                                                    </div>
                                                </div>
                                                <div class="px-2 fs-15">
                                                    <a href="javascript:void(0);" class="text-muted" onclick="markAsRead('booking', {{ $arrival->id }}, this)">
                                                        <i class="ri-close-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 3. Dirty Rooms --}}
                                    @foreach($navbarNotifications['dirtyRooms'] ?? [] as $room)
                                        <div class="text-reset notification-item d-block dropdown-item position-relative" id="noti-room-{{ $room->id }}">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-16">
                                                        <i class="ri-brush-line"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('rooms.index') }}" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Kamar Kotor: {{ $room->room_number }}</h6>
                                                    </a>
                                                    <div class="fs-13 text-muted">
                                                        <p class="mb-1">Status: {{ $room->status }}. Butuh pembersihan.</p>
                                                    </div>
                                                </div>
                                                <div class="px-2 fs-15">
                                                    <a href="javascript:void(0);" class="text-muted" onclick="markAsRead('room', {{ $room->id }}, this)">
                                                        <i class="ri-close-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 4. Attendance Reminder --}}
                                    @if($navbarNotifications['needsCheckIn'] ?? false)
                                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-16">
                                                        <i class="ri-fingerprint-line"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('attendance.index') }}" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Anda belum absen hari ini!</h6>
                                                    </a>
                                                    <div class="fs-13 text-muted">
                                                        <p class="mb-1">Silakan lakukan absensi check-in terlebih dahulu.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(($navbarNotifications['totalCount'] ?? 0) == 0)
                                        <div class="text-center pb-5 pt-4">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <img loading="lazy" src="{{URL::asset('build/images/nothing-found.png')}}" class="img-fluid" alt="">
                                            </div>
                                            <div class="pb-3">
                                                <h5 class="fs-18 fw-semibold">No New Notifications</h5>
                                                <p class="text-muted mb-0">You're all caught up!</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if(($navbarNotifications['totalCount'] ?? 0) > 0)
                                    <div class="my-3 text-center view-all">
                                        <a href="{{ route('bookings.index', ['status' => 'checked_in']) }}" class="btn btn-soft-success waves-effect waves-light">View All Active Bookings <i class="ri-arrow-right-line align-middle"></i></a>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="tab-pane fade py-2 ps-2" id="alerts-tab" role="tabpanel" aria-labelledby="alerts-tab">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    @foreach($navbarNotifications['unpaidDepartures'] ?? [] as $unpaid)
                                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-16">
                                                        <i class="ri-error-warning-line"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('bookings.show', $unpaid->id) }}" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Action Required</h6>
                                                    </a>
                                                    <div class="fs-13 text-muted">
                                                        <p class="mb-1">Payment settle needed for Room {{ $unpaid->room->room_number ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="notification-actions" id="notification-actions">
                                <div class="d-flex text-muted justify-content-center">
                                    Select <div id="select-content" class="text-body fw-semibold px-1">0</div> Result <button type="button" class="btn btn-link link-danger p-0 ms-3" data-bs-toggle="modal" data-bs-target="#removeNotificationModal">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img loading="lazy" class="rounded-circle header-profile-user" src="{{ (Auth::user()->avatar && Auth::user()->avatar !== 'avatar-1.jpg') ? asset('storage/avatars/' . Auth::user()->avatar) : asset('build/images/users/avatar-1.jpg') }}" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{Auth::user()->name}}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{Auth::user()->roles->first()->name ?? 'User'}}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome {{ Auth::user()->name }}!</h6>
                        <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="mdi mdi-view-dashboard text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Dashboard</span></a>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">My Profile</span></a>
                        @can('bookings.list')
                        <a class="dropdown-item" href="{{ route('bookings.index') }}"><i class="mdi mdi-calendar-check text-muted fs-16 align-middle me-1"></i> <span class="align-middle">My Bookings</span></a>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void();" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bx bx-power-off font-size-16 align-middle me-1"></i> <span>Logout</span></a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" data-ajax="true" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- removeNotificationModal -->
<div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="NotificationModalbtn-close"></button>
            </div>
            <div class="modal-body">
                <div class="mt-2 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <h4>Are you sure ?</h4>
                        <p class="text-muted mx-4 mb-0">Are you sure you want to remove this Notification ?</p>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn w-sm btn-danger" id="delete-notification">Yes, Delete It!</button>
                </div>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    function markAsRead(type, id, element) {
        // Find the notification item container
        const item = element.closest('.notification-item');
        
        fetch('{{ route('notifications.markAsRead') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type, id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fade out and remove
                item.style.transition = 'opacity 0.3s ease';
                item.style.opacity = '0';
                setTimeout(() => {
                    item.remove();
                    
                    // Update badge count
                    const badges = document.querySelectorAll('.topbar-badge');
                    badges.forEach(badge => {
                        let count = parseInt(badge.textContent) - 1;
                        if (count <= 0) {
                            badge.remove();
                        } else {
                            badge.textContent = count;
                        }
                    });

                    // If no more items, show "Nothing found"
                    const container = document.querySelector('#all-noti-tab [data-simplebar]');
                    if (container && container.querySelectorAll('.notification-item').length === 0) {
                        container.innerHTML = `
                            <div class="text-center pb-5 pt-4">
                                <div class="avatar-lg mx-auto mb-3">
                                    <img loading="lazy" src="{{URL::asset('build/images/nothing-found.png')}}" class="img-fluid" alt="">
                                </div>
                                <div class="pb-3">
                                    <h5 class="fs-18 fw-semibold">No New Notifications</h5>
                                    <p class="text-muted mb-0">You're all caught up!</p>
                                </div>
                            </div>
                        `;
                    }
                }, 300);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateLiveClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        const el = document.getElementById('liveClock');
        if (el) el.textContent = h + ':' + m + ':' + s;
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
</script>
