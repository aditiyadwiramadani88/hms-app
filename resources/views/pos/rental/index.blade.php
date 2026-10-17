@extends('layouts.master')
@section('title')
    Rental POS (Point of Sale Rental)
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            POS
        @endslot
        @slot('title')
            Rental POS
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Top KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-4">
                                <i class="ri-archive-line"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Transaksi Rental</p>
                            <h4 class="mb-0 fw-bold text-primary">{{ number_format($totalOrders ?? 0) }} <span class="fs-13 fw-normal text-muted">Order</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-warning-subtle bg-warning-subtle bg-opacity-10 mb-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning text-white rounded-circle fs-4">
                                <i class="ri-time-line"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Sedang Disewa</p>
                            <h4 class="mb-0 fw-bold text-warning">{{ number_format($activeRentals ?? 0) }} <span class="fs-13 fw-normal text-muted">Aktif</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 mb-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info text-white rounded-circle fs-4">
                                <i class="ri-checkbox-circle-line"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Sudah Dikembalikan</p>
                            <h4 class="mb-0 fw-bold text-info">{{ number_format($returnedRentals ?? 0) }} <span class="fs-13 fw-normal text-muted">Selesai</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-success-subtle bg-success-subtle bg-opacity-10 mb-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success text-white rounded-circle fs-4">
                                <i class="ri-money-dollar-circle-line"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Pendapatan Rental</p>
                            <h4 class="mb-0 fw-bold text-success fs-18">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm" id="posList">
                <div class="card-header border-0 bg-light-subtle">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-shopping-cart-2-line me-2 text-primary"></i>Riwayat Order Rental</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('pos.rental.create') }}" class="btn btn-primary">
                                    <i class="ri-add-line align-bottom me-1"></i> Order Rental Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('pos.rental.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Cari nomor order, nama tamu, kamar, atau barang...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-select" name="rental_status">
                                    <option value="">Semua Status Rental</option>
                                    <option value="rented" {{ request('rental_status') === 'rented' ? 'selected' : '' }}>Sedang Disewa</option>
                                    <option value="returned" {{ request('rental_status') === 'returned' ? 'selected' : '' }}>Sudah Dikembalikan</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-select" name="payment_status">
                                    <option value="">Semua Status Bayar</option>
                                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <input type="date" class="form-control" name="date_from"
                                       value="{{ request('date_from') }}" placeholder="Dari Tanggal">
                            </div>
                            <div class="col-xxl-1 col-sm-3">
                                <input type="date" class="form-control" name="date_to"
                                       value="{{ request('date_to') }}" placeholder="Sampai Tanggal">
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <div class="d-flex gap-2">
                                    <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                        <i class="ri-filter-3-line me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('pos.rental.index') }}" class="btn btn-soft-secondary" title="Reset filter">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-hover mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="text-uppercase ps-3">No. Order</th>
                                    <th class="text-uppercase">Tamu / Kamar</th>
                                    <th class="text-uppercase">Barang Disewa</th>
                                    <th class="text-uppercase">Tanggal Sewa</th>
                                    <th class="text-uppercase text-end">Total</th>
                                    <th class="text-uppercase text-center">Pembayaran</th>
                                    <th class="text-uppercase text-center">Status Rental</th>
                                    <th class="text-uppercase text-center" style="width: 170px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders ?? [] as $order)
                                <tr>
                                    <td class="ps-3 fw-medium">
                                        <a href="{{ route('pos.show', $order->id) }}" class="text-primary fw-semibold">
                                            {{ $order->order_number ?? '#' . $order->id }}
                                        </a>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fs-10 d-block mt-1" style="width: fit-content;">RENTAL</span>
                                    </td>
                                    <td>
                                        @if($order->booking_id)
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    <span class="badge bg-primary-subtle text-primary fs-14">
                                                        <i class="ri-hotel-bed-fill"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="fw-medium text-dark">{{ $order->booking->guest->name ?? ($order->guest->name ?? 'Tamu Kamar') }}</span>
                                                    <br><small class="text-muted">Kamar {{ $order->booking->room->room_number ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        @elseif($order->guest_id)
                                            <span class="fw-medium text-dark">{{ $order->guest->name ?? 'Tamu' }}</span>
                                            <br><small class="text-muted">Walk-in (Tanpa Kamar)</small>
                                        @else
                                            <span class="badge bg-light text-dark border">Walk-in (Tanpa Kamar)</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1" style="max-width: 280px;">
                                            @foreach($order->items as $item)
                                                <span class="badge bg-secondary-subtle text-secondary border">
                                                    {{ $item->item_name }} (x{{ $item->quantity }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>{{ $order->created_at ? $order->created_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="text-end fw-bold text-dark">
                                        Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($order->payment_method === 'charge_to_room')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ri-hotel-bed-line me-1"></i>Charge to Room</span>
                                        @elseif($order->payment_method === 'cash')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-money-dollar-circle-line me-1"></i>Cash</span>
                                        @elseif($order->payment_method === 'qris')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-qr-code-line me-1"></i>QRIS</span>
                                        @elseif($order->payment_method === 'bank_transfer')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-bank-card-line me-1"></i>Transfer</span>
                                        @else
                                            <span class="badge bg-light text-muted border">{{ ucfirst($order->payment_method ?? '-') }}</span>
                                        @endif
                                        <div class="mt-1">
                                            @if($order->payment_status === 'paid')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-10">Lunas</span>
                                            @elseif($order->payment_status === 'unpaid')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-10">Belum Bayar</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10">{{ ucfirst($order->payment_status) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($order->rental_status === 'returned')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-12">
                                                <i class="ri-checkbox-circle-fill me-1"></i>Sudah Kembali
                                            </span>
                                            @if($order->returned_at)
                                                <br><small class="text-muted fs-11">{{ $order->returned_at->format('d/m/y H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-12">
                                                <i class="ri-time-fill me-1"></i>Sedang Disewa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if($order->rental_status !== 'returned')
                                                <button type="button" class="btn btn-sm btn-soft-warning btn-return-rental"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#returnRentalModal"
                                                    data-action="{{ route('pos.rental.return', $order->id) }}"
                                                    data-order="{{ $order->order_number ?? '#' . $order->id }}"
                                                    data-items="{{ $order->items->map(fn($i) => $i->item_name . ' (' . $i->quantity . ' Unit)')->join(', ') }}"
                                                    title="Kembalikan Barang ke Stok">
                                                    <i class="ri-arrow-go-back-line me-1"></i>Kembalikan
                                                </button>
                                            @else
                                                <span class="btn btn-sm btn-light disabled text-muted border-0" title="Barang telah dikembalikan">
                                                    <i class="ri-check-line text-success"></i> Selesai
                                                </span>
                                            @endif
                                            <a href="{{ route('pos.show', $order->id) }}" class="btn btn-sm btn-soft-primary" title="Lihat Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('pos.print', $order->id) }}" target="_blank" class="btn btn-sm btn-soft-secondary" title="Cetak Struk">
                                                <i class="ri-printer-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="avatar-md mx-auto mb-3">
                                            <span class="avatar-title bg-light text-muted rounded-circle fs-1">
                                                <i class="ri-shopping-bag-3-line"></i>
                                            </span>
                                        </div>
                                        <h6 class="fw-semibold">Belum Ada Transaksi Rental</h6>
                                        <p class="text-muted mb-3 fs-13">Mulai buat transaksi sewa item (Extrabed, Handuk, Bantal, dll) melalui tombol di bawah.</p>
                                        <a href="{{ route('pos.rental.create') }}" class="btn btn-sm btn-primary">
                                            <i class="ri-add-line me-1"></i> Buat Order Rental Baru
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Kembalikan Barang Rental --}}
    <div class="modal fade" id="returnRentalModal" tabindex="-1" aria-labelledby="returnRentalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="returnRentalForm" method="POST" action="">
                    @csrf
                    <div class="modal-header bg-warning-subtle text-warning">
                        <h5 class="modal-title" id="returnRentalModalLabel">
                            <i class="ri-arrow-go-back-line me-1"></i> Pengembalian Barang Rental
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 mb-3 fs-13">
                            <i class="ri-information-line me-1"></i> Dengan mengembalikan barang rental, kuota stok barang akan otomatis dipulihkan (+qty) kembali ke inventori gudang / etalase.
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fs-12 text-uppercase fw-semibold mb-1">Nomor Order</label>
                            <div class="fw-bold fs-15 text-dark" id="modalOrderNumber">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fs-12 text-uppercase fw-semibold mb-1">Daftar Barang yang Dikembalikan</label>
                            <div class="p-2 bg-light rounded border text-dark fs-13 fw-medium" id="modalOrderItems">-</div>
                        </div>

                        <div class="mb-3">
                            <label for="return_notes" class="form-label text-muted fs-12 text-uppercase fw-semibold mb-1">Catatan Pengembalian (Opsional)</label>
                            <textarea class="form-control" id="return_notes" name="return_notes" rows="2" placeholder="Contoh: Kondisi barang lengkap, bersih, tidak ada cacat..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ri-check-line me-1"></i> Konfirmasi & Pulihkan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const returnModal = document.getElementById('returnRentalModal');
        if (returnModal) {
            returnModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const action = button.getAttribute('data-action');
                const orderNumber = button.getAttribute('data-order');
                const items = button.getAttribute('data-items');

                const form = document.getElementById('returnRentalForm');
                form.action = action;
                document.getElementById('modalOrderNumber').textContent = orderNumber;
                document.getElementById('modalOrderItems').textContent = items;
                document.getElementById('return_notes').value = '';
            });
        }
    });
</script>
@endsection
