@extends('layouts.master')
@section('title')
    System Settings
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .branding-preview {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            margin-bottom: 12px;
        }
        .branding-preview img {
            max-width: 100%;
            max-height: 100px;
            object-fit: contain;
        }
        .branding-preview .placeholder {
            color: #adb5bd;
            font-size: 13px;
        }
        .favicon-preview {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px;
        }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Admin @endslot
        @slot('title') System Settings @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" data-ajax="true" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-hotel-line me-2 text-primary"></i>Hotel Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="hotel_name" class="form-label">Hotel Name</label>
                            <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="{{ $settings['hotel_name'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="tax_percentage" class="form-label">Tax Percentage (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tax_percentage" name="tax_percentage" value="{{ $settings['tax_percentage'] ?? 0 }}" step="0.01" min="0" max="100" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Set to 0 to disable tax calculation in bookings.</small>
                        </div>
                        <div class="mb-3">
                            <label for="room_deposit_amount" class="form-label">Room Deposit Amount (Check-in)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="room_deposit_amount" name="room_deposit_amount" value="{{ $settings['room_deposit_amount'] ?? 0 }}" step="1000" min="0" required>
                            </div>
                            <small class="text-muted">Nominal deposit kamar (jaminan) yang ditawarkan lewat checkbox saat Check In.</small>
                        </div>
                        <div class="text-end">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Hotel Settings</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-settings-3-fill me-2 text-primary"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless table-nowrap align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted fw-medium">Application Name</td>
                                        <td>{{ $settings['app_name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Application URL</td>
                                        <td>{{ $settings['app_url'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Midtrans Environment</td>
                                        <td>
                                            @if(($settings['midtrans_environment'] ?? '') === 'Production')
                                                <span class="badge bg-success-subtle text-success">Production</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Sandbox</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-image-line me-2 text-primary"></i>Branding — Logo</h5>
                    </div>
                    <div class="card-body">
                        <div class="branding-preview" id="logoPreview">
                            @if($settings['logo_path'])
                                <img loading="lazy" src="{{ asset('storage/' . $settings['logo_path']) }}" alt="Current Logo">
                            @else
                                <span class="placeholder"><i class="ri-image-line fs-2 d-block mb-1"></i>No logo uploaded. Will use default.</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/png,image/jpg,image/jpeg,image/svg+xml" id="logoInput">
                            <small class="text-muted">Rekomendasi 200×100 px, rasio 2:1, max 2MB. Format: PNG, JPG, SVG</small>
                        </div>
                        @if($settings['logo_path'])
                            <a href="{{ route('admin.settings.delete-logo') }}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete logo?')">
                                <i class="ri-delete-bin-line me-1"></i> Hapus Logo
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-attachment-line me-2 text-primary"></i>Branding — Favicon</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div>
                                @if($settings['favicon_path'])
                                    <img loading="lazy" src="{{ asset('storage/' . $settings['favicon_path']) }}" alt="Current Favicon" class="favicon-preview">
                                @else
                                    <img loading="lazy" src="{{ URL::asset('build/images/favicon.ico') }}" alt="Default Favicon" class="favicon-preview">
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label">Upload Favicon</label>
                                <input type="file" class="form-control" name="favicon" accept="image/png,image/x-icon,image/svg+xml" id="faviconInput">
                                <small class="text-muted">32×32 px, max 500KB. Format: PNG, ICO, SVG</small>
                            </div>
                        </div>
                        @if($settings['favicon_path'])
                            <a href="{{ route('admin.settings.delete-favicon') }}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete favicon?')">
                                <i class="ri-delete-bin-line me-1"></i> Hapus Favicon
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-image-2-line me-2 text-primary"></i>Branding — Login Background</h5>
                    </div>
                    <div class="card-body">
                        <div class="branding-preview" id="loginBgPreview" style="min-height: 150px; background-size: cover; background-position: center;">
                            @if($settings['login_bg_path'])
                                <img loading="lazy" src="{{ asset('storage/' . $settings['login_bg_path']) }}" alt="Login Background" style="max-height: 150px; object-fit: contain;">
                            @else
                                <span class="placeholder"><i class="ri-image-line fs-2 d-block mb-1"></i>No background uploaded. Will use default.</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Background</label>
                            <input type="file" class="form-control" name="login_bg" accept="image/png,image/jpg,image/jpeg" id="loginBgInput">
                            <small class="text-muted">Max 2MB. Format: PNG, JPG. Akan tampil di halaman login.</small>
                        </div>
                        @if($settings['login_bg_path'])
                            <a href="{{ route('admin.settings.delete-login-bg') }}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete login background?')">
                                <i class="ri-delete-bin-line me-1"></i> Hapus Background
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-palette-line me-2 text-primary"></i>Branding — Theme Colors</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Navbar Color (Top Bar)</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="navbarColor" name="navbar_color" value="{{ $settings['navbar_color'] ?? '#2a3042' }}" style="width: 60px;">
                                <input type="text" class="form-control" id="navbarColorText" value="{{ $settings['navbar_color'] ?? '#2a3042' }}" maxlength="20">
                            </div>
                            <small class="text-muted">Warna background top bar. Default: #2a3042</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sidebar Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="sidebarColor" name="sidebar_color" value="{{ $settings['sidebar_color'] ?? '#2a3042' }}" style="width: 60px;">
                                <input type="text" class="form-control" id="sidebarColorText" value="{{ $settings['sidebar_color'] ?? '#2a3042' }}" maxlength="20">
                            </div>
                            <small class="text-muted">Warna background sidebar. Default: #2a3042</small>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" data-submit-protect="true" class="btn btn-primary btn-lg"><i class="ri-save-line align-bottom me-1"></i> Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.getElementById('logoInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('logoPreview');
                preview.innerHTML = '<img loading="lazy" src="' + ev.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('loginBgInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('loginBgPreview');
                preview.innerHTML = '<img loading="lazy" src="' + ev.target.result + '" alt="Preview" style="max-height: 150px; object-fit: contain;">';
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('faviconInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                const img = document.querySelector('.favicon-preview');
                if (img) img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });

        // Sync color picker ↔ text input
        function syncColorInput(pickerId, textId) {
            const picker = document.getElementById(pickerId);
            const text = document.getElementById(textId);
            if (!picker || !text) return;
            picker.addEventListener('input', function() { text.value = this.value; });
            text.addEventListener('input', function() { picker.value = this.value; });
        }
        syncColorInput('navbarColor', 'navbarColorText');
        syncColorInput('sidebarColor', 'sidebarColorText');
    </script>
@endsection
