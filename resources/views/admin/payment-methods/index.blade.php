@extends('layouts.master')
@section('title') Metode Pembayaran @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Settings @endslot
        @slot('title') Metode Pembayaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-bank-card-line me-2 text-primary"></i>Metode Pembayaran</h5>
                            <p class="text-muted mb-0 mt-1 fs-13">
                                Dipakai sebagai pilihan metode bayar di modul <strong>Tenant</strong> (transaksi penyewa/tenant). Booking &amp; POS hotel punya daftar metode sendiri (Tunai/QRIS/Transfer/dll) dan tidak membaca data di sini.
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Metode
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Kode</th>
                                    <th>Urutan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentMethods as $m)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-medium">{{ $m->name }}</td>
                                    <td><code>{{ $m->code }}</code></td>
                                    <td>{{ $m->sort_order }}</td>
                                    <td>
                                        @if($m->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="javascript:void(0);" class="text-primary d-inline-block"
                                                   onclick="editMethod({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ $m->code }}', {{ $m->sort_order }}, {{ $m->is_active ? 1 : 0 }})">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('admin.payment-methods.destroy', $m->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus metode ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada metode pembayaran. Jalankan seeder.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.payment-methods.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Metode Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Tunai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" placeholder="e.g. cash" required>
                            <small class="text-muted">Kode unik (snake_case), digunakan oleh sistem.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="sort_order" value="0" min="0">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Metode Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" id="editCode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="sort_order" id="editSortOrder" min="0">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
function editMethod(id, name, code, sortOrder, isActive) {
    document.getElementById('editForm').action = '{{ route("admin.payment-methods.update", ":id") }}'.replace(':id', id);
    document.getElementById('editName').value = name;
    document.getElementById('editCode').value = code;
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editIsActive').checked = isActive === 1;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endsection
