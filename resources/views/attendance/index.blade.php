@extends('layouts.master')
@section('title') Absensi @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .attendance-card { max-width: 480px; margin: 0 auto; }
    .camera-container { position: relative; width: 100%; padding-top: 75%; background: #000; border-radius: 12px; overflow: hidden; }
    .camera-container video, .camera-container canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
    .camera-container canvas { display: none; }
    .status-indicator { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; border-radius: 50px; font-size: 0.875rem; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .btn-checkin { height: 56px; font-size: 1.125rem; font-weight: 600; border-radius: 50px; width: 100%; }
    .btn-checkin:disabled { opacity: 0.5; }
    .gps-info { font-size: 0.8125rem; color: #6c757d; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    .calendar-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 0.75rem; cursor: pointer; }
    .calendar-day.bg-success { color: #fff; }
    .calendar-day.bg-warning { color: #212529; }
    .calendar-day.bg-danger { color: #fff; }
    .calendar-day.bg-info { color: #fff; }
    .calendar-day.disabled { opacity: 0.3; cursor: default; }
    .calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 4px; }
    .calendar-header div { text-align: center; font-size: 0.7rem; font-weight: 600; color: #6c757d; padding: 4px 0; }
    .summary-stat { text-align: center; padding: 0.75rem 0.5rem; }
    .summary-stat .value { font-size: 1.25rem; font-weight: 700; }
    .summary-stat .label { font-size: 0.6875rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .photo-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 2px solid #e9ecef; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Karyawan @endslot
    @slot('title') Absensi @endslot
@endcomponent

<div class="attendance-card">
    {{-- Shift Info --}}
    @if($schedule && $schedule->shift)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0">
                    <span class="avatar-sm d-flex align-items-center justify-content-center rounded-circle" style="background: {{ $schedule->shift->color }}20; color: {{ $schedule->shift->color }}">
                        <i class="ri-time-line fs-20"></i>
                    </span>
                </div>
                <div>
                    <h6 class="mb-1">{{ $schedule->shift->name }}</h6>
                    <p class="mb-0 text-muted small">
                        <i class="ri-clock-line me-1"></i>
                        {{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') }}
                        @if($schedule->shift->start_time_2 && $schedule->shift->end_time_2)
                        <br><i class="ri-history-line me-1"></i> Shift 2: {{ \Carbon\Carbon::parse($schedule->shift->start_time_2)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->shift->end_time_2)->format('H:i') }}
                        @endif
                        @if($schedule->location)
                        &middot; <i class="ri-map-pin-line me-1"></i>{{ $schedule->location }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card mb-3">
        <div class="card-body text-center py-4">
            <i class="ri-calendar-close-line fs-40 text-muted mb-2 d-block"></i>
            <p class="text-muted mb-0">Anda tidak memiliki jadwal shift hari ini.</p>
        </div>
    </div>
    @endif

    {{-- Status Card --}}
    <div class="card mb-3">
        <div class="card-body text-center">
            @if($todayAttendance)
                @if($todayAttendance->check_in_time && !$todayAttendance->check_out_time)
                    @if($todayAttendance->break_start_time && !$todayAttendance->break_end_time)
                        <span class="status-indicator bg-info-subtle text-info mb-3">
                            <span class="status-dot bg-info"></span> Sedang Istirahat
                        </span>
                        <div class="mt-2 mb-3">
                            <div class="text-muted small">Mulai Istirahat:</div>
                            <div class="fs-3 fw-bold">{{ $todayAttendance->break_start_time->format('H:i') }}</div>
                        </div>
                    @else
                        <span class="status-indicator bg-success-subtle text-success mb-3">
                            <span class="status-dot bg-success"></span> Sudah Check In
                        </span>
                        <div class="mt-2 mb-3">
                            <div class="text-muted small">Check In:</div>
                            <div class="fs-3 fw-bold">{{ $todayAttendance->check_in_time->format('H:i') }}</div>
                            @if($todayAttendance->status === 'late')
                                <span class="badge bg-warning mt-1">Terlambat {{ $todayAttendance->late_minutes }} menit</span>
                            @else
                                <span class="badge bg-success mt-1">Tepat Waktu</span>
                            @endif
                        </div>
                    @endif
                @elseif($todayAttendance->check_out_time)
                    <span class="status-indicator bg-secondary-subtle text-secondary mb-3">
                        <span class="status-dot bg-secondary"></span> Selesai
                    </span>
                    <div class="mt-2 mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted small">Check In</div>
                                <div class="fw-bold">{{ $todayAttendance->check_in_time->format('H:i') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Check Out</div>
                                <div class="fw-bold">{{ $todayAttendance->check_out_time->format('H:i') }}</div>
                            </div>
                        </div>
                        @php
                            $ci = \Carbon\Carbon::parse($todayAttendance->check_in_time);
                            $co = \Carbon\Carbon::parse($todayAttendance->check_out_time);
                            if ($co < $ci) $co->addDay();
                            $totalMinutes = (int) max(0, $ci->diffInMinutes($co));
                            $hours = intdiv($totalMinutes, 60);
                            $mins = $totalMinutes % 60;
                        @endphp
                        <div class="mt-2 text-muted small">Total: {{ $hours }} jam {{ $mins }} menit</div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="doResetCheckout()">
                                <i class="ri-refresh-line me-1"></i> Batal Check Out
                            </button>
                        </div>
                    </div>
                @else
                    <span class="status-indicator bg-secondary-subtle text-secondary mb-3">
                        <span class="status-dot bg-secondary"></span> Tidak ada data
                    </span>
                @endif
            @elseif($schedule && $schedule->shift)
                <span class="status-indicator bg-warning-subtle text-warning mb-3">
                    <span class="status-dot bg-warning"></span> Belum Check In
                </span>
            @endif

            {{-- Camera + GPS + Action Buttons --}}
            @if($schedule && $schedule->shift && (!$todayAttendance || ($todayAttendance->check_in_time && !$todayAttendance->check_out_time)))
            <div class="mt-3">
                <div class="camera-container mb-3" id="cameraContainer">
                    <video id="cameraPreview" autoplay playsinline></video>
                    <canvas id="photoCanvas"></canvas>
                </div>

                <div id="gpsStatus" class="gps-info mb-3">
                    <i class="ri-map-pin-line me-1"></i> Mencari lokasi...
                </div>

                <div id="photoPreview" class="mb-3" style="display:none;">
                    <img loading="lazy" id="capturedPhoto" class="photo-thumb" alt="Preview">
                    <button type="button" class="btn btn-sm btn-soft-secondary ms-2" onclick="retakePhoto()">
                        <i class="ri-refresh-line"></i> Ulang
                    </button>
                </div>

                @if(!$todayAttendance)
                <button type="button" class="btn btn-success btn-checkin" id="btnCheckIn" disabled onclick="doCheckIn()">
                    <i class="ri-login-circle-line me-2"></i> Check In
                </button>
                @endif

                @if($todayAttendance && $todayAttendance->check_in_time && !$todayAttendance->check_out_time)
                    @php $isSplit = $schedule->shift->start_time_2 && $schedule->shift->end_time_2; @endphp
                    @if(!$todayAttendance->break_start_time)
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-warning btn-checkin" id="btnBreakStart" onclick="doBreakStart()">
                                <i class="ri-cup-line me-2"></i> {{ $isSplit ? 'Mulai Jeda Shift' : 'Mulai Istirahat' }}
                            </button>
                            <button type="button" class="btn btn-primary btn-checkin" id="btnCheckOut" onclick="doCheckOut()">
                                <i class="ri-logout-circle-line me-2"></i> Check Out
                            </button>
                        </div>
                    @elseif($todayAttendance->break_start_time && !$todayAttendance->break_end_time)
                        <button type="button" class="btn btn-info btn-checkin" id="btnBreakEnd" onclick="doBreakEnd()">
                            <i class="ri-walk-line me-2"></i> {{ $isSplit ? 'Mulai Shift 2' : 'Selesai Istirahat' }}
                        </button>
                    @else
                        <button type="button" class="btn btn-primary btn-checkin" id="btnCheckOut" onclick="doCheckOut()">
                            <i class="ri-logout-circle-line me-2"></i> Check Out
                        </button>
                    @endif
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Monthly Calendar --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h6 class="card-title mb-0"><i class="ri-calendar-line me-1"></i>Riwayat Bulan Ini</h6>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-soft-primary" onclick="changeMonth(-1)"><i class="ri-arrow-left-s-line"></i></button>
                <span id="monthLabel" class="fw-medium small">{{ \Carbon\Carbon::create(null, $currentMonth, 1)->format('F Y') }}</span>
                <button class="btn btn-sm btn-soft-primary" onclick="changeMonth(1)"><i class="ri-arrow-right-s-line"></i></button>
            </div>
        </div>
        <div class="card-body" id="calendarBody">
            @include('attendance.partials.calendar', ['attendances' => $monthlyAttendances, 'month' => $currentMonth, 'year' => $currentYear, 'summary' => $summary])
        </div>
    </div>

    {{-- Legend --}}
    <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
        <span><span class="status-dot bg-success me-1"></span> Hadir</span>
        <span><span class="status-dot bg-warning me-1"></span> Terlambat</span>
        <span><span class="status-dot bg-danger me-1"></span> Absen</span>
        <span><span class="status-dot bg-info me-1"></span> Overtime</span>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title">Detail Absensi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-3">
                    <i class="ri-loader-4-line ri-spin fs-3"></i>
                    <p class="text-muted mt-2">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="attendanceForm" method="POST" data-ajax="true" style="display:none;">
    @csrf
    <input type="hidden" name="photo" id="inputPhoto">
    <input type="hidden" name="latitude" id="inputLatitude">
    <input type="hidden" name="longitude" id="inputLongitude">
</form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
let mediaStream = null;
let currentPosition = null;
let capturedPhotoData = null;
let cameraReady = false;
let gpsReady = false;
let currentMonth = {{ $currentMonth }};
let currentYear = {{ $currentYear }};
const activeLocations = @json($activeLocations);

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function findNearestLocation(lat, lng) {
    if (!activeLocations || activeLocations.length === 0) return null;

    let nearest = null;
    let nearestDist = Infinity;

    activeLocations.forEach(function(loc) {
        const dist = calculateDistance(lat, lng, parseFloat(loc.latitude), parseFloat(loc.longitude));
        if (dist < nearestDist) {
            nearestDist = dist;
            nearest = loc;
        }
    });

    return { location: nearest, distance: Math.round(nearestDist) };
}

function updateGPSStatus(type, message, extra) {
    const el = document.getElementById('gpsStatus');
    if (!el) return;
    const icons = { success: 'ri-checkbox-circle-line', warning: 'ri-alert-line', error: 'ri-close-circle-line' };
    const colors = { success: '#28a745', warning: '#ffc107', error: '#dc3545' };
    let html = '<i class="' + (icons[type] || 'ri-map-pin-line') + '" style="color:' + (colors[type] || '#6c757d') + '"></i> ' + message;
    if (extra) html += '<br><small>' + extra + '</small>';
    el.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    initCamera();
    initGPS();
});

function initCamera() {
    const video = document.getElementById('cameraPreview');
    if (!video) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showAlert('error', 'Kamera tidak tersedia', 'Perangkat ini tidak mendukung akses kamera.');
        return;
    }

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
        audio: false
    }).then(function(stream) {
        mediaStream = stream;
        video.srcObject = stream;
        video.onloadedmetadata = function() {
            video.play();
            cameraReady = true;
            checkReady();
        };
    }).catch(function(err) {
        console.error('Camera error:', err);
        showAlert('error', 'Izin Kamera Diperlukan', 'Akses kamera diperlukan untuk melakukan absensi.');
    });
}

function initGPS() {
    if (!navigator.geolocation) {
        updateGPSStatus('error', 'GPS tidak tersedia di perangkat ini.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            currentPosition = position;
            const accuracy = Math.round(position.coords.accuracy);
            const nearest = findNearestLocation(position.coords.latitude, position.coords.longitude);

            let distanceMsg = '';
            let warningMsg = '';

            if (nearest && activeLocations.length > 0) {
                distanceMsg = nearest.distance + 'm dari ' + nearest.location.name;
                if (nearest.distance > nearest.location.radius_meters) {
                    warningMsg = 'Anda di luar area absensi (' + nearest.distance + 'm). Maksimal ' + nearest.location.radius_meters + 'm.';
                }
            }

            if (position.coords.accuracy > 150) {
                updateGPSStatus('warning', 'Akurasi GPS sangat rendah: ' + accuracy + 'm. Coba di area terbuka.', distanceMsg || null);
            } else if (position.coords.accuracy > 50) {
                updateGPSStatus('success', 'Lokasi ditemukan (akurasi: ' + accuracy + 'm) ⚠️', distanceMsg || null);
                gpsReady = true;
                checkReady();
            } else {
                updateGPSStatus('success', 'Lokasi ditemukan (akurasi: ' + accuracy + 'm)', distanceMsg || null);
                gpsReady = true;
                checkReady();
            }

            if (warningMsg) {
                setTimeout(function() {
                    const el = document.getElementById('gpsStatus');
                    if (el) el.innerHTML += '<br><span class="text-danger"><i class="ri-alert-fill me-1"></i>' + warningMsg + '</span>';
                }, 100);
            }
        },
        function(error) {
            let msg = 'Gagal mendapatkan lokasi.';
            if (error.code === 1) msg = 'Izin lokasi ditolak. Akses lokasi diperlukan untuk absensi.';
            else if (error.code === 2) msg = 'GPS tidak tersedia.';
            else if (error.code === 3) msg = 'Waktu permintaan lokasi habis.';
            updateGPSStatus('error', msg);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function checkReady() {
    const btn = document.getElementById('btnCheckIn');
    if (btn) btn.disabled = !(cameraReady && gpsReady);
}

function capturePhoto() {
    const video = document.getElementById('cameraPreview');
    const canvas = document.getElementById('photoCanvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedPhotoData = canvas.toDataURL('image/jpeg', 0.85);

    document.getElementById('capturedPhoto').src = capturedPhotoData;
    document.getElementById('photoPreview').style.display = 'block';
    document.getElementById('cameraPreview').style.display = 'none';
}

function retakePhoto() {
    capturedPhotoData = null;
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('cameraPreview').style.display = '';
}

function doCheckIn() {
    if (!capturedPhotoData) { capturePhoto(); }
    submitAttendance('{{ route("attendance.check-in") }}', 'check-in');
}

function doCheckOut() {
    if (!capturedPhotoData) { capturePhoto(); }
    submitAttendance('{{ route("attendance.check-out") }}', 'check-out');
}

function doBreakStart() {
    if (!capturedPhotoData) { capturePhoto(); }
    submitAttendance('{{ route("attendance.break-start") }}', 'break-start');
}

function doBreakEnd() {
    if (!capturedPhotoData) { capturePhoto(); }
    submitAttendance('{{ route("attendance.break-end") }}', 'break-end');
}

function doResetCheckout() {
    if (confirm('Apakah Anda yakin ingin membatalkan Check Out hari ini?')) {
        const btn = document.querySelector('.btn-outline-danger');
        if (btn) btn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Memproses...';
        
        fetch('{{ route("attendance.reset-checkout") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(function(resp) { return resp.json(); }).then(function(result) {
            if (result.success) {
                showAlert('success', 'Berhasil', result.message);
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showAlert('error', 'Gagal', result.message);
                if (btn) btn.innerHTML = '<i class="ri-refresh-line me-1"></i> Batal Check Out';
            }
        });
    }
}

function submitAttendance(url, type) {
    if (!capturedPhotoData) {
        capturePhoto();
        setTimeout(function() { submitAttendance(url, type); }, 500);
        return;
    }

    if (!currentPosition) {
        showAlert('error', 'Lokasi Tidak Tersedia', 'Pastikan GPS aktif dan coba lagi.');
        return;
    }

    let btn;
    if (type === 'check-in') btn = document.getElementById('btnCheckIn');
    else if (type === 'check-out') btn = document.getElementById('btnCheckOut');
    else if (type === 'break-start') btn = document.getElementById('btnBreakStart');
    else if (type === 'break-end') btn = document.getElementById('btnBreakEnd');

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line ri-spin me-2"></i> Memproses...'; }

    document.getElementById('inputPhoto').value = capturedPhotoData;
    document.getElementById('inputLatitude').value = currentPosition.coords.latitude;
    document.getElementById('inputLongitude').value = currentPosition.coords.longitude;

    const form = document.getElementById('attendanceForm');

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form))
    }).then(function(resp) { return resp.json(); }).then(function(result) {
        if (result.success) {
            let statusText = result.data.status === 'late' ? ' (Terlambat ' + result.data.late_minutes + ' menit)' : '';
            if (type === 'check-out') {
                statusText = '';
                if (result.data.early_leave_minutes > 0) statusText = ' (Pulang awal ' + result.data.early_leave_minutes + ' menit)';
                if (result.data.overtime_minutes > 0) statusText = ' (Lembur ' + result.data.overtime_minutes + ' menit)';
            }
            let locationText = '';
            if (result.data.location_name) {
                locationText = ' di ' + result.data.location_name;
                if (result.data.distance) locationText += ' (' + result.data.distance + 'm)';
            }
            let msgType = type === 'check-in' ? 'Check In Berhasil' : (type === 'check-out' ? 'Check Out Berhasil' : (type === 'break-start' ? 'Istirahat Dimulai' : 'Istirahat Selesai'));
            showAlert('success', msgType, result.message + statusText + locationText);
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showAlert('error', 'Gagal', result.message || 'Terjadi kesalahan.');
            if (btn) {
                btn.disabled = false;
                if (type === 'check-in') btn.innerHTML = '<i class="ri-login-circle-line me-2"></i> Check In';
                else if (type === 'check-out') btn.innerHTML = '<i class="ri-logout-circle-line me-2"></i> Check Out';
                else if (type === 'break-start') btn.innerHTML = '<i class="ri-cup-line me-2"></i> Mulai Istirahat';
                else if (type === 'break-end') btn.innerHTML = '<i class="ri-walk-line me-2"></i> Selesai Istirahat';
            }
        }
    }).catch(function(err) {
        showAlert('error', 'Kesalahan Jaringan', 'Terjadi kesalahan koneksi. Coba lagi.');
        if (btn) {
            btn.disabled = false;
            if (type === 'check-in') btn.innerHTML = '<i class="ri-login-circle-line me-2"></i> Check In';
            else if (type === 'check-out') btn.innerHTML = '<i class="ri-logout-circle-line me-2"></i> Check Out';
            else if (type === 'break-start') btn.innerHTML = '<i class="ri-cup-line me-2"></i> Mulai Istirahat';
            else if (type === 'break-end') btn.innerHTML = '<i class="ri-walk-line me-2"></i> Selesai Istirahat';
        }
    });
}

function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth < 1) { currentMonth = 12; currentYear--; }
    if (currentMonth > 12) { currentMonth = 1; currentYear++; }

    fetch('{{ route("attendance.history") }}?month=' + currentMonth + '&year=' + currentYear, {
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(result) {
        if (result.success) {
            updateCalendar(result.data);
        }
    });
}

function updateCalendar(data) {
    const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    document.getElementById('monthLabel').textContent = monthNames[currentMonth] + ' ' + currentYear;

    const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

    const attMap = {};
    data.attendances.forEach(function(a) { attMap[a.date] = a; });

    let html = '<div class="calendar-header">';
    ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].forEach(function(d) {
        html += '<div>' + d + '</div>';
    });
    html += '</div><div class="calendar-grid">';

    for (let i = 0; i < firstDay; i++) {
        html += '<div class="calendar-day disabled"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = currentYear + '-' + String(currentMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        const att = attMap[dateStr];
        let cls = 'calendar-day';
        let colorClass = '';
        if (att) {
            if (att.status === 'present') colorClass = 'bg-success';
            else if (att.status === 'late') colorClass = 'bg-warning';
            else if (att.status === 'absent') colorClass = 'bg-danger';
            else if (att.status === 'late_and_early_leave') colorClass = 'bg-danger';
            else if (att.status === 'early_leave') colorClass = 'bg-info';
        } else {
            colorClass = 'bg-light';
        }
        cls += ' ' + colorClass;

        html += '<div class="' + cls + '" onclick="showDayDetail(\'' + dateStr + '\', this)">' + d + '</div>';
    }

    html += '</div>';

    // Summary
    const s = data.summary;
    html += '<div class="row g-2 mt-3">';
    html += '<div class="col-3 summary-stat"><div class="value text-success">' + s.total_present + '</div><div class="label">Hadir</div></div>';
    html += '<div class="col-3 summary-stat"><div class="value text-warning">' + s.total_late + '</div><div class="label">Telat</div></div>';
    html += '<div class="col-3 summary-stat"><div class="value text-danger">' + s.total_absent + '</div><div class="label">Absen</div></div>';
    html += '<div class="col-3 summary-stat"><div class="value text-info">' + s.total_overtime_hours + '</div><div class="label">Lembur</div></div>';
    html += '</div>';

    document.querySelector('#calendarBody').innerHTML = html;
}

function showDayDetail(dateStr, el) {
    const attMap = {};
    @foreach($monthlyAttendances as $date => $att)
        attMap['{{ $date }}'] = {
            check_in: '{{ $att->check_in_time ? $att->check_in_time->format("H:i") : "-" }}',
            check_out: '{{ $att->check_out_time ? $att->check_out_time->format("H:i") : "-" }}',
            break_start: '{{ $att->break_start_time ? $att->break_start_time->format("H:i") : "-" }}',
            break_end: '{{ $att->break_end_time ? $att->break_end_time->format("H:i") : "-" }}',
            status: '{{ $att->status }}',
            late: {{ $att->late_minutes }},
            early_leave: {{ $att->early_leave_minutes }},
        };
    @endforeach

    const att = attMap[dateStr];
    if (!att) return;

    const statusLabels = { present: 'Hadir', late: 'Terlambat', early_leave: 'Pulang Awal', late_and_early_leave: 'Terlambat & Pulang Awal', absent: 'Absen' };
    const statusColors = { present: 'success', late: 'warning', early_leave: 'info', late_and_early_leave: 'danger', absent: 'danger' };

    let html = '<div class="text-center">';
    html += '<div class="fs-5 fw-bold mb-1">' + dateStr + '</div>';
    html += '<span class="badge bg-' + (statusColors[att.status] || 'secondary') + ' mb-3">' + (statusLabels[att.status] || att.status) + '</span>';
    html += '<div class="row g-2 mt-2">';
    html += '<div class="col-6"><div class="text-muted small">Check In</div><div class="fw-bold">' + att.check_in + '</div></div>';
    html += '<div class="col-6"><div class="text-muted small">Check Out</div><div class="fw-bold">' + att.check_out + '</div></div>';
    html += '</div>';
    if (att.break_start !== '-' || att.break_end !== '-') {
        html += '<div class="row g-2 mt-2">';
        html += '<div class="col-6"><div class="text-muted small">Istirahat</div><div class="fw-bold">' + att.break_start + '</div></div>';
        html += '<div class="col-6"><div class="text-muted small">Kembali</div><div class="fw-bold">' + att.break_end + '</div></div>';
        html += '</div>';
    }
    if (att.late > 0) html += '<div class="text-warning small mt-2">Terlambat: ' + att.late + ' menit</div>';
    if (att.early_leave > 0) html += '<div class="text-info small">Pulang awal: ' + att.early_leave + ' menit</div>';
    html += '</div>';

    document.getElementById('detailModalBody').innerHTML = html;
    var modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

function showAlert(icon, title, text) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: icon, title: title, text: text, confirmButtonText: 'OK' });
    } else {
        alert(title + ': ' + text);
    }
}
</script>
@endsection
