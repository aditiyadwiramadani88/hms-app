@extends('layouts.master')
@section('title') Jenis Cuti @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') HR & Payroll @endslot
        @slot('title') Jenis Cuti @endslot
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
                            <h5 class="card-title mb-0"><i class="ri-calendar-todo-line me-2 text-primary"></i>Jenis Cuti</h5>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Jenis Cuti
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
                                    <th>Dibayar</th>
                                    <th>Max Hari/Tahun</th>
                                    <th>Urutan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaveTypes as $lt)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-medium">{{ $lt->name }}</td>
                                    <td>
                                        @if($lt->is_paid)
                                            <span class="badge bg-success-subtle text-success">Berbayar</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Tidak Berbayar</span>
                                        @endif
                                    </td>
                                    <td>{{ $lt->max_days_per_year ?? '-' }}</td>
                                    <td>{{ $lt->sort_order }}</td>
                                    <td>
                                        @if($lt->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="javascript:void(0);" class="text-primary d-inline-block"
                                                   onclick="editType({{ $lt->id }}, '{{ addslashes($lt->name) }}', {{ $lt->is_paid ? 1 : 0 }}, {{ $lt->max_days_per_year ?? 'null' }}, {{ $lt->sort_order }}, {{ $lt->is_active ? 1 : 0 }})">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('admin.leave-types.destroy', $lt->id) }}" method="POST" style="display:inline-block;" data-ajax="true" data-ajax-reload="true" data-ajax-confirm="Hapus jenis cuti ini?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" data-submit-protect="true">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada jenis cuti. Tambahkan jenis cuti baru.</td>
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
                <form action="{{ route('admin.leave-types.store') }}" method="POST" data-ajax="true" data-ajax-reload="true">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jenis Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Cuti Tahunan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maks Hari Per Tahun</label>
                            <input type="number" class="form-control" name="max_days_per_year" placeholder="Kosongkan jika tidak terbatas" min="0">
                            <small class="text-muted">Kosongkan jika tidak ada batasan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="sort_order" value="0" min="0">
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_paid" value="1" checked>
                            <label class="form-check-label">Berbayar (mengurangi jatah cuti)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" data-submit-protect="true">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST" data-ajax="true" data-ajax-reload="true">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jenis Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maks Hari Per Tahun</label>
                            <input type="number" class="form-control" name="max_days_per_year" id="editMaxDays" min="0">
                            <small class="text-muted">Kosongkan jika tidak ada batasan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="sort_order" id="editSortOrder" min="0">
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="editIsPaid" value="1">
                            <label class="form-check-label">Berbayar (mengurangi jatah cuti)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" data-submit-protect="true">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
function editType(id, name, isPaid, maxDays, sortOrder, isActive) {
    document.getElementById('editForm').action = '{{ route("admin.leave-types.update", ":id") }}'.replace(':id', id);
    document.getElementById('editName').value = name;
    document.getElementById('editIsPaid').checked = isPaid === 1;
    document.getElementById('editMaxDays').value = maxDays || '';
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editIsActive').checked = isActive === 1;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endsection
