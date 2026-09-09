<!doctype html >
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-topbar="dark" data-sidebar="light" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Hotel Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Hotel Management System" name="description" />
    <meta content="HMS" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    @php
        $faviconHotel = current_hotel();
        $faviconPath = $faviconHotel && $faviconHotel->favicon_path
            ? asset('storage/' . $faviconHotel->favicon_path)
            : URL::asset('build/images/favicon.ico');
    @endphp
    <link rel="shortcut icon" href="{{ $faviconPath }}">
    <link rel="icon" type="image/png" href="{{ $faviconPath }}">
    @include('layouts.head-css')
</head>

@section('body')
    @include('layouts.body')
@show
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @if(auth()->check() && auth()->user()->tenant_id)
            @include('layouts.sidebar-tenant')
        @else
            @include('layouts.sidebar-hms')
        @endif
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @if(session('impersonating_from'))
                    <div class="alert alert-warning alert-dismissible border-0 mb-3 d-flex align-items-center" role="alert">
                        <i class="ri-spy-line fs-4 me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Mode Impersonate:</strong> Anda sedang login sebagai <strong>{{ auth()->user()->name }}</strong>.
                        </div>
                        <form action="{{ route('impersonate.stop') }}" method="POST" class="ms-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="ri-arrow-go-back-line me-1"></i> Kembali ke Admin
                            </button>
                        </form>
                    </div>
                    @endif
                    @include('layouts.partials.toast-container')
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    @include('layouts.customizer')

    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
    @stack('scripts')

    {{-- Force Logout Detection --}}
    @if(isset($force_logout) && $force_logout)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Sesi Berakhir',
                    text: 'Admin telah memaksa logout akun Anda. Silakan login ulang.',
                    icon: 'warning',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        fetch('{{ route("force-logout.reset") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        }).then(function() {
                            window.location.href = '{{ route("login") }}';
                        }).catch(function() {
                            window.location.href = '{{ route("login") }}';
                        });
                    }
                });
            } else {
                alert('Sesi Anda telah berakhir. Silakan login ulang.');
                window.location.href = '{{ route("login") }}';
            }
        });
    </script>
    @endif
</body>

</html>
