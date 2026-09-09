@extends('layouts.master')
@section('title')
    Buat Akun Tenant - {{ $tenant->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Tenant @endslot
        @slot('li_2') <a href="{{ route('admin.tenants.show', $tenant->id) }}">{{ $tenant->name }}</a> @endslot
        @slot('title') Buat Akun User @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-add-line me-2 text-primary"></i>Buat Akun untuk {{ $tenant->name }}</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.tenants.users.store', $tenant->id) }}" method="POST" data-ajax="true">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="Nama user tenant" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="email@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" placeholder="Min 8 karakter" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Ulangi password" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Buat Akun
                            </button>
                            <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-soft-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
