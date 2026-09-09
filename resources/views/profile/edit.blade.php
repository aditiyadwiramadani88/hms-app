@extends('layouts.master')
@section('title')
    My Profile
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Account
        @endslot
        @slot('title')
            My Profile
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xxl-3">
            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center">
                        <div class="profile-user position-relative d-inline-block mx-auto mb-4">
                            <img loading="lazy" src="{{ (Auth::user()->avatar && Auth::user()->avatar !== 'avatar-1.jpg') ? asset('storage/avatars/' . Auth::user()->avatar) : asset('build/images/users/avatar-1.jpg') }}" 
                                 class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                <form action="{{ route('profile.update') }}" method="POST" data-ajax="true" enctype="multipart/form-data" id="avatarForm" data-ajax-success="updateAvatarPreview">
                                    @csrf
                                    <input id="profile-img-file-input" type="file" name="avatar" class="profile-img-file-input d-none" onchange="document.getElementById('avatarForm').submit();">
                                    <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                        <span class="avatar-title rounded-circle bg-light text-body" style="cursor: pointer;">
                                            <i class="ri-camera-fill"></i>
                                        </span>
                                    </label>
                                </form>
                            </div>
                        </div>
                        <h5 class="fs-16 mb-1">{{ Auth::user()->name }}</h5>
                        <p class="text-muted mb-0">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xxl-9">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                <i class="ri-user-line me-1 align-bottom"></i> Personal Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#changePassword" role="tab">
                                <i class="ri-lock-password-line me-1 align-bottom"></i> Change Password
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        {{-- Personal Details Tab --}}
                        <div class="tab-pane active" id="personalDetails" role="tabpanel">
                            <form action="{{ route('profile.update') }}" method="POST" data-ajax="true" data-ajax-reload="true">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="firstnameInput" class="form-label">Full Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                   id="firstnameInput" name="name" value="{{ old('name', Auth::user()->name) }}" required>
                                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="emailInput" class="form-label">Email Address</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                   id="emailInput" name="email" value="{{ old('email', Auth::user()->email) }}" required>
                                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="hstack gap-2 justify-content-end">
                                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Info</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Change Password Tab --}}
                        <div class="tab-pane" id="changePassword" role="tabpanel">
                            <form action="{{ route('profile.password') }}" method="POST" data-ajax="true" data-ajax-reload="true">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="oldpasswordInput" class="form-label">Old Password*</label>
                                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                                   name="current_password" id="oldpasswordInput" placeholder="Enter current password" required>
                                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="newpasswordInput" class="form-label">New Password*</label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                   name="password" id="newpasswordInput" placeholder="Enter new password" required>
                                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="confirmpasswordInput" class="form-label">Confirm Password*</label>
                                            <input type="password" class="form-control" name="password_confirmation" 
                                                   id="confirmpasswordInput" placeholder="Confirm new password" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="text-end mt-3">
                                            <button type="submit" data-submit-protect="true" class="btn btn-success">Change Password</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function updateAvatarPreview(result, form) {
            if (result.data && result.data.avatar_url) {
                const profileImg = document.querySelector('.user-profile-image');
                if (profileImg) profileImg.src = result.data.avatar_url + '?t=' + Date.now();

                const headerImg = document.querySelector('.header-profile-user');
                if (headerImg) headerImg.src = result.data.avatar_url + '?t=' + Date.now();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Toastify({ text: "{{ session('success') }}", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            @endif
            @if(session('error'))
                Toastify({ text: "{{ session('error') }}", duration: 3000, gravity: "top", position: "right", style: { background: "#f06548" } }).showToast();
            @endif
        });
    </script>
@endsection
