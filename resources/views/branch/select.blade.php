@extends('layouts.master-without-nav')
@section('title')
    Select Branch
@endsection
@section('content')
<div class="auth-page-wrapper pt-5">
    <!-- auth page bg -->
    <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
        <div class="bg-overlay"></div>
        <div class="shape">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
            </svg>
        </div>
    </div>
    <div class="auth-page-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    @php
                        $selBranch = current_hotel();
                        $selLogo = $selBranch && $selBranch->logo_light_path
                            ? asset('storage/' . $selBranch->logo_light_path)
                            : ($selBranch && $selBranch->logo_path
                                ? asset('storage/' . $selBranch->logo_path)
                                : URL::asset('build/images/logo-light.png'));
                    @endphp
                    <div class="text-center mt-sm-5 mb-4 text-white-50">
                        <div>
                            <a href="/" class="d-inline-block auth-logo">
                                <img loading="lazy" src="{{ $selLogo }}" alt="{{ $selBranch?->name ?? 'Hotel' }}" height="60" style="max-width: 280px; object-fit: contain;">
                            </a>
                        </div>
                        <p class="mt-3 fs-15 fw-medium">{{ $selBranch?->name ?? 'Hotel Management System' }}</p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-4">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-primary">Welcome Back !</h5>
                                <p class="text-muted">Please select a branch to continue.</p>
                            </div>
                            <div class="p-2 mt-4 text-center">
                                @if(session('error'))
                                    <div class="alert alert-danger mb-4" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <div class="row g-3">
                                    @foreach($hotels as $hotel)
                                        <div class="col-12">
                                            <form action="{{ route('branch.switch') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="hotel_id" value="{{ $hotel->id }}">
                                                <button type="submit" data-submit-protect="true" class="btn btn-outline-primary btn-lg w-100 py-3 d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ri-hotel-line fs-20 me-3"></i>
                                                        <div class="text-start">
                                                            <h6 class="mb-0 fw-bold">{{ $hotel->name }}</h6>
                                                            <small class="text-muted">{{ $hotel->code }}</small>
                                                        </div>
                                                    </div>
                                                    <i class="ri-arrow-right-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <p class="mb-0">Need to logout? <a href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="fw-semibold text-primary text-decoration-underline"> Logout </a> </p>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
