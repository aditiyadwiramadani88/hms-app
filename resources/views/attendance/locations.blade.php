@extends('layouts.master')
@section('title') Lokasi Absensi @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 300px; border-radius: 8px; }
    .location-item { transition: background 0.2s; }
    .location-item:hover { background: #f8f9fa; }
    .coords-input { font-family: monospace; font-size: 0.8125rem; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Employee Schedule @endslot
    @slot('title') Lokasi Absensi @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-map-pin-line me-2 text-primary"></i>Daftar Lokasi Absensi</h5>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="resetForm()">
                    <i class="ri-add-line align-bottom me-1"></i> Tambah Lokasi
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Nama</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Radius (m)</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                            <tr class="location-item">
                                <td>{{ $location->name }}</td>
                                <td class="coords-input">{{ $location->latitude }}</td>
                                <td class="coords-input">{{ $location->longitude }}</td>
                                <td>{{ $location->radius_meters }}</td>
                                <td>
                                    @if($location->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-soft-warning btn-sm" onclick="editLocation({{ $location->id }})">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-soft-danger btn-sm" onclick="deleteLocation({{ $location->id }})">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ri-map-pin-off-line fs-3 d-block mb-2"></i>
                                    Belum ada lokasi absensi.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ri-map-line me-1"></i>Peta</h6>
            </div>
            <div class="card-body p-2">
                <div id="map"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Modal --}}
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="locationForm">
                @csrf
                <input type="hidden" name="id" id="formId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Lokasi Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="formName" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Radius (meter) <span class="text-danger">*</span></label>
                            <input type="number" name="radius_meters" id="formRadius" class="form-control" value="100" min="20" max="1000" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" id="formActive" checked>
                                <label class="form-check-label" for="formActive">Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="number" name="latitude" id="formLatitude" class="form-control coords-input" step="any" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="number" name="longitude" id="formLongitude" class="form-control coords-input" step="any" required>
                        </div>
                        <div class="col-12">
                            <div id="formMap" style="height: 250px; border-radius: 8px;"></div>
                            <div class="text-muted small mt-1">Klik pada peta untuk memilih koordinat.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" data-submit-protect="true" class="btn btn-primary" id="formSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map = null;
let marker = null;
let circle = null;
let locationsData = @json($locations);

document.addEventListener('DOMContentLoaded', function() {
    initMap();

    document.getElementById('locationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveLocation();
    });

    document.getElementById('formRadius').addEventListener('input', updateCircle);
});

function initMap(centerLat, centerLng) {
    const lat = centerLat || -8.4095;
    const lng = centerLng || 115.1889;

    if (map) {
        map.setView([lat, lng], 15);
        return;
    }

    map = L.map('map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Show all location markers
    locationsData.forEach(function(loc) {
        if (loc.is_active) {
            L.circle([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
                radius: loc.radius_meters,
                color: '#28a745',
                fillColor: '#28a745',
                fillOpacity: 0.1
            }).addTo(map).bindPopup(loc.name + ' (' + loc.radius_meters + 'm)');
        }
    });
}

function initFormMap(lat, lng, radius) {
    const formMapEl = document.getElementById('formMap');
    if (formMapEl._leaflet_map) {
        formMapEl._leaflet_map.remove();
    }

    const fmap = L.map('formMap').setView([lat || -8.4095, lng || 115.1889], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(fmap);

    formMapEl._leaflet_map = fmap;

    if (marker) { fmap.removeLayer(marker); }
    marker = L.marker([lat || -8.4095, lng || 115.1889], { draggable: true }).addTo(fmap);
    marker.bindPopup('Geser untuk menyesuaikan').openPopup();

    circle = L.circle([lat || -8.4095, lng || 115.1889], {
        radius: radius || 100,
        color: '#0d6efd',
        fillColor: '#0d6efd',
        fillOpacity: 0.1
    }).addTo(fmap);

    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        document.getElementById('formLatitude').value = pos.lat.toFixed(7);
        document.getElementById('formLongitude').value = pos.lng.toFixed(7);
        circle.setLatLng(pos);
    });

    fmap.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('formLatitude').value = e.latlng.lat.toFixed(7);
        document.getElementById('formLongitude').value = e.latlng.lng.toFixed(7);
        circle.setLatLng(e.latlng);
    });

    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        document.getElementById('formLatitude').value = pos.lat.toFixed(7);
        document.getElementById('formLongitude').value = pos.lng.toFixed(7);
        circle.setLatLng(pos);
    });
}

function updateCircle() {
    if (circle) {
        circle.setRadius(parseInt(document.getElementById('formRadius').value) || 100);
    }
}

function resetForm() {
    document.getElementById('formId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formLatitude').value = '';
    document.getElementById('formLongitude').value = '';
    document.getElementById('formRadius').value = '100';
    document.getElementById('formActive').checked = true;
    document.getElementById('modalTitle').textContent = 'Tambah Lokasi Absensi';
    document.getElementById('formSubmitBtn').textContent = 'Simpan';

    setTimeout(function() {
        initFormMap(-8.4095, 115.1889, 100);
    }, 500);
}

function editLocation(id) {
    const loc = locationsData.find(function(l) { return l.id === id; });
    if (!loc) return;

    document.getElementById('formId').value = loc.id;
    document.getElementById('formName').value = loc.name;
    document.getElementById('formLatitude').value = loc.latitude;
    document.getElementById('formLongitude').value = loc.longitude;
    document.getElementById('formRadius').value = loc.radius_meters;
    document.getElementById('formActive').checked = loc.is_active;
    document.getElementById('modalTitle').textContent = 'Edit Lokasi: ' + loc.name;
    document.getElementById('formSubmitBtn').textContent = 'Update';

    var modal = new bootstrap.Modal(document.getElementById('locationModal'));
    modal.show();

    setTimeout(function() {
        initFormMap(parseFloat(loc.latitude), parseFloat(loc.longitude), loc.radius_meters);
    }, 500);
}

function saveLocation() {
    const id = document.getElementById('formId').value;
    const isEdit = !!id;

    const formData = new FormData(document.getElementById('locationForm'));
    if (isEdit) {
        formData.append('_method', 'PUT');
    }

    const url = isEdit ? '/admin/attendance-locations/' + id : '/admin/attendance-locations';

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: new URLSearchParams(formData)
    }).then(function(r) { return r.json(); }).then(function(result) {
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message, timer: 1500, showConfirmButton: false });
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Terjadi kesalahan.' });
        }
    }).catch(function(err) {
        Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Gagal menyimpan data.' });
    });
}

function deleteLocation(id) {
    Swal.fire({
        title: 'Hapus Lokasi?',
        text: 'Data yang sudah dihapus tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            fetch('/admin/attendance-locations/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: '_method=DELETE&_token={{ csrf_token() }}'
            }).then(function(r) { return r.json(); }).then(function(result) {
                if (result.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message, timer: 1500, showConfirmButton: false });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Terjadi kesalahan.' });
                }
            });
        }
    });
}
</script>
@endsection
