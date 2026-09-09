@extends('layouts.master')
@section('title')
    Rincian Pembayaran Kost - {{ $booking->guest->name ?? 'Guest' }}
@endsection
@section('content')
    <x-breadcrumb title="Rincian Pembayaran Kost" :links="[
        ['label' => 'Reports', 'url' => route('reports.index')],
        ['label' => 'Laporan Kost', 'url' => route('reports.kost')],
    ]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <div class="flex-grow-1">
                        <h4 class="card-title mb-1">
                            <i class="ri-file-list-3-line me-2 text-primary"></i>
                            RINCIAN PEMBAYARAN KOST BULANAN
                        </h4>
                        <p class="text-muted mb-0">{{ $hotel->name ?? 'SIMPANG HOMESTAY DAN KOZZ' }}</p>
                    </div>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <a href="{{ route('reports.kost.detail.pdf', $booking->id) }}" class="btn btn-danger btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                        </a>
                        <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ri-eye-line align-bottom me-1"></i> Lihat Booking
                        </a>
                    </div>
                </div>

                {{-- Guest & Room Info --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th class="text-muted" style="width: 140px;">NO KAMAR</th>
                                    <td class="fw-bold fs-15">: {{ $booking->room?->room_number ?? ($booking->custom_room_name ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">NAMA PENYEWA</th>
                                    <td class="fw-bold fs-15">: {{ $booking->guest->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">TIPE KAMAR</th>
                                    <td>: {{ $booking->room?->roomType?->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th class="text-muted" style="width: 140px;">CHECK-IN</th>
                                    <td>: {{ $booking->check_in?->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">HARGA KOST</th>
                                    <td>: Rp {{ number_format($booking->base_price, 0, ',', '.') }} / bulan</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">DEPOSIT</th>
                                    <td>: Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Payment Table --}}
                <div class="card-body pt-3">
                    <div class="table-responsive table-card">
                        <table class="table table-bordered align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle" style="width: 40px;">NO</th>
                                    <th rowspan="2" class="align-middle">HARGA KOST</th>
                                    <th rowspan="2" class="align-middle">DEPOSIT</th>
                                    <th rowspan="2" class="align-middle">JUMLAH</th>
                                    <th colspan="2" class="text-center">TANGGAL</th>
                                    <th rowspan="2" class="align-middle">PERIODE</th>
                                    <th colspan="3" class="text-center">TTD</th>
                                </tr>
                                <tr>
                                    <th>TF</th>
                                    <th>CASH</th>
                                    <th>PENGHUNI</th>
                                    <th>KASIR</th>
                                    <th>PEMIMPIN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentRows as $row)
                                <tr>
                                    <td class="text-center">{{ $row['no'] }}</td>
                                    <td class="text-end">Rp {{ number_format($row['harga_kost'], 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if($row['deposit'] > 0)
                                            Rp {{ number_format($row['deposit'], 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-medium">Rp {{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $row['tanggal_tf'] ?? '-' }}</td>
                                    <td class="text-center">{{ $row['tanggal_cash'] ?? '-' }}</td>
                                    <td class="text-center text-nowrap">{{ $row['periode'] }}</td>
                                    {{-- TTD Penghuni --}}
                                    <td class="text-center">
                                        <span class="text-success" title="Pembayaran diterima">
                                            <i class="ri-check-double-line fs-16"></i>
                                        </span>
                                    </td>
                                    {{-- TTD Kasir --}}
                                    <td class="text-center">
                                        <span class="text-primary" title="{{ $row['kasir'] }}">
                                            <i class="ri-check-line fs-16"></i>
                                            <small class="d-block text-muted" style="font-size: 10px;">{{ Str::limit($row['kasir'], 8) }}</small>
                                        </span>
                                    </td>
                                    {{-- TTD Pemimpin (Approval) --}}
                                    <td class="text-center">
                                        @if($row['approval'] && $row['approval']->isApproved())
                                            <span class="text-success" title="Disetujui oleh {{ $row['approval']->approver->name ?? '-' }} pada {{ $row['approval']->approved_at?->format('d/m/Y H:i') }}">
                                                <i class="ri-check-double-line fs-16"></i>
                                                <small class="d-block text-muted" style="font-size: 10px;">{{ Str::limit($row['approval']->approver->name ?? '', 8) }}</small>
                                            </span>
                                        @elseif($row['approval'] && $row['approval']->status === 'rejected')
                                            <span class="text-danger" title="Ditolak: {{ $row['approval']->notes }}">
                                                <i class="ri-close-line fs-16"></i>
                                                <small class="d-block text-danger" style="font-size: 10px;">Ditolak</small>
                                            </span>
                                        @else
                                            @can('approveKostPayment')
                                                <div class="btn-group btn-group-sm">
                                                    <form action="{{ route('reports.kost.approval.approve', $row['approval']->id) }}" method="POST" data-ajax="true" class="d-inline">
                                                        @csrf
                                                        <button type="submit" data-submit-protect="true" class="btn btn-soft-success btn-sm px-2 py-0" title="Approve">
                                                            <i class="ri-check-line"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-soft-danger btn-sm px-2 py-0" title="Reject" 
                                                        data-bs-toggle="modal" data-bs-target="#rejectModal{{ $row['approval']->id }}">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-money-dollar-circle-line fs-1 d-block mb-2"></i>
                                            Belum ada pembayaran tercatat.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Legend --}}
                    <div class="mt-3 d-flex gap-4 text-muted small">
                        <span><i class="ri-check-double-line text-success"></i> = Disetujui/Verified</span>
                        <span><i class="ri-check-line text-primary"></i> = Tercatat</span>
                        <span><span class="badge bg-warning-subtle text-warning">Menunggu</span> = Belum diverifikasi pemimpin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modals --}}
    @foreach($paymentRows as $row)
        @if($row['approval'] && $row['approval']->isPending())
        <div class="modal fade" id="rejectModal{{ $row['approval']->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('reports.kost.approval.reject', $row['approval']->id) }}" method="POST" data-ajax="true">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tolak Pembayaran #{{ $row['no'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                <textarea name="notes" class="form-control" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" data-submit-protect="true" class="btn btn-danger">Tolak Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endsection
