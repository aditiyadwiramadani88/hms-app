@extends('layouts.master')
@section('title') Laporan Kendaraan @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .stat-card { text-align: center; padding: 1rem 0.5rem; border-radius: 12px; height: 100%; }
    .stat-card .value { font-size: 1.5rem; font-weight: 800; }
    .stat-card .label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-unlinked { background: #ffc107; color: #212529; }
    .clickable-row { cursor: pointer; }
    .clickable-row:hover { background: #f8f9fa; }
    .photo-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 2px solid #e9ecef; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Security Gate @endslot
    @slot('title') Laporan Kendaraan @endslot
@endcomponent

{{-- Stats --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="stat-card card border-primary bg-primary-subtle"><div class="value text-primary">{{ $stats['total'] }}</div><div class="label">Total Masuk</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card card border-success bg-success-subtle"><div class="value text-success">{{ $stats['inside'] }}</div><div class="label">Masih di Dalam</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card card border-info bg-info-subtle"><div class="value text-info">{{ $stats['linked'] }}</div><div class="label">Ter-link Booking</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card card border-warning bg-warning-subtle"><div class="value text-warning">{{ $stats['unlinked'] }}</div><div class="label">Belum Ter-link</div></div></div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="in" {{ request('status') === 'in' ? 'selected' : '' }}>Masuk</option>
                    <option value="out" {{ request('status') === 'out' ? 'selected' : '' }}>Keluar</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tujuan</label>
                <select name="purpose" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['menginap','kunjungan','delivery','karyawan','lainnya'] as $p)
                    <option value="{{ $p }}" {{ request('purpose') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Link Booking</label>
                <select name="link_status" class="form-select">
                    <option value="">Semua</option>
                    <option value="linked" {{ request('link_status') === 'linked' ? 'selected' : '' }}>Ter-link</option>
                    <option value="unlinked" {{ request('link_status') === 'unlinked' ? 'selected' : '' }}>Belum Ter-link</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Cari</label>
                <input type="text" name="search" class="form-control" placeholder="Plat / Pengemudi..." value="{{ request('search') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="ri-filter-line me-1"></i> Filter</button>
                <a href="{{ route('security-gate.report') }}" class="btn btn-light">Reset</a>
                <a href="{{ route('security-gate.report.parking') }}" class="btn btn-info"><i class="ri-file-chart-line me-1"></i> Laporan Parkir</a>
            </div>
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
                        <th>Plat</th>
                        <th>Pengemudi</th>
                        <th>Tipe</th>
                        <th>Tujuan</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Status</th>
                        <th>Booking</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="clickable-row" onclick="showDetail({{ $log->id }})">
                        <td><strong>{{ $log->plate_number }}</strong></td>
                        <td>{{ $log->driver_name }}</td>
                        <td>{{ $log->vehicle_type }}</td>
                        <td>{{ $log->purpose }}@if($log->destination_room) ({{ $log->destination_room }})@endif</td>
                        <td>{{ $log->time_in->format('H:i') }}</td>
                        <td>{{ $log->time_out?->format('H:i') ?? '-' }}</td>
                        <td>
                            @if($log->status === 'in')
                            <span class="badge bg-success">Masuk</span>
                            @elseif($log->status === 'out')
                            <span class="badge bg-secondary">Keluar</span>
                            @else
                            <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td>
                            @if($log->booking_id)
                            <span class="badge bg-info">#{{ $log->booking_id }}</span>
                            @else
                            <span class="badge badge-unlinked">Belum ter-link</span>
                            @endif
                        </td>
                        <td>
                            @if($log->photo_in)
                            <i class="ri-camera-line text-muted" title="Ada foto"></i>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $logs->links() }}

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Kendaraan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-4"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
function showDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    document.getElementById('detailContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';

    fetch('{{ route("security-gate.report.detail", ":id") }}'.replace(':id', id))
        .then(r => r.json())
        .then(d => {
            const linked = d.is_linked
                ? '<span class="badge bg-info">Booking #' + d.booking_id + '</span>'
                : '<span class="badge bg-warning">Belum ter-link</span>';

            let photos = '';
            if (d.photo_in) photos += '<div><strong>Foto Masuk:</strong><br><img loading="lazy" src="' + d.photo_in + '" class="img-fluid rounded mt-1" style="max-height:200px"></div>';
            if (d.photo_out) photos += '<div class="mt-2"><strong>Foto Keluar:</strong><br><img loading="lazy" src="' + d.photo_out + '" class="img-fluid rounded mt-1" style="max-height:200px"></div>';

            document.getElementById('detailContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><td>Plat</td><td><strong>\${d.plate_number}</strong></td></tr>
                            <tr><td>Pengemudi</td><td>\${d.driver_name}</td></tr>
                            <tr><td>Tipe</td><td>\${d.vehicle_type}</td></tr>
                            <tr><td>Warna</td><td>\${d.vehicle_color || '-'}</td></tr>
                            <tr><td>Tujuan</td><td>\${d.purpose}</td></tr>
                            <tr><td>Kamar</td><td>\${d.room || '-'}</td></tr>
                            <tr><td>Waktu Masuk</td><td>\${d.time_in}</td></tr>
                            <tr><td>Waktu Keluar</td><td>\${d.time_out || '-'}</td></tr>
                            <tr><td>Status</td><td><span class="badge bg-\${d.status === 'in' ? 'success' : 'secondary'}">\${d.status}</span></td></tr>
                            <tr><td>Booking</td><td>\${linked}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><td>Tamu</td><td>\${d.guest || '-'}</td></tr>
                            <tr><td>Security Masuk</td><td>\${d.security_in || '-'}</td></tr>
                            <tr><td>Security Keluar</td><td>\${d.security_out || '-'}</td></tr>
                            <tr><td>Catatan</td><td>\${d.notes || '-'}</td></tr>
                        </table>
                        \${photos}
                    </div>
                </div>
            `;
        })
        .catch(() => {
            document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Gagal memuat detail.</div>';
        });
}
</script>
@endsection
