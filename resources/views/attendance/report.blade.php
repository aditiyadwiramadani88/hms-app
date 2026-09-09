@if(request()->ajax())
    @include('attendance._table-content')
    @php return; @endphp
@endif
@extends('layouts.master')
@section('title') Laporan Absensi @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .filter-card .form-label { font-size: 0.8125rem; margin-bottom: 0.25rem; }
    .photo-thumb-modal { max-width: 100%; border-radius: 8px; cursor: pointer; }
    .status-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
    .ajax-loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: none; align-items: center; justify-content: center; z-index: 10; border-radius: 4px; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Laporan @endslot
    @slot('title') Laporan Absensi @endslot
@endcomponent

{{-- Filters --}}
<div class="card filter-card">
    <div class="card-body">
        <form method="GET" action="{{ route('attendance.report.index') }}" id="attendanceFilterForm" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="present" @selected(request('status') == 'present')>Hadir</option>
                    <option value="late" @selected(request('status') == 'late')>Terlambat</option>
                    <option value="early_leave" @selected(request('status') == 'early_leave')>Pulang Awal</option>
                    <option value="late_and_early_leave" @selected(request('status') == 'late_and_early_leave')>Terlambat & Pulang Awal</option>
                    <option value="absent" @selected(request('status') == 'absent')>Absen</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Shift</label>
                <select name="shift_id" class="form-select form-select-sm">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-filter-line"></i></button>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h5 class="card-title mb-0">Data Absensi</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('attendance.report.export', request()->query()) }}" class="btn btn-soft-success btn-sm">
                <i class="ri-file-excel-line me-1"></i> Excel
            </a>
            <a href="{{ route('attendance.report.export.pdf', request()->query()) }}" class="btn btn-soft-danger btn-sm">
                <i class="ri-file-pdf-line me-1"></i> PDF
            </a>
            <a href="{{ route('attendance.report.summary') }}?month={{ request('month', now()->format('m')) }}&year={{ request('year', now()->format('Y')) }}" class="btn btn-soft-info btn-sm">
                <i class="ri-bar-chart-line me-1"></i> Ringkasan
            </a>
        </div>
    </div>
    <div id="attendance-table-container" style="position: relative;">
        @include('attendance._table-content')
    </div>
</div>

{{-- Leave / Cuti Section --}}
@if(isset($leaves) && $leaves->isNotEmpty())
<div class="card mt-3">
    <div class="card-header py-2">
        <h5 class="card-title mb-0 fs-13"><i class="ri-calendar-check-line me-1 text-info"></i> Data Cuti</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-nowrap align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th>Karyawan</th>
                        <th>Tanggal</th>
                        <th>Jenis Cuti</th>
                        <th>Status</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leaves as $leave)
                    <tr>
                        <td>{{ $leave->employee->name ?? '-' }}</td>
                        <td>{{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ $leave->leaveType->name ?? 'Cuti' }}</span></td>
                        <td><span class="badge bg-success-subtle text-success">Approved</span></td>
                        <td>{{ Str::limit($leave->reason, 40) ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <i class="ri-loader-4-line ri-spin fs-2 text-muted"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editLoading" class="text-center py-4 d-none">
                        <i class="ri-loader-4-line ri-spin fs-2 text-muted"></i>
                    </div>
                    <div id="editFormContent">
                        <div class="alert alert-info py-2 px-3 fs-12 mb-3" id="editShiftInfo" style="display:none;">
                            <i class="ri-information-line me-1"></i>
                            <strong>Jadwal Shift:</strong> <span id="editShiftName"></span>
                            <br>
                            <span class="text-dark">Seharusnya Check-in: <strong id="editShiftStart"></strong> | Check-out: <strong id="editShiftEnd"></strong></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Check In Time</label>
                            <input type="datetime-local" name="check_in_time" id="edit_check_in_time" class="form-control">
                            <small class="text-muted" id="editCheckInHint"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Check Out Time</label>
                            <input type="datetime-local" name="check_out_time" id="edit_check_out_time" class="form-control">
                            <small class="text-muted" id="editCheckOutHint"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="present">Hadir</option>
                                <option value="late">Terlambat</option>
                                <option value="early_leave">Pulang Awal</option>
                                <option value="late_and_early_leave">Terlambat & Pulang Awal</option>
                                <option value="absent">Absen</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin (Tambahan)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Masukkan alasan perubahan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-submit-protect="true">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/flatpickr/flatpickr.min.js') }}"></script>
<script>
var csrfToken = '{{ csrf_token() }}';

function showDetail(id) {
    const body = document.getElementById('detailModalBody');
    body.innerHTML = '<div class="text-center py-4"><i class="ri-loader-4-line ri-spin fs-2 text-muted"></i></div>';
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();

    fetch('/admin/attendance/report/' + id, {
        headers: { 'Accept': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(result) {
        if (!result.success) {
            body.innerHTML = '<div class="text-center py-4 text-danger">Gagal memuat detail.</div>';
            return;
        }
        var d = result.data;
        var statusColor = d.status_color || 'secondary';
        var statusIcon = d.status === 'absent' ? 'ri-close-circle-line' : (d.status === 'late' || d.status === 'late_and_early_leave' ? 'ri-alert-line' : 'ri-checkbox-circle-line');

        var html = '<div class="row g-3">';
        html += '<div class="col-md-6">';
        html += '<h6 class="text-muted small text-uppercase mb-2">Check In</h6>';
        if (d.check_in_photo_url) {
            html += '<a href="' + d.check_in_photo_url + '" target="_blank"><img loading="lazy" src="' + d.check_in_photo_url + '" class="photo-thumb-modal mb-2" style="max-height:200px;"></a>';
        } else {
            html += '<div class="text-muted small mb-2">Tidak ada foto</div>';
        }
        html += '<div class="small"><strong>Waktu:</strong> ' + (d.check_in_time || '-') + '</div>';
        if (d.check_in_latitude) html += '<div class="small"><strong>GPS:</strong> ' + d.check_in_latitude + ', ' + d.check_in_longitude + '</div>';
        if (d.check_in_location_name) html += '<div class="small"><strong>Lokasi:</strong> ' + d.check_in_location_name + '</div>';
        html += '</div>';

        html += '<div class="col-md-6">';
        html += '<h6 class="text-muted small text-uppercase mb-2">Check Out</h6>';
        if (d.check_out_photo_url) {
            html += '<a href="' + d.check_out_photo_url + '" target="_blank"><img loading="lazy" src="' + d.check_out_photo_url + '" class="photo-thumb-modal mb-2" style="max-height:200px;"></a>';
        } else {
            html += '<div class="text-muted small mb-2">Tidak ada foto</div>';
        }
        html += '<div class="small"><strong>Waktu:</strong> ' + (d.check_out_time || '-') + '</div>';
        if (d.check_out_latitude) html += '<div class="small"><strong>GPS:</strong> ' + d.check_out_latitude + ', ' + d.check_out_longitude + '</div>';
        if (d.check_out_location_name) html += '<div class="small"><strong>Lokasi:</strong> ' + d.check_out_location_name + '</div>';
        html += '</div>';

        html += '<div class="col-12"><hr class="my-1"></div>';
        html += '<div class="col-md-4"><div class="small"><strong>Karyawan:</strong> ' + d.employee_name + '</div></div>';
        html += '<div class="col-md-3"><div class="small"><strong>Tanggal:</strong> ' + d.attendance_date + '</div></div>';
        html += '<div class="col-md-3"><div class="small"><strong>Shift:</strong> ' + d.shift_name + ' (' + d.shift_time + ')</div></div>';
        html += '<div class="col-md-2"><span class="badge bg-' + statusColor + ' fs-6"><i class="' + statusIcon + ' me-1"></i>' + d.status_label + '</span></div>';

        if (d.break_start_time || d.break_end_time) {
            html += '<div class="col-md-4"><div class="small"><strong><i class="ri-cup-line me-1"></i>Break:</strong> ' + (d.break_start_time || '-') + ' - ' + (d.break_end_time || '...') + '</div></div>';
        }

        if (d.late_minutes > 0) html += '<div class="col-md-3"><div class="small text-warning"><strong>Terlambat:</strong> ' + d.late_minutes + ' menit</div></div>';
        if (d.early_leave_minutes > 0) html += '<div class="col-md-3"><div class="small text-info"><strong>Pulang Awal:</strong> ' + d.early_leave_minutes + ' menit</div></div>';
        if (d.overtime_minutes > 0) html += '<div class="col-md-3"><div class="small text-primary"><strong>Lembur:</strong> ' + d.overtime_minutes + ' menit</div></div>';
        if (d.notes) html += '<div class="col-12"><div class="small"><strong>Catatan:</strong> ' + d.notes + '</div></div>';

        if (d.check_in_latitude && d.check_in_longitude) {
            html += '<div class="col-12 mt-2">';
            html += '<h6 class="text-muted small text-uppercase mb-2">Lokasi pada Peta</h6>';
            html += '<div id="detailMap" style="height: 200px; border-radius: 8px; border: 1px solid #e9ecef;"></div>';
            html += '</div>';
        }
        html += '</div>';
        body.innerHTML = html;

        if (d.check_in_latitude && d.check_in_longitude) {
            setTimeout(function() {
                var map = L.map('detailMap').setView([d.check_in_latitude, d.check_in_longitude], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                L.marker([d.check_in_latitude, d.check_in_longitude]).addTo(map).bindPopup('<strong>Check In</strong><br>' + (d.check_in_time || '')).openPopup();
                if (d.check_out_latitude && d.check_out_longitude) {
                    L.marker([d.check_out_latitude, d.check_out_longitude], {opacity: 0.7}).addTo(map).bindPopup('<strong>Check Out</strong><br>' + (d.check_out_time || ''));
                    map.fitBounds([[d.check_in_latitude, d.check_in_longitude], [d.check_out_latitude, d.check_out_longitude]], {padding: [30, 30]});
                }
            }, 300);
        }
    }).catch(function() {
        body.innerHTML = '<div class="text-center py-4 text-danger">Gagal memuat detail.</div>';
    });
}

function showEdit(id) {
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    const form = document.getElementById('editForm');
    form.action = '/admin/attendance/report/' + id + '/edit';

    document.getElementById('editLoading').classList.remove('d-none');
    document.getElementById('editFormContent').classList.add('d-none');
    modal.show();

    fetch('/admin/attendance/report/' + id, {
        headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(result => {
        if(result.success) {
            document.getElementById('edit_check_in_time').value = result.data.check_in_time || '';
            document.getElementById('edit_check_out_time').value = result.data.check_out_time || '';
            document.getElementById('edit_status').value = result.data.status || 'present';

            var shiftInfo = document.getElementById('editShiftInfo');
            if (result.data.shift_name && result.data.shift_time && result.data.shift_time !== '-') {
                var times = result.data.shift_time.split(' - ');
                document.getElementById('editShiftName').textContent = result.data.shift_name + ' (' + result.data.shift_time + ')';
                document.getElementById('editShiftStart').textContent = times[0] || '-';
                document.getElementById('editShiftEnd').textContent = times[1] || '-';
                document.getElementById('editCheckInHint').textContent = 'Shift start: ' + (times[0] || '-');
                document.getElementById('editCheckOutHint').textContent = 'Shift end: ' + (times[1] || '-');
                shiftInfo.style.display = 'block';
            } else {
                shiftInfo.style.display = 'none';
                document.getElementById('editCheckInHint').textContent = '';
                document.getElementById('editCheckOutHint').textContent = '';
            }

            document.getElementById('editLoading').classList.add('d-none');
            document.getElementById('editFormContent').classList.remove('d-none');
        }
    });
}

function resetCheckout(id) {
    Swal.fire({ title: 'Reset Checkout?', text: 'Jam check out akan dihapus. Lanjutkan?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Reset!', cancelButtonText: 'Batal', customClass: { confirmButton: 'btn btn-danger w-xs me-2 mt-2', cancelButton: 'btn btn-light w-xs mt-2' }, buttonsStyling: false }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin/attendance/report/' + id + '/reset-checkout', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } }).then(r => r.json()).then(res => {
                if(res.success) { Swal.fire('Berhasil', res.message, 'success').then(() => { attendanceAjax.reload(); }); }
                else { Swal.fire('Error', res.message, 'error'); }
            });
        }
    });
}

function fixStatus(id) {
    Swal.fire({ title: 'Perbaiki Status?', text: 'Sistem akan menghitung ulang keterlambatan dan pulang awal. Lanjutkan?', icon: 'info', showCancelButton: true, confirmButtonText: 'Ya, Perbaiki!', cancelButtonText: 'Batal', customClass: { confirmButton: 'btn btn-primary w-xs me-2 mt-2', cancelButton: 'btn btn-light w-xs mt-2' }, buttonsStyling: false }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin/attendance/report/' + id + '/fix-status', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } }).then(r => r.json()).then(res => {
                if(res.success) { Swal.fire('Berhasil', res.message, 'success').then(() => { attendanceAjax.reload(); }); }
                else { Swal.fire('Error', res.message, 'error'); }
            });
        }
    });
}

function deleteAttendance(id) {
    Swal.fire({ title: 'Hapus Data Absensi?', text: 'Data akan dihapus permanen. Karyawan bisa absen ulang. Lanjutkan?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal', customClass: { confirmButton: 'btn btn-danger w-xs me-2 mt-2', cancelButton: 'btn btn-light w-xs mt-2' }, buttonsStyling: false }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin/attendance/report/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } }).then(r => r.json()).then(res => {
                if(res.success) { Swal.fire('Berhasil', res.message, 'success').then(() => { attendanceAjax.reload(); }); }
                else { Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error'); }
            });
        }
    });
}

// AJAX Table Handler
var attendanceAjax = (function() {
    var container = document.getElementById('attendance-table-container');
    var form = document.getElementById('attendanceFilterForm');
    var currentRequest = null;

    function fetchContent(url) {
        if (currentRequest) currentRequest.abort();
        showLoading();
        currentRequest = new AbortController();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: currentRequest.signal })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
            .then(function(html) {
                if (html.indexOf('<!DOCTYPE') > -1 || html.indexOf('<html') > -1) { window.location.href = url; return; }
                container.innerHTML = html;
                hideLoading();
                reinitTooltips();
                history.pushState(null, '', url);
                // Sync export link query strings with current filters
                var currentUrl = new URL(url);
                document.querySelectorAll('.card-header a[href*="export"]').forEach(function(link) {
                    var linkUrl = new URL(link.href);
                    linkUrl.search = currentUrl.search;
                    link.href = linkUrl.toString();
                });
                currentRequest = null;
            })
            .catch(function(err) {
                if (err.name === 'AbortError') return;
                hideLoading();
                currentRequest = null;
                window.location.href = url;
            });
    }

    function showLoading() {
        var overlay = container.querySelector('.ajax-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'ajax-loading-overlay';
            overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            container.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    function hideLoading() {
        var overlay = container.querySelector('.ajax-loading-overlay');
        if (overlay) overlay.style.display = 'none';
    }

    function reinitTooltips() {
        container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            var existing = bootstrap.Tooltip.getInstance(el);
            if (existing) existing.dispose();
            new bootstrap.Tooltip(el);
        });
    }

    function reload() {
        fetchContent(window.location.href);
    }

    // Bind filter form
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var url = new URL(form.action);
            url.search = '';
            new FormData(form).forEach(function(v, k) { if (v) url.searchParams.set(k, v); });
            url.searchParams.delete('page');
            fetchContent(url.toString());
        });
    }

    // Bind pagination (event delegation)
    container.addEventListener('click', function(e) {
        var link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            fetchContent(link.href);
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Popstate
    window.addEventListener('popstate', function() { fetchContent(window.location.href); });

    return { reload: reload, fetch: fetchContent };
})();
</script>
@endsection
