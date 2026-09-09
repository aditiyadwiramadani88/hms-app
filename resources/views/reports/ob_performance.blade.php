@extends('layouts.master')
@section('title')
    Laporan Kebersihan / Performa OB
@endsection
@section('content')
    <x-breadcrumb title="Performa OB" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-user-smile-line me-2 text-primary"></i>
                        LAPORAN KEBERSIHAN / PERFORMA OB
                    </h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('reports.ob-performance.pdf', ['month' => $month->format('Y-m'), 'staff_id' => $staffId]) }}" class="btn btn-danger btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                        </a>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.ob-performance') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Bulan</label>
                                <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Staff</label>
                                <select name="staff_id" class="form-select">
                                    <option value="">Semua Staff</option>
                                    @foreach($staffList as $staff)
                                        <option value="{{ $staff->id }}" @selected($staffId == $staff->id)>{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body pt-3">
                    {{-- Month Label --}}
                    <div class="mb-3">
                        <h5 class="text-uppercase fw-bold text-primary mb-0">{{ $month->translatedFormat('F Y') }}</h5>
                    </div>

                    {{-- Main Table --}}
                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm" id="reportTable">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 40px;">NO</th>
                                    <th>NAMA</th>
                                    <th>TOTAL</th>
                                    <th>UMUM</th>
                                    <th>ONLINE</th>
                                    <th>KOST</th>
                                    <th>SALES</th>
                                    <th>P.K</th>
                                    <th>KOSONG</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData['rows'] as $i => $row)
                                <tr data-staff="{{ $row['nama'] }}">
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row['nama'] }}</td>
                                    <td class="text-center fw-bold text-primary clickable-detail" data-staff="{{ $row['nama'] }}" data-category="all" onclick="showDetail(this)">{{ $row['sales'] }}</td>
                                    <td class="text-center clickable-detail" data-staff="{{ $row['nama'] }}" data-category="umum" onclick="showDetail(this)">{{ $row['umum'] }}</td>
                                    <td class="text-center clickable-detail" data-staff="{{ $row['nama'] }}" data-category="online" onclick="showDetail(this)">{{ $row['online'] }}</td>
                                    <td class="text-center clickable-detail" data-staff="{{ $row['nama'] }}" data-category="kost" onclick="showDetail(this)">{{ $row['kost'] }}</td>
                                    <td class="text-center clickable-detail" data-staff="{{ $row['nama'] }}" data-category="sales_cat" onclick="showDetail(this)">{{ $row['sales_cat'] }}</td>
                                    <td class="text-center clickable-detail" data-staff="{{ $row['nama'] }}" data-category="pk" onclick="showDetail(this)">{{ $row['pk'] }}</td>
                                    <td class="text-center text-muted clickable-detail" data-staff="{{ $row['nama'] }}" data-category="kosong" onclick="showDetail(this)">{{ $row['kosong'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                            Tidak ada data pada bulan {{ $month->translatedFormat('F Y') }}.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($reportData['rows']) > 0)
                            <tfoot class="table-dark fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end text-uppercase">TOTAL</td>
                                    <td class="text-center">{{ $reportData['totals']['sales'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['umum'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['online'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['kost'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['sales_cat'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['pk'] }}</td>
                                    <td class="text-center">{{ $reportData['totals']['kosong'] }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalTitle">Detail Tugas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>NO</th>
                                    <th>TANGGAL</th>
                                    <th>KAMAR</th>
                                    <th>SUMBER BOOKING</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="detailModalBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    var detailData = @json($reportData['details']);

    function showDetail(el) {
        var staff = el.dataset.staff;
        var category = el.dataset.category;
        var count = parseInt(el.textContent);
        if (count === 0) return;

        var filtered = detailData.filter(function(d) {
            if (d.staff !== staff) return false;
            if (category === 'all') return d.kategori.toLowerCase() !== 'kosong';
            return d.kategori.toLowerCase() === category;
        });

        var body = document.getElementById('detailModalBody');
        if (filtered.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data.</td></tr>';
        } else {
            body.innerHTML = filtered.map(function(d, i) {
                var badge = d.status_verifikasi === 'verified' ? 'success'
                    : d.status_verifikasi === 'rejected' ? 'danger'
                    : d.status_verifikasi === 'pending' ? 'warning' : 'secondary';
                return '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td class="text-nowrap">' + d.tanggal + '</td>' +
                    '<td>' + d.kamar + '</td>' +
                    '<td>' + d.sumber + '</td>' +
                    '<td><span class="badge bg-' + badge + '">' + d.status_verifikasi + '</span></td>' +
                    '</tr>';
            }).join('');
        }

        var titleMap = { umum: 'UMUM', online: 'ONLINE', kost: 'KOST', sales_cat: 'SALES', pk: 'P.K', kosong: 'KOSONG', all: 'TOTAL' };
        document.getElementById('detailModalTitle').textContent = staff + ' — ' + (titleMap[category] || category.toUpperCase()) + ' (' + count + ')';

        var modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();
    }
    </script>

    <style>
    .clickable-detail { cursor: pointer; transition: background .15s; }
    .clickable-detail:hover { background: rgba(13, 110, 253, 0.08); text-decoration: underline; }
    </style>
@endsection
