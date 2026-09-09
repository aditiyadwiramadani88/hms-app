@extends('layouts.master')
@section('title') Master Kendaraan Tamu @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container { width: 100% !important; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Master Data @endslot
    @slot('title') Master Kendaraan Tamu @endslot
@endcomponent

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Toolbar --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <form method="GET" class="row g-2">
                    <div class="col-8">
                        <input type="text" name="search" class="form-control" placeholder="Cari plat, pemilik, merk..." value="{{ request('search') }}">
                    </div>
                    <div class="col-4">
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100"><i class="ri-search-line"></i></button>
                    </div>
                </form>
            </div>
            <div class="col-6 col-md-2">
                <select name="vehicle_type" form="filterForm" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    @foreach(['motor'=>'Motor','mobil'=>'Mobil','truck'=>'Truck'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('vehicle_type')===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status_filter" form="filterForm" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status_filter')==='active'?'selected':'' }}>Aktif</option>
                    <option value="inactive" {{ request('status_filter')==='inactive'?'selected':'' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#vehicleModal">
                    <i class="ri-add-line me-1"></i> Tambah Kendaraan
                </button>
            </div>
        </div>
        <form id="filterForm" method="GET">
            <input type="hidden" name="search" value="{{ request('search') }}">
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plat Nomor</th>
                        <th>Tipe</th>
                        <th>Merk</th>
                        <th>Warna</th>
                        <th>Pemilik</th>
                        <th>Tamu</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $v)
                    <tr>
                        <td><strong>{{ $v->plate_number }}</strong></td>
                        <td>{{ $v->vehicle_type }}</td>
                        <td>{{ $v->vehicle_brand ?? '-' }}</td>
                        <td>{{ $v->vehicle_color ?? '-' }}</td>
                        <td>{{ $v->owner_name ?? '-' }}</td>
                        <td>{{ $v->guest?->name ?? '-' }}</td>
                        <td>
                            @if($v->is_active)
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="editVehicle({{ $v->id }})">
                                <i class="ri-edit-line"></i>
                            </button>
                            @if($v->is_active)
                            <form action="{{ route('guest-vehicles.destroy', $v) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline" onsubmit="return confirm('Nonaktifkan kendaraan {{ $v->plate_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger"><i class="ri-close-line"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data kendaraan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $vehicles->links() }}

{{-- Add/Edit Modal --}}
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('guest-vehicles.store') }}" id="vehicleForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="vehicle_id" id="vehicleId">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Kendaraan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                        <input type="text" name="plate_number" id="mPlate" class="form-control" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Kendaraan <span class="text-danger">*</span></label>
                        <select name="vehicle_type" id="mVtype" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="motor">Motor</option>
                            <option value="mobil">Mobil</option>
                            <option value="truck">Truck</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Merk</label>
                            <input type="text" name="vehicle_brand" id="mBrand" class="form-control" maxlength="100">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Warna</label>
                            <input type="text" name="vehicle_color" id="mColor" class="form-control" maxlength="50">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Pemilik</label>
                        <input type="text" name="owner_name" id="mOwner" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tamu</label>
                        <select name="guest_id" id="mGuest" class="form-control select2-modal">
                            <option value="">- Tidak ada -</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" id="mNotes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3 form-check" id="activeField" style="display:none">
                        <input type="checkbox" name="is_active" id="mActive" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="mActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" data-submit-protect="true" class="btn btn-primary" id="submitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$('#mGuest').select2({
    dropdownParent: $('#vehicleModal'),
    placeholder: 'Cari tamu...',
    allowClear: true,
    ajax: {
        url: '{{ route("guests.search") }}',
        dataType: 'json',
        delay: 300,
        data: function (params) { return { q: params.term, limit: 20 }; },
        processResults: function (data) { return { results: data }; },
        cache: true
    },
    minimumInputLength: 2
});

function editVehicle(id) {
    fetch('{{ route("guest-vehicles.show", ":id") }}'.replace(':id', id))
        .then(r => r.json())
        .then(v => {
            document.getElementById('modalTitle').textContent = 'Edit Kendaraan';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('vehicleForm').action = '{{ route("guest-vehicles.update", ":id") }}'.replace(':id', id);
            document.getElementById('mPlate').value = v.plate_number;
            document.getElementById('mVtype').value = v.vehicle_type;
            document.getElementById('mBrand').value = v.vehicle_brand || '';
            document.getElementById('mColor').value = v.vehicle_color || '';
            document.getElementById('mOwner').value = v.owner_name || '';
            if (v.guest_id) document.getElementById('mGuest').value = v.guest_id;
            document.getElementById('mNotes').value = v.notes || '';
            document.getElementById('activeField').style.display = 'block';
            document.getElementById('mActive').checked = v.is_active;
            document.getElementById('submitBtn').textContent = 'Update';
            new bootstrap.Modal(document.getElementById('vehicleModal')).show();
        });
}

document.getElementById('vehicleModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleForm').action = '{{ route("guest-vehicles.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('modalTitle').textContent = 'Tambah Kendaraan';
    document.getElementById('activeField').style.display = 'none';
    document.getElementById('submitBtn').textContent = 'Simpan';
});
</script>
@endsection
