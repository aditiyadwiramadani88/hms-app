@extends('layouts.master')
@section('title')
    Akun Tidak Aktif
@endsection
@section('content')
    <div class="authentication-bg min-vh-100" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card shadow-lg mt-5">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-lg mx-auto mb-4">
                                <span class="avatar-title bg-danger text-white rounded-circle fs-1">
                                    <i class="mdi mdi-lock-alert-outline"></i>
                                </span>
                            </div>
                            <h4 class="text-danger mb-2">Akun Tidak Aktif</h4>
                            <p class="text-muted mb-4">
                                akun tenant Anda saat ini tidak aktif atau kontrak sudah berakhir.
                                Silakan hubungi admin hotel untuk mengaktifkan kembali akun Anda.
                            </p>

                            <div class="card bg-light border-dashed mb-4">
                                <div class="card-body py-3">
                                    <p class="mb-1 text-muted small">Tenant</p>
                                    <p class="fw-semibold mb-0">{{ auth()->user()->tenant->name ?? '-' }}</p>
                                </div>
                            </div>

                            <form action="{{ route('logout') }}" method="POST" data-ajax="true">
                                @csrf
                                <button type="submit" data-submit-protect="true" class="btn btn-danger w-100">
                                    <i class="mdi mdi-logout me-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
