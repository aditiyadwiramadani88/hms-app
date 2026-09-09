<!-- ========== App Menu - Hotel Management System ========== -->
<style>
    .app-menu.navbar-menu {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .app-menu.navbar-menu #scrollbar {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }
</style>
@php
    $hotelLogo = current_hotel();
    $sidebarColor = $hotelLogo && $hotelLogo->sidebar_color ? $hotelLogo->sidebar_color : null;
@endphp
<div class="app-menu navbar-menu"@if($sidebarColor) style="background-color: {{ $sidebarColor }};"@endif>
    <!-- LOGO -->
    @php
        $hotelLogo = current_hotel();
        $logoPath = $hotelLogo && $hotelLogo->logo_path
            ? asset('storage/' . $hotelLogo->logo_path)
            : URL::asset('build/images/logo-dark.png');
        $logoLightPath = $hotelLogo && $hotelLogo->logo_light_path
            ? asset('storage/' . $hotelLogo->logo_light_path)
            : URL::asset('build/images/logo-light.png');
        $logoSrc = $hotelLogo && $hotelLogo->logo_path
            ? asset('storage/' . $hotelLogo->logo_path)
            : URL::asset('build/images/logo-sm.png');
    @endphp
    <div class="navbar-brand-box">
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img loading="lazy" src="{{ $logoSrc }}" alt="{{ $hotelLogo?->name ?? 'Hotel' }}" height="22" style="max-width: 40px; object-fit: contain;">
            </span>
            <span class="logo-lg">
                <img loading="lazy" src="{{ $logoPath }}" alt="{{ $hotelLogo?->name ?? 'Hotel' }}" height="40" style="max-width: 180px; object-fit: contain;">
            </span>
        </a>
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img loading="lazy" src="{{ $logoSrc }}" alt="" height="22" style="max-width: 40px; object-fit: contain;">
            </span>
            <span class="logo-lg">
                <img loading="lazy" src="{{ $logoLightPath }}" alt="{{ $hotelLogo?->name ?? 'Hotel' }}" height="40" style="max-width: 180px; object-fit: contain;">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                <!-- Dashboard -->
                <li class="menu-title"><span data-key="t-menu">{{ __('translation.menu') }}</span></li>
                @can('view reports')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}">
                        <i class="bx bxs-dashboard"></i> <span data-key="t-dashboard">{{ __('translation.dashboards') }}</span>
                    </a>
                </li>
                @endcan

                <!-- FRONT OFFICE -->
                @canany(['manage reservations', 'manage pos', 'view own bookings'])
                <li class="menu-title mt-2"><span><i class="bx bx-buildings me-1"></i>{{ __('translation.front_office') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarFrontOffice" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFrontOffice">
                        <i class="bx bx-buildings"></i> <span>{{ __('translation.front_office') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarFrontOffice">
                        <ul class="nav nav-sm flex-column">
                            @can('bookings.list')
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="#sidebarReservations" data-bs-toggle="collapse" role="button" aria-expanded="false">
                                    <span>{{ __('translation.bookings') }}</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="sidebarReservations">
                                    <ul class="nav nav-sm flex-column ps-3">
                                        @can('bookings.list')
                                        <li class="nav-item">
                                            <a href="{{ route('bookings.index') }}" class="nav-link">{{ __('translation.list') }} {{ __('translation.bookings') }}</a>
                                        </li>
                                        @endcan
                                        @can('bookings.create')
                                        <li class="nav-item">
                                            <a href="{{ route('bookings.create') }}" class="nav-link">{{ __('translation.new') }} {{ __('translation.bookings') }}</a>
                                        </li>
                                        @endcan
                                        @can('bookings.list')
                                        <li class="nav-item">
                                            <a href="{{ route('bookings.calendar') }}" class="nav-link">{{ __('translation.calendar') }}</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                            @if(!auth()->user()->hasRole('Front Page Only'))
                            <li class="nav-item">
                                <a href="{{ route('guests.index') }}" class="nav-link">{{ __('translation.guests') }}</a>
                            </li>
                            @endif
                            @endcan
                            @can('manage pos')
                            @if(!auth()->user()->hasRole('Front Page Only'))
                            <li class="nav-item">
                                <a href="{{ route('pos.index') }}" class="nav-link">{{ __('translation.pos') }}</a>
                            </li>
                            @endif
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                <!-- HOUSEKEEPING -->
                @if(auth()->user()->hasAnyRole(['Housekeeping', 'OB']) || auth()->user()->canAny(['manage housekeeping', 'housekeeping.dashboard', 'housekeeping.my-tasks']))
                <li class="menu-title mt-2"><span><i class="ri-tools-fill me-1"></i>{{ __('translation.operations') }}</span></li>

                {{-- Personal Tasks (for Housekeeping/OB role) --}}
                @if(auth()->user()->hasAnyRole(['Housekeeping', 'OB']) || auth()->user()->can('housekeeping.my-tasks'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('housekeeping.my-tasks') }}">
                        <i class="bx bx-task"></i> <span>My Tasks</span>
                    </a>
                </li>
                @endif

                {{-- Housekeeping Dashboard (Admin/Manager) --}}
                @can('manage housekeeping')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('housekeeping.index') }}">
                        <i class="ri-building-4-line"></i> <span>Housekeeping</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('housekeeping.checker.dashboard') }}">
                        <i class="ri-checkbox-multiple-line"></i> <span>QC / Checker</span>
                    </a>
                </li>
                @endcan

                @endif

                {{-- Attendance & Serah Terima --}}
                @canany(['attendance.check-in', 'attendance.view-own', 'handover.create', 'handover.confirm', 'handover.view-own'])
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAttendanceHandover" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAttendanceHandover">
                        <i class="ri-fingerprint-line"></i> <span>Absensi & Serah Terima</span>
                        @if(isset($pendingHandovers) && $pendingHandovers > 0)
                            <span class="badge bg-danger rounded-circle ms-auto">{{ $pendingHandovers }}</span>
                        @endif
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAttendanceHandover">
                        <ul class="nav nav-sm flex-column">
                            @canany(['attendance.check-in', 'attendance.view-own'])
                            <li class="nav-item">
                                <a href="{{ route('attendance.index') }}" class="nav-link">Absensi</a>
                            </li>
                            @endcanany
                            @canany(['handover.create', 'handover.confirm', 'handover.view-own'])
                            <li class="nav-item">
                                <a href="{{ route('shift-handovers.index') }}" class="nav-link">
                                    Serah Terima
                                    @if(isset($pendingHandovers) && $pendingHandovers > 0)
                                        <span class="badge bg-danger rounded-pill ms-1">{{ $pendingHandovers }}</span>
                                    @endif
                                </a>
                            </li>
                            @endcanany
                            @canany(['handover.manage-templates'])
                            <li class="nav-item">
                                <a href="{{ route('handover-templates.index') }}" class="nav-link">Template Checklist</a>
                            </li>
                            @endcanany
                            @canany(['handover.view-all'])
                            <li class="nav-item">
                                <a href="{{ route('shift-handovers.history') }}" class="nav-link">Riwayat Serah Terima</a>
                            </li>
                            @endcanany
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Security Gate --}}
                @canany(['security.vehicle-gate', 'security.vehicle-log'])
                <li class="menu-title mt-2"><span><i class="ri-shield-check-line me-1"></i>Security</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('security-gate.dashboard') }}">
                        <i class="ri-car-line"></i> <span>Security Gate</span>
                    </a>
                </li>
                @endcanany

                {{-- My Schedule (for all employees) --}}
                @can('schedules.my-schedule')
                @if(!auth()->user()->hasAnyRole(['Housekeeping', 'OB']) && !auth()->user()->canAny(['manage housekeeping', 'housekeeping.dashboard', 'housekeeping.my-tasks']))
                <li class="menu-title mt-2"><span><i class="ri-tools-fill me-1"></i>{{ __('translation.operations') }}</span></li>
                @endif
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('my-schedule.index') }}">
                        <i class="ri-calendar-2-line"></i> <span>My Schedule</span>
                    </a>
                </li>
                @endcan

                {{-- Employee Schedule --}}
                @canany(['schedules.view', 'schedules.create', 'schedules.manage-shifts', 'schedules.manage-locations', 'schedules.approve-swap', 'schedules.my-schedule'])
                @if(!auth()->user()->hasAnyRole(['Housekeeping', 'OB']) && !auth()->user()->canAny(['manage housekeeping', 'housekeeping.dashboard', 'housekeeping.my-tasks']))
                <li class="menu-title mt-2"><span><i class="ri-tools-fill me-1"></i>{{ __('translation.operations') }}</span></li>
                @endif
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarSchedule" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSchedule">
                        <i class="ri-calendar-todo-line"></i> <span>Employee Schedule</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarSchedule">
                        <ul class="nav nav-sm flex-column">
                            @canany(['schedules.view', 'schedules.create'])
                            <li class="nav-item">
                                <a href="{{ route('employee-schedules.index') }}" class="nav-link">Jadwal Bulanan</a>
                            </li>
                            @endcanany
                            @can('schedules.manage-shifts')
                            <li class="nav-item">
                                <a href="{{ route('shifts.index') }}" class="nav-link">Master Shift</a>
                            </li>
                            @endcan
                            @can('schedules.manage-locations')
                            <li class="nav-item">
                                <a href="{{ route('schedule-locations.index') }}" class="nav-link">Master Lokasi</a>
                            </li>
                            @endcan
                            @can('schedules.approve-swap')
                            <li class="nav-item">
                                <a href="{{ route('shift-swaps.index') }}" class="nav-link">Tukar Shift</a>
                            </li>
                            @endcan
                            @can('attendance.manage-locations')
                            <li class="nav-item">
                                <a href="{{ route('attendance-locations.index') }}" class="nav-link">Lokasi Absensi</a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- My Leaves (for all employees with permission) --}}
                @can('leaves.request')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('my-leaves.index') }}">
                        <i class="ri-calendar-event-line"></i> <span>Cuti Saya</span>
                    </a>
                </li>
                @endcan

                <!-- HR & PAYROLL -->
                @can('manage leaves')
                <li class="menu-title mt-2"><span><i class="ri-team-line me-1"></i>HR & Payroll</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarLeaves" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLeaves">
                        <i class="ri-team-line"></i> <span>Cuti</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarLeaves">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.leave-requests.index') }}" class="nav-link">Pengajuan Cuti</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.leave-types.index') }}" class="nav-link">Jenis Cuti</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                <!-- MASTER DATA -->
                @can('manage system')
                <li class="menu-title mt-2"><span><i class="bx bx-cube me-1"></i>{{ __('translation.general') }} Data</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarMaster" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMaster">
                        <i class="bx bx-cube"></i> <span>{{ __('translation.general') }} Data</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarMaster">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="#sidebarRooms" data-bs-toggle="collapse" role="button" aria-expanded="false">
                                    <span>{{ __('translation.rooms') }}</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="sidebarRooms">
                                    <ul class="nav nav-sm flex-column ps-3">
                                        <li class="nav-item">
                                            <a href="{{ route('room-types.index') }}" class="nav-link">{{ __('translation.room_types') }}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('room-statuses.index') }}" class="nav-link">{{ __('translation.room_status') }}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('rooms.index') }}" class="nav-link">{{ __('translation.list') }} {{ __('translation.rooms') }}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('housekeeping.checklist-templates.index') }}" class="nav-link">Checklist Items</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                             <li class="nav-item">
                                <a href="{{ route('employees.index') }}" class="nav-link">{{ __('translation.employees') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('suppliers.index') }}" class="nav-link">{{ __('translation.suppliers') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('guest-categories.index') }}" class="nav-link">{{ __('translation.guests') }} Categories</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('booking-sources.index') }}" class="nav-link">Sumber Booking</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('vouchers.index') }}" class="nav-link">{{ __('translation.vouchers') }}</a>
                            </li>
                            @can('security.manage-vehicles')
                            <li class="nav-item">
                                <a href="{{ route('guest-vehicles.index') }}" class="nav-link">Master Kendaraan</a>
                            </li>
                            @endcan
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="#sidebarPOS" data-bs-toggle="collapse" role="button" aria-expanded="false">
                                    <span>{{ __('translation.pos') }}</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="sidebarPOS">
                                    <ul class="nav nav-sm flex-column ps-3">
                                        @can('manage pos')
                                        <li class="nav-item">
                                            <a href="{{ route('inventory.index') }}" class="nav-link">{{ __('translation.inventory') }}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('inventory.categories.index') }}" class="nav-link">{{ __('translation.list') }} Categories</a>
                                        </li>
                                        @endcan
                                        @can('manage warehouse')
                                        <li class="nav-item">
                                            <a href="{{ route('warehouse.index') }}" class="nav-link">Gudang & Etalase</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('warehouse.transfer.index') }}" class="nav-link">Mutasi Lintas Gudang</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                <!-- PROCUREMENT -->
                @can('manage procurement')
                <li class="menu-title mt-2"><span><i class="ri-shopping-bag-3-line me-1"></i>{{ __('translation.procurement') }}</span></li>
                 <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('purchases.index') }}">
                        <i class="ri-shopping-cart-2-line"></i> <span>{{ __('translation.purchases') }}</span>
                    </a>
                </li>
                @endcan

                <!-- ASSET MANAGEMENT -->
                @can('manage system')
                <li class="menu-title mt-2"><span><i class="ri-archive-line me-1"></i>Asset Management</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAssets" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAssets">
                        <i class="ri-archive-line"></i> <span>Asset Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAssets">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('assets.index') }}" class="nav-link">Daftar Asset</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('asset-categories.index') }}" class="nav-link">Kategori Asset</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                <!-- MAINTENANCE -->
                @canany(['manage system', 'manage maintenance'])
                <li class="nav-item mt-2">
                    <a class="nav-link menu-link" href="{{ route('maintenance.records.index') }}">
                        <i class="ri-tools-fill"></i> <span>Maintenance</span>
                    </a>
                </li>
                @endcanany

                <!-- PAYMENT METHODS -->
                @can('manage system')
                <li class="nav-item mt-2">
                    <a class="nav-link menu-link" href="{{ route('admin.payment-methods.index') }}">
                        <i class="ri-bank-card-line"></i> <span>Metode Pembayaran</span>
                    </a>
                </li>
                @endcan

                <!-- TENANT MANAGEMENT -->
                @canany(['manage tenants', 'manage system', 'manage maintenance'])
                <li class="menu-title mt-2"><span><i class="ri-store-2-line me-1"></i>Tenant</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarTenant" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTenant">
                        <i class="ri-store-2-line"></i> <span>Tenant Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarTenant">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.tenants.index') }}" class="nav-link">Daftar Tenant</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.tenant-billings.index') }}" class="nav-link">Billing Sewa</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.tenant-monitoring.index') }}" class="nav-link">Monitoring Omzet</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcanany

                <!-- FINANCE -->
                @can('view reports')
                <li class="menu-title mt-2"><span><i class="ri-bank-card-2-line me-1"></i>{{ __('translation.finance') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarFinance" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFinance">
                        <i class="ri-bank-card-2-line"></i> <span>{{ __('translation.finance') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarFinance">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('bank-accounts.index') }}" class="nav-link">{{ __('translation.bank_accounts') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('finance.categories.index') }}" class="nav-link">{{ __('translation.finance') }} Categories</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                <!-- REPORTS -->
                @canany(['view reports', 'reports.revenue', 'reports.occupancy', 'reports.transactions', 'reports.bonus', 'reports.analytics'])
                <li class="menu-title mt-2"><span><i class="bx bx-bar-chart-alt-2 me-1"></i>{{ __('translation.reports') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarReports">
                        <i class="bx bx-bar-chart-alt-2"></i> <span>{{ __('translation.reports') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarReports">
                        <ul class="nav nav-sm flex-column">
                            @canany(['view reports', 'reports.revenue'])
                            <li class="nav-item">
                                <a href="{{ route('reports.revenue') }}" class="nav-link">{{ __('translation.revenue_report') }}</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.occupancy'])
                            <li class="nav-item">
                                <a href="{{ route('reports.occupancy') }}" class="nav-link">{{ __('translation.occupancy_report') }}</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.transactions'])
                            <li class="nav-item">
                                <a href="{{ route('reports.transactions') }}" class="nav-link">{{ __('translation.transactions') }}</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.occupancy'])
                            <li class="nav-item">
                                <a href="{{ route('reports.room_activity') }}" class="nav-link">Room Activity</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.bonus'])
                            <li class="nav-item">
                                <a href="{{ route('bonus-reports.index') }}" class="nav-link">Bonus OB</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.kost'])
                            <li class="nav-item">
                                <a href="{{ route('reports.kost') }}" class="nav-link">Laporan Kost</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.kost'])
                            <li class="nav-item">
                                <a href="{{ route('reports.shift') }}" class="nav-link">Laporan Shift</a>
                            </li>
                            @endcanany
                            @canany(['view reports', 'reports.revenue'])
                            <li class="nav-item">
                                <a href="{{ route('reports.monthly') }}" class="nav-link">Laporan Bulanan</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.ob-performance') }}" class="nav-link">Performa OB</a>
                            </li>
                            @endcanany
                            @can('manage housekeeping')
                            <li class="nav-item">
                                <a href="{{ route('reports.housekeeping') }}" class="nav-link">Laporan Housekeeping</a>
                            </li>
                            @endcan
                            @canany(['view reports', 'reports.transactions'])
                            <li class="nav-item">
                                <a href="{{ route('reports.daily') }}" class="nav-link">Laporan Harian</a>
                            </li>
                            @endcanany
                            @can('attendance.view-all')
                            <li class="nav-item">
                                <a href="{{ route('attendance.report.index') }}" class="nav-link">Laporan Absensi</a>
                            </li>
                            @endcan
                            @can('security.vehicle-report')
                            <li class="nav-item">
                                <a href="{{ route('security-gate.report.parking') }}" class="nav-link">Laporan Parkir</a>
                            </li>
                            @endcan
                            @canany(['view reports', 'reports.transactions'])
                            <li class="nav-item">
                                <a href="{{ route('reports.transfer-online') }}" class="nav-link">Laporan Transfer Online</a>
                            </li>
                            @endcanany
                            @canany(['view reports'])
                            <li class="nav-item">
                                <a href="{{ route('reports.bonus-karyawan') }}" class="nav-link">Laporan Bonus Karyawan</a>
                            </li>
                            @endcanany
                        </ul>
                    </div>
                </li>
                @endcanany

                <!-- ADMINISTRATION -->
                @can('manage roles')
                <li class="menu-title mt-2"><span><i class="ri-admin-line me-1"></i>{{ __('translation.administration') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAdmin" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAdmin">
                        <i class="ri-admin-line"></i> <span>{{ __('translation.administration') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAdmin">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.users') }}" class="nav-link">{{ __('translation.users_staff') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.roles') }}" class="nav-link">{{ __('translation.roles_permissions') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.settings') }}" class="nav-link">{{ __('translation.settings') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.website.about.edit') }}" class="nav-link">Website Content</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
