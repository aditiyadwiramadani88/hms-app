<!-- ========== Tenant Sidebar ========== -->
@php
    $tenantHotel = auth()->user()->tenant?->hotel ?? current_hotel();
    $tenantLogoPath = $tenantHotel && $tenantHotel->logo_path
        ? asset('storage/' . $tenantHotel->logo_path)
        : URL::asset('build/images/logo-dark.png');
    $tenantLogoLightPath = $tenantHotel && $tenantHotel->logo_light_path
        ? asset('storage/' . $tenantHotel->logo_light_path)
        : URL::asset('build/images/logo-light.png');
    $tenantLogoSmPath = $tenantHotel && $tenantHotel->logo_path
        ? asset('storage/' . $tenantHotel->logo_path)
        : URL::asset('build/images/logo-sm.png');
    $tenantSidebarColor = $tenantHotel && $tenantHotel->sidebar_color ? $tenantHotel->sidebar_color : null;
@endphp
<div class="app-menu navbar-menu"@if($tenantSidebarColor) style="background-color: {{ $tenantSidebarColor }};"@endif>
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="{{ route('tenant.dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img loading="lazy" src="{{ $tenantLogoSmPath }}" alt="{{ $tenantHotel?->name ?? 'Hotel' }}" height="22" style="max-width: 40px; object-fit: contain;">
            </span>
            <span class="logo-lg">
                <img loading="lazy" src="{{ $tenantLogoPath }}" alt="{{ $tenantHotel?->name ?? 'Hotel' }}" height="40" style="max-width: 180px; object-fit: contain;">
            </span>
        </a>
        <a href="{{ route('tenant.dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img loading="lazy" src="{{ $tenantLogoSmPath }}" alt="" height="22" style="max-width: 40px; object-fit: contain;">
            </span>
            <span class="logo-lg">
                <img loading="lazy" src="{{ $tenantLogoLightPath }}" alt="{{ $tenantHotel?->name ?? 'Hotel' }}" height="40" style="max-width: 180px; object-fit: contain;">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <!-- Tenant Info -->
    <div class="px-4 py-3 border-bottom border-bottom-dashed">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <div class="avatar-sm">
                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-18">
                        <i class="ri-store-2-line"></i>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1 ms-3 text-truncate">
                <h6 class="mb-0 text-truncate">{{ auth()->user()->tenant->name ?? 'Tenant' }}</h6>
                <small class="text-muted">{{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>Menu Tenant</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}" href="{{ route('tenant.dashboard') }}">
                        <i class="ri-dashboard-line"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('tenant.transactions.create') ? 'active' : '' }}" href="{{ route('tenant.transactions.create') }}">
                        <i class="ri-add-circle-line"></i> <span>Buat Transaksi</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('tenant.transactions.*') && !request()->routeIs('tenant.transactions.create') ? 'active' : '' }}" href="{{ route('tenant.transactions.index') }}">
                        <i class="ri-shopping-cart-line"></i> <span>Riwayat Transaksi</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}" href="{{ route('tenant.products.index') }}">
                        <i class="ri-store-line"></i> <span>Produk Saya</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('tenant.billings.*') ? 'active' : '' }}" href="{{ route('tenant.billings.index') }}">
                        <i class="ri-bill-line"></i> <span>Tagihan Sewa</span>
                    </a>
                </li>

                <li class="menu-title mt-3"><span>Akun</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="javascript:void(0);" onclick="document.getElementById('tenant-logout-form').submit();">
                        <i class="ri-logout-box-r-line"></i> <span>Logout</span>
                    </a>
                    <form id="tenant-logout-form" action="{{ route('logout') }}" method="POST" data-ajax="true" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
