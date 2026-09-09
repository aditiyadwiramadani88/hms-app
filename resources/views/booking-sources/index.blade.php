@extends('layouts.master')
@section('title') Sumber Booking @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Master Data @endslot
        @slot('title') Sumber Booking @endslot
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
                            <h5 class="card-title mb-0"><i class="ri-share-forward-line me-2 text-primary"></i>Sumber Booking</h5>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSourceModal">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Sumber
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
                                    <th>Warna Badge</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sources as $source)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $source->color }}-subtle text-{{ $source->color }}">{{ $source->name }}</span>
                                    </td>
                                    <td>{{ ucfirst($source->color) }}</td>
                                    <td>
                                        @if($source->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="javascript:void(0);" class="text-primary d-inline-block"
                                                   onclick="editSource({{ $source->id }}, '{{ $source->name }}', '{{ $source->color }}', {{ $source->is_active ? 1 : 0 }})">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('booking-sources.destroy', $source->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display:inline-block;" onsubmit="return confirm('Hapus sumber ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada sumber booking.</td>
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
    <div class="modal fade" id="addSourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('booking-sources.store') }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Sumber Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Traveloka" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warna Badge</label>
                            <select class="form-select" name="color">
                                <option value="primary">Biru (Primary)</option>
                                <option value="success">Hijau (Success)</option>
                                <option value="danger">Merah (Danger)</option>
                                <option value="warning">Kuning (Warning)</option>
                                <option value="info">Tosca (Info)</option>
                                <option value="secondary" selected>Abu-abu (Secondary)</option>
                                <option value="dark">Hitam (Dark)</option>
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editSourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editSourceForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Sumber Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warna Badge</label>
                            <select class="form-select" name="color" id="editColor">
                                <option value="primary">Biru (Primary)</option>
                                <option value="success">Hijau (Success)</option>
                                <option value="danger">Merah (Danger)</option>
                                <option value="warning">Kuning (Warning)</option>
                                <option value="info">Tosca (Info)</option>
                                <option value="secondary">Abu-abu (Secondary)</option>
                                <option value="dark">Hitam (Dark)</option>
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
function editSource(id, name, color, isActive) {
    document.getElementById('editSourceForm').action = '{{ route("booking-sources.update", ":id") }}'.replace(':id', id);
    document.getElementById('editName').value = name;
    document.getElementById('editColor').value = color;
    document.getElementById('editIsActive').checked = isActive === 1;
    new bootstrap.Modal(document.getElementById('editSourceModal')).show();
}
</script>
@endsection
