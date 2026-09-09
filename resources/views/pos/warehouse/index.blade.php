@extends('layouts.master')
@section('title')
    Gudang & Etalase
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('title')
            Gudang & Etalase
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Gudang / Etalase</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('warehouse.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Gudang</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Etalase Resepsionis">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe</label>
                            <select name="type" class="form-select">
                                <option value="storage">Gudang Penyimpanan</option>
                                <option value="display">Etalase (Display)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" data-submit-protect="true">Tambah</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Gudang</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Gudang</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouses as $wh)
                                <tr>
                                    <td>{{ $wh->name }}</td>
                                    <td>
                                        <span class="badge {{ $wh->type == 'display' ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $wh->type == 'display' ? 'Etalase' : 'Gudang' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($wh->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $wh->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('warehouse.destroy', $wh->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-submit-protect="true" onclick="return confirm('Yakin hapus gudang ini?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editModal{{ $wh->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('warehouse.update', $wh->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Gudang</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Gudang</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $wh->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tipe</label>
                                                        <select name="type" class="form-select">
                                                            <option value="storage" {{ $wh->type == 'storage' ? 'selected' : '' }}>Gudang Penyimpanan</option>
                                                            <option value="display" {{ $wh->type == 'display' ? 'selected' : '' }}>Etalase (Display)</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $wh->id }}" {{ $wh->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="activeSwitch{{ $wh->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary" data-submit-protect="true">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada gudang terdaftar di cabang ini.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    @if(session('success'))
        Toastify({
            text: "{{ session('success') }}",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: { background: "linear-gradient(to right, #0ab39c, #405189)" }
        }).showToast();
    @endif

    @if(session('error'))
        Toastify({
            text: "{{ session('error') }}",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: { background: "linear-gradient(to right, #f06548, #f7b84b)" }
        }).showToast();
    @endif

    @if($errors->any())
        @foreach($errors->all() as $error)
            Toastify({ text: "{{ $error }}", duration: 4000, close: true, gravity: "top", position: "right", stopOnFocus: true, style: { background: "linear-gradient(to right, #f06548, #f7b84b)" } }).showToast();
        @endforeach
    @endif
</script>
@endsection
