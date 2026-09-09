@extends('layouts.master')
@section('title')
    Laporan Harian Pendapatan & Pengeluaran
@endsection
@section('content')
    <x-breadcrumb title="Laporan Harian" :links="[['label' => 'Reports', 'url' => route('reports.index')], ['label' => 'Laporan Bulanan', 'url' => route('reports.monthly', ['month' => $date->format('Y-m')])]]" />

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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-book-2-line me-2 text-primary"></i>
                        Laporan Semua Pendapatan & Pengeluaran
                    </h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        @php
                            $statusBadge = [
                                'pending' => ['bg' => 'warning', 'label' => 'Pending'],
                                'approved' => ['bg' => 'success', 'label' => 'Approved'],
                                'rejected' => ['bg' => 'danger', 'label' => 'Rejected'],
                            ][$approval?->status ?? 'pending'];
                        @endphp
                        <span class="badge bg-{{ $statusBadge['bg'] }} fs-12 d-flex align-items-center">
                            <i class="ri-{{ $approval?->isApproved() ? 'check-double' : ($approval?->isRejected() ? 'close' : 'time') }}-line me-1"></i>
                            {{ $statusBadge['label'] }}
                            @if($approval?->approver)
                                <span class="ms-1 opacity-75">by {{ $approval->approver->name }}</span>
                            @endif
                        </span>
                        <a href="{{ route('reports.daily.export.pdf', ['date' => $date->format('Y-m-d')]) }}" class="btn btn-danger btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                        </a>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.daily') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canApprove && (!$approval || $approval->isPending() || $approval->isRejected()))
                <div class="card-body py-2 bg-light border-bottom">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <span class="text-muted me-2 fs-12">Approval:</span>
                        @if(!$approval || $approval->isPending())
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="ri-check-double-line me-1"></i> Approve
                        </button>
                        @endif
                        @if(!$approval || $approval->isPending())
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-line me-1"></i> Reject
                        </button>
                        @endif
                        @if($approval && $approval->isRejected())
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="ri-check-double-line me-1"></i> Setujui Ulang
                        </button>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Approve Modal --}}
                <div class="modal fade" id="approveModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('reports.daily.approve') }}" class="modal-content">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                            <div class="modal-header">
                                <h5 class="modal-title">Setujui Laporan Harian</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Setujui laporan harian tanggal <strong>{{ $date->translatedFormat('l, d M Y') }}</strong>?</p>
                                <div class="mb-3">
                                    <label class="form-label">Catatan (opsional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan persetujuan..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">Ya, Setujui</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Reject Modal --}}
                <div class="modal fade" id="rejectModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('reports.daily.reject') }}" class="modal-content">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                            <div class="modal-header">
                                <h5 class="modal-title">Tolak Laporan Harian</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Tolak laporan harian tanggal <strong>{{ $date->translatedFormat('l, d M Y') }}</strong>?</p>
                                <div class="mb-3">
                                    <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Alasan mengapa laporan ditolak..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body pt-3">
                    {{-- Summary --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Kas Masuk</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($totals['kas_masuk'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-danger fs-12 mb-1">Total Kas Keluar</p>
                                    <h4 class="mb-0 text-danger">Rp {{ number_format($totals['kas_keluar'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-primary fs-12 mb-1">Sisa (Kas Masuk - Keluar)</p>
                                    <h4 class="mb-0 text-primary">Rp {{ number_format($totals['kas_masuk'] - $totals['kas_keluar'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Tanggal</p>
                                    <h4 class="mb-0 text-info">{{ $date->translatedFormat('l, d M Y') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Checkout Summary --}}
                    @if($checkoutSummary->count() > 0)
                    <div class="alert alert-info border-0 mb-3">
                        <h6 class="alert-heading mb-2"><i class="ri-logout-box-r-line me-1"></i> Checkout Hari Ini ({{ $checkoutSummary->count() }} booking)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($checkoutSummary as $co)
                                <span class="badge bg-info-subtle text-info fs-11 p-2">
                                    Room {{ $co['room'] }} — {{ $co['guest'] }} (Rp {{ number_format($co['paid'], 0, ',', '.') }})
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Shift Drill-Down Links --}}
                    @if(isset($shifts) && $shifts->count() > 0)
                    <div class="mb-3">
                        <h6 class="text-muted mb-2"><i class="ri-time-line me-1"></i> Lihat per Shift:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($shifts as $shift)
                                <a href="{{ route('reports.shift', ['date' => $date->format('Y-m-d'), 'shift_id' => $shift->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-arrow-right-s-line"></i> {{ $shift->name }}
                                    <small class="text-muted">({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Main Table --}}
                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                {{-- Row 1: main headers --}}
                                <tr>
                                    <th rowspan="2" style="width: 35px; vertical-align: middle;">NO</th>
                                    <th rowspan="2" style="vertical-align: middle;">CHECKIN</th>
                                    <th rowspan="2" style="vertical-align: middle;">CHECKOUT</th>
                                    <th rowspan="2" style="vertical-align: middle;">LAMA STAY</th>
                                    <th rowspan="2" style="vertical-align: middle;">JENIS</th>
                                    <th rowspan="2" style="vertical-align: middle;">NAMA ROOM</th>
                                    <th rowspan="2" style="vertical-align: middle;">BOOKING ID</th>
                                    <th rowspan="2" style="vertical-align: middle;">KETERANGAN</th>
                                    <th rowspan="2" style="vertical-align: middle;">TGL BAYAR</th>
                                    <th colspan="{{ $bankAccounts->count() ?: 1 }}" class="text-success">KAS MASUK</th>
                                    <th colspan="{{ $bankAccounts->count() ?: 1 }}" class="text-danger">KAS KELUAR</th>
                                    <th rowspan="2" style="vertical-align: middle;">BAYAR</th>
                                    <th rowspan="2" style="vertical-align: middle;" class="text-info">MANUAL INCOME</th>
                                </tr>
                                {{-- Row 2: wallet sub-columns --}}
                                <tr>
                                    @forelse($bankAccounts as $wallet)
                                        <th class="text-success fs-11" style="min-width: 90px;">{{ $wallet->name }}</th>
                                    @empty
                                        <th class="text-success fs-11">-</th>
                                    @endforelse
                                    @forelse($bankAccounts as $wallet)
                                        <th class="text-danger fs-11" style="min-width: 90px;">{{ $wallet->name }}</th>
                                    @empty
                                        <th class="text-danger fs-11">-</th>
                                    @endforelse
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                <tr>
                                    <td class="text-center">{{ $row['no'] }}</td>
                                    <td>{{ $row['checkin'] ?? '-' }}</td>
                                    <td>{{ $row['checkout'] ?? '-' }}</td>
                                    <td>{{ $row['lama_stay'] ?? '-' }}</td>
                                    <td>{{ $row['sumber'] ?? '-' }}</td>
                                    <td>{{ $row['room_name'] ?? '-' }}</td>
                                    <td class="text-center">
                                        @if(isset($row['booking_id']) && $row['booking_id'])
                                            <a href="{{ route('bookings.show', $row['booking_id']) }}" class="text-primary">#{{ $row['booking_id'] }}</a>
                                        @else - @endif
                                    </td>
                                    <td style="max-width: 250px; white-space: normal;">{{ Str::limit($row['keterangan'], 60) }}</td>
                                    <td class="text-nowrap">{{ $row['payment_date'] ?? '-' }}</td>
                                    {{-- KAS MASUK sub-columns per wallet --}}
                                    @forelse($bankAccounts as $wallet)
                                        <td class="text-end">
                                            @if($row['kas_masuk'] > 0 && ($row['bank_account_id'] ?? null) == $wallet->id)
                                                <span class="text-success fw-medium">{{ number_format($row['kas_masuk'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @empty
                                        <td class="text-end">
                                            @if($row['kas_masuk'] > 0)
                                                <span class="text-success">{{ number_format($row['kas_masuk'], 0, ',', '.') }}</span>
                                            @else - @endif
                                        </td>
                                    @endforelse
                                    {{-- KAS KELUAR sub-columns per wallet --}}
                                    @forelse($bankAccounts as $wallet)
                                        <td class="text-end">
                                            @if($row['kas_keluar'] > 0 && ($row['bank_account_id'] ?? null) == $wallet->id)
                                                <span class="text-danger fw-medium">{{ number_format($row['kas_keluar'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @empty
                                        <td class="text-end">
                                            @if($row['kas_keluar'] > 0)
                                                <span class="text-danger">{{ number_format($row['kas_keluar'], 0, ',', '.') }}</span>
                                            @else - @endif
                                        </td>
                                    @endforelse
                                    <td class="text-end">
                                        @if($row['bayar'] > 0)
                                            {{ number_format($row['bayar'], 0, ',', '.') }}
                                        @else - @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($row['manual_income'] ?? 0) > 0)
                                            <span class="text-info fw-medium">{{ number_format($row['manual_income'], 0, ',', '.') }}</span>
                                        @else - @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 11 + ($bankAccounts->count() * 2) }}" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                            Tidak ada transaksi pada tanggal ini.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($rows) > 0)
                                <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="9" class="text-end">TOTAL:</td>
                                    {{-- KAS MASUK totals per wallet --}}
                                    @forelse($bankAccounts as $wallet)
                                        @php
                                            $walletMasuk = collect($rows)->where('bank_account_id', $wallet->id)->sum('kas_masuk');
                                        @endphp
                                        <td class="text-end text-success">
                                            {{ $walletMasuk > 0 ? number_format($walletMasuk, 0, ',', '.') : '-' }}
                                        </td>
                                    @empty
                                        <td class="text-end text-success">{{ number_format($totals['kas_masuk'], 0, ',', '.') }}</td>
                                    @endforelse
                                    {{-- KAS KELUAR totals per wallet --}}
                                    @forelse($bankAccounts as $wallet)
                                        @php
                                            $walletKeluar = collect($rows)->where('bank_account_id', $wallet->id)->sum('kas_keluar');
                                        @endphp
                                        <td class="text-end text-danger">
                                            {{ $walletKeluar > 0 ? number_format($walletKeluar, 0, ',', '.') : '-' }}
                                        </td>
                                    @empty
                                        <td class="text-end text-danger">{{ number_format($totals['kas_keluar'], 0, ',', '.') }}</td>
                                    @endforelse
                                    <td class="text-end">{{ number_format($totals['bayar'], 0, ',', '.') }}</td>
                                    <td class="text-end text-info">{{ number_format($totals['manual_income'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    @if($approval)
                    <div class="mt-3 p-3 rounded bg-{{ $approval->isRejected() ? 'danger' : 'success' }}-subtle border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-{{ $approval->isApproved() ? 'check-double-fill' : 'close-circle-fill' }} text-{{ $approval->isRejected() ? 'danger' : 'success' }} fs-4"></i>
                            <div>
                                <strong>{{ $approval->isApproved() ? 'Laporan Disetujui' : 'Laporan Ditolak' }}</strong>
                                <span class="text-muted">oleh {{ $approval->approver?->name ?? 'System' }} pada {{ $approval->approved_at?->translatedFormat('d M Y, H:i') }}</span>
                                @if($approval->notes)
                                <p class="mb-0 mt-1 text-muted">{{ $approval->notes }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
        </div>
    </div>
@endsection
