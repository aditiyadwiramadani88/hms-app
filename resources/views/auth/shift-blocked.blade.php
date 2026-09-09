@extends('layouts.master-without-nav')
@section('title')
    Akses Ditolak - Bukan Jam Shift
@endsection
@section('content')
<div class="auth-page-wrapper pt-5">
    <!-- auth page bg -->
    @php
        $loginBg = current_hotel();
        $loginBgStyle = $loginBg && $loginBg->login_bg_path
            ? 'background-image: url(' . asset('storage/' . $loginBg->login_bg_path) . ');'
            : '';
    @endphp
    <div class="auth-one-bg-position auth-one-bg" id="auth-particles" style="{{ $loginBgStyle }}">
        <div class="bg-overlay"></div>
        <div class="shape">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
            </svg>
        </div>
    </div>

    <!-- auth page content -->
    <div class="auth-page-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    @php
                        $hotel = current_hotel();
                        $logo = $hotel && $hotel->logo_light_path
                            ? asset('storage/' . $hotel->logo_light_path)
                            : ($hotel && $hotel->logo_path
                                ? asset('storage/' . $hotel->logo_path)
                                : URL::asset('build/images/logo-light.png'));
                    @endphp
                    <div class="text-center mt-sm-5 mb-4 text-white-50">
                        <div>
                            <a href="#" class="d-inline-block auth-logo">
                                <img loading="lazy" src="{{ $logo }}" alt="{{ $hotel?->name ?? 'Hotel' }}" height="60" style="max-width: 280px; object-fit: contain;">
                            </a>
                        </div>
                        <p class="mt-3 fs-15 fw-medium">{{ $hotel?->name ?? 'Hotel Management System' }}</p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-4">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <div class="avatar-lg mx-auto mb-3">
                                    <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-1">
                                        <i class="ri-time-line"></i>
                                    </div>
                                </div>
                                <h5 class="text-danger">Akses Ditolak</h5>
                                <p class="text-muted mb-4">
                                    {{ session('shift_message', 'Anda tidak dapat mengakses sistem di luar jam shift Anda.') }}
                                </p>
                            </div>

                            @if(session('shift_name'))
                            <div class="border rounded p-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted fs-13"><i class="ri-briefcase-line me-1"></i> Shift Anda</span>
                                    <span class="fw-semibold">{{ session('shift_name') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted fs-13"><i class="ri-time-line me-1"></i> Jam Kerja</span>
                                    <span class="fw-semibold">{{ session('shift_time') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted fs-13"><i class="ri-timer-flash-line me-1"></i> Waktu Sekarang</span>
                                    <span class="fw-bold fs-16 text-danger" id="currentTime">{{ now()->format('H:i:s') }}</span>
                                </div>
                            </div>
                            @endif

                            <div class="text-center">
                                <p class="text-muted fs-13 mb-4">Silakan login kembali saat jam shift Anda dimulai.</p>
                                <a href="{{ route('dashboard') }}" class="btn btn-primary w-100 mb-2">
                                    <i class="ri-refresh-line me-1"></i> Coba Lagi
                                </a>
                                <form action="{{ route('logout') }}" method="POST" data-ajax="true">
                                    @csrf
                                    <button type="submit" data-submit-protect="true" class="btn btn-outline-danger w-100">
                                        <i class="ri-logout-box-r-line me-1"></i> Logout
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 text-center">
                                <p class="mb-0 text-muted fs-12">
                                    Login sebagai: <strong>{{ auth()->user()->name ?? '-' }}</strong>
                                </p>
                                <p class="mb-0 text-muted fs-12">
                                    {{ now()->translatedFormat('l, d F Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <p class="mb-0 text-muted">&copy; {{ date('Y') }} {{ $hotel?->name ?? 'HMS' }}. Develop by Fekusa Dev</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
    // Update clock
    setInterval(function() {
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + 
                     now.getMinutes().toString().padStart(2, '0') + ':' + 
                     now.getSeconds().toString().padStart(2, '0');
        const el = document.getElementById('currentTime');
        if (el) el.textContent = time;
    }, 1000);

    // Auto-retry every 30 seconds — if shift has started, redirect to dashboard
    setInterval(function() {
        window.location.href = '{{ route("dashboard") }}';
    }, 30000);
</script>
@endsection
