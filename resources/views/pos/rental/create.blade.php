@extends('layouts.master')
@section('title')
    Order Rental Baru - Rental POS
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('pos.rental.index') }}">Rental POS</a>
        @endslot
        @slot('title')
            Order Rental Baru
        @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('pos.rental.store') }}" method="POST" data-ajax="true" id="posRentalForm">
        @csrf
        <div class="row">
            {{-- Left: Rental Items Catalog --}}
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light-subtle d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">
                                <i class="ri-hotel-bed-line me-2 text-primary"></i>Katalog Barang Sewa (Rental)
                            </h5>
                            <small class="text-muted">Item kategori <strong>SEWA</strong> — Tanpa pemotongan stok inventori habis pakai (*No Stock Deduction*)</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            {{ $inventoryItems->count() }} Item Tersedia
                        </span>
                    </div>
                    <div class="card-body">
                        {{-- Search & Filter --}}
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <div class="search-box">
                                    <input type="text" id="rentalItemSearch" class="form-control" placeholder="Cari barang sewa (contoh: Extrabed, Handuk, Bantal)...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <select id="rentalCategoryFilter" class="form-select" style="width: 170px;">
                                    <option value="">Semua Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Item Table --}}
                        <div class="table-responsive border rounded" style="max-height: 480px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top fs-11 text-uppercase">
                                    <tr>
                                        <th class="ps-3">Nama Barang Sewa</th>
                                        <th>Kategori</th>
                                        <th>Ketersediaan Stok</th>
                                        <th class="text-end">Tarif Sewa</th>
                                        <th class="text-center" style="width: 90px;">Jumlah</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="rentalInventoryList">
                                    @forelse($inventoryItems as $item)
                                    @php
                                        $available = (int)$item->stock;
                                        $totalCap = (int)($item->total_capacity ?? $item->stock);
                                        $rented = (int)($item->rented_count ?? 0);
                                    @endphp
                                    <tr class="rental-item-row" data-name="{{ strtolower($item->name) }}" data-category="{{ $item->inventoryCategory?->name ?? 'SEWA' }}">
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                            <small class="text-muted">Satuan: {{ $item->unit ?? 'Pcs' }}</small>
                                            @if($item->is_refundable)
                                                <div class="mt-1">
                                                    <span class="badge bg-warning-subtle text-warning fs-10 border border-warning-subtle">
                                                        <i class="ri-shield-check-line me-1"></i>Deposit: Rp {{ number_format((float)$item->deposit_amount > 0 ? $item->deposit_amount : $item->price_per_unit, 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                {{ $item->inventoryCategory?->name ?? 'SEWA' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($available > 0)
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-12 px-2 py-1">
                                                        <i class="ri-checkbox-circle-fill me-1"></i>{{ $available }} / {{ $totalCap }} Tersedia
                                                    </span>
                                                    @if($rented > 0)
                                                        <span class="text-warning fs-11 fw-medium mt-1">
                                                            <i class="ri-time-line"></i> {{ $rented }} sedang disewa
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-12 px-2 py-1">
                                                        <i class="ri-close-circle-fill me-1"></i>0 / {{ $totalCap }} (Habis)
                                                    </span>
                                                    @if($rented > 0)
                                                        <span class="text-muted fs-11 mt-1">
                                                            {{ $rented }} sedang disewa
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-primary fs-14">
                                            Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center rental-qty-input" value="1" min="1" max="{{ $available }}" id="qty_{{ $item->id }}" {{ $available <= 0 ? 'disabled' : '' }}>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm {{ $available > 0 ? 'btn-primary' : 'btn-light disabled' }} add-rental-btn w-100"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-price="{{ $item->price_per_unit }}"
                                                data-stock="{{ $available }}"
                                                data-unit="{{ $item->unit ?? 'Pcs' }}"
                                                data-deposit="{{ $item->is_refundable ? ((float)$item->deposit_amount > 0 ? $item->deposit_amount : $item->price_per_unit) : 0 }}"
                                                {{ $available <= 0 ? 'disabled' : '' }}>
                                                <i class="{{ $available > 0 ? 'ri-add-line' : 'ri-close-line' }} me-1"></i> {{ $available > 0 ? 'Tambah' : 'Habis' }}
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div class="avatar-md mx-auto mb-3">
                                                <span class="avatar-title bg-light text-muted rounded-circle fs-1">
                                                    <i class="ri-inbox-line"></i>
                                                </span>
                                            </div>
                                            <h6 class="fw-semibold">Belum Ada Barang Kategori SEWA</h6>
                                            <p class="text-muted mb-3 fs-13">Tambahkan item rental di master data Inventory dengan kategori <strong>SEWA</strong> agar muncul di sini.</p>
                                            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-soft-primary" target="_blank">
                                                <i class="ri-external-link-line me-1"></i> Ke Master Inventory
                                            </a>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Customer & Order Summary --}}
            <div class="col-xl-4">
                {{-- Customer Info (Nullable / Fleksibel) --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="ri-user-line me-2 text-primary"></i>Penyewa / Kamar</h5>
                            <span class="badge bg-secondary-subtle text-secondary border">Opsional (Nullable)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($booking)
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="guest_id" value="{{ $booking->guest_id }}">
                            <input type="hidden" name="room_id" value="{{ $booking->room_id }}">
                            <div class="alert alert-primary border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs flex-shrink-0 me-2">
                                        <span class="avatar-title bg-primary text-white rounded-circle fs-14">
                                            <i class="ri-hotel-bed-line"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $booking->guest->name ?? 'Tamu Kamar' }}</h6>
                                        <small class="text-muted">Kamar {{ $booking->room->room_number ?? 'N/A' }} ({{ $booking->room->roomType->name ?? '' }})</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Pilih Kamar Tamu (Checked-In)</label>
                                <select class="form-select" name="room_id" id="rental_room_select">
                                    <option value="" data-booking="" data-guest-id="" data-guest="">-- Walk-in / Tanpa Kamar --</option>
                                    @foreach($occupiedBookings as $occupiedBooking)
                                        <option value="{{ $occupiedBooking->room->id }}"
                                            data-booking="{{ $occupiedBooking->id }}"
                                            data-guest-id="{{ $occupiedBooking->guest_id }}"
                                            data-guest="{{ $occupiedBooking->guest->name ?? '' }}">
                                            Kamar {{ $occupiedBooking->room->room_number }} — {{ $occupiedBooking->guest->name ?? 'Tamu' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted fs-11">Pilih kamar jika disewa tamu menginap, atau biarkan Walk-in untuk tamu luar.</small>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Nama Tamu / Pelanggan</label>
                                <select class="form-select" name="guest_id" id="rental_guest_select">
                                    <option value="">Walk-in Guest (Umum)</option>
                                    @foreach($guests as $guest)
                                        <option value="{{ $guest->id }}">{{ $guest->name }} ({{ $guest->phone ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="booking_id" id="rental_booking_id" value="">
                        @endif
                    </div>
                </div>

                {{-- Cart & Order Summary --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-shopping-bag-line me-2 text-primary"></i>Daftar Barang Disewa</h5>
                        <span id="rentalCartCount" class="badge bg-primary">0 Item</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                            <table class="table align-middle table-borderless mb-0">
                                <thead class="table-light fs-11 text-uppercase text-muted border-bottom">
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th class="text-center" style="width: 90px;">Qty</th>
                                        <th class="text-end" style="width: 100px;">Subtotal</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="rentalCartItems">
                                    <tr id="emptyCartRow">
                                        <td colspan="4" class="text-center text-muted py-4 fs-13">
                                            <i class="ri-inbox-line fs-2 d-block mb-1 text-muted"></i>
                                            Belum ada barang sewa yang dipilih.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Calculation Breakdown --}}
                        <div class="p-3 bg-light border-top">
                            <div class="d-flex justify-content-between mb-2 fs-13">
                                <span class="text-muted">Subtotal Sewa:</span>
                                <span class="fw-semibold text-dark" id="displaySubtotal">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs-13" id="depositRow" style="display: none;">
                                <span class="text-warning"><i class="ri-shield-check-line me-1"></i>Estimasi Jaminan (Deposit):</span>
                                <span class="fw-semibold text-warning" id="displayDeposit">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 fs-13">
                                <span class="text-muted">Potongan (Diskon):</span>
                                <div class="input-group input-group-sm" style="width: 140px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="discount_amount" id="rentalDiscount" class="form-control text-end" value="0" min="0">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top">
                                <span class="fw-bold fs-15 text-dark">Total Tagihan:</span>
                                <span class="fw-bold fs-17 text-primary" id="displayGrandTotal">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Options --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light-subtle">
                        <h5 class="card-title mb-0"><i class="ri-wallet-3-line me-2 text-primary"></i>Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Pilih Akun / Metode</label>
                            <select class="form-select" name="bank_account_id" id="rental_payment_account">
                                <option value="charge_to_room" id="opt_charge_to_room" data-type="room" style="{{ $booking ? '' : 'display:none;' }}">
                                    Charge to Room (Bebankan ke Tagihan Kamar)
                                </option>
                                @foreach($bankAccounts as $account)
                                    @php
                                        $accountType = 'bank';
                                        $lowerName = strtolower($account->name);
                                        if (str_contains($lowerName, 'tunai') || str_contains($lowerName, 'cash')) {
                                            $accountType = 'cash';
                                        } elseif (str_contains($lowerName, 'qris')) {
                                            $accountType = 'qris';
                                        }
                                    @endphp
                                    <option value="{{ $account->id }}" data-type="{{ $accountType }}" {{ $accountType === 'cash' && !$booking ? 'selected' : '' }}>
                                        {{ $account->name }} (Saldo: Rp {{ number_format($account->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="payment_method" id="rental_payment_method" value="{{ $booking ? 'charge_to_room' : 'cash' }}">
                        </div>

                        {{-- Cash Calculator --}}
                        <div id="rental_cash_calc" class="p-3 bg-light rounded border border-dashed mb-3" style="display:none;">
                            <div class="d-flex align-items-center mb-2">
                                <label class="form-label fs-12 text-muted text-uppercase mb-0 flex-grow-1">Uang Diterima</label>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="customer_paid" id="rental_customer_paid" placeholder="Masukkan nominal uang...">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-13">Kembalian:</span>
                                <span id="rental_change_amount" class="fw-bold text-success fs-15">Rp 0</span>
                            </div>
                            <div id="rental_paid_warning" class="text-danger fs-12 mt-2" style="display:none;">
                                <i class="ri-error-warning-line align-middle"></i> Uang diterima kurang dari total sewa.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Catatan / Keterangan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Contoh: Sewa extrabed 1 malam kamar 101..."></textarea>
                        </div>

                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100 py-2 fs-15 fw-bold" id="btnSubmitRental" disabled>
                            <i class="ri-check-line me-1"></i> Simpan & Selesaikan Order Rental
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let cart = {}; // id => { id, name, price, unit, qty, deposit }

        const cartItemsContainer = document.getElementById('rentalCartItems');
        const emptyCartRow = document.getElementById('emptyCartRow');
        const cartCountBadge = document.getElementById('rentalCartCount');
        const displaySubtotal = document.getElementById('displaySubtotal');
        const displayDeposit = document.getElementById('displayDeposit');
        const depositRow = document.getElementById('depositRow');
        const displayGrandTotal = document.getElementById('displayGrandTotal');
        const discountInput = document.getElementById('rentalDiscount');
        const btnSubmit = document.getElementById('btnSubmitRental');

        const roomSelect = document.getElementById('rental_room_select');
        const guestSelect = document.getElementById('rental_guest_select');
        const bookingIdHidden = document.getElementById('rental_booking_id');
        const paymentAccount = document.getElementById('rental_payment_account');
        const paymentMethodHidden = document.getElementById('rental_payment_method');
        const optChargeToRoom = document.getElementById('opt_charge_to_room');
        const cashCalc = document.getElementById('rental_cash_calc');
        const customerPaidInput = document.getElementById('rental_customer_paid');
        const changeAmountDisplay = document.getElementById('rental_change_amount');
        const paidWarning = document.getElementById('rental_paid_warning');

        // Search and Category Filter
        const itemSearch = document.getElementById('rentalItemSearch');
        const categoryFilter = document.getElementById('rentalCategoryFilter');
        const itemRows = document.querySelectorAll('.rental-item-row');

        function filterItems() {
            const query = (itemSearch.value || '').toLowerCase().trim();
            const cat = (categoryFilter.value || '').toLowerCase().trim();

            itemRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const category = row.getAttribute('data-category').toLowerCase();
                const matchesSearch = !query || name.includes(query);
                const matchesCat = !cat || category === cat;

                row.style.display = (matchesSearch && matchesCat) ? '' : 'none';
            });
        }

        itemSearch.addEventListener('keyup', filterItems);
        categoryFilter.addEventListener('change', filterItems);

        // Room Selection Changes (Nullable & Flexible)
        if (roomSelect) {
            roomSelect.addEventListener('change', function () {
                const selectedOpt = roomSelect.options[roomSelect.selectedIndex];
                const bookingId = selectedOpt.getAttribute('data-booking') || '';
                const guestId = selectedOpt.getAttribute('data-guest-id') || '';

                bookingIdHidden.value = bookingId;

                if (guestId && guestSelect) {
                    guestSelect.value = guestId;
                }

                if (bookingId) {
                    // Room selected -> Enable Charge to Room
                    optChargeToRoom.style.display = '';
                    optChargeToRoom.removeAttribute('disabled');
                    paymentAccount.value = 'charge_to_room';
                } else {
                    // Walk-in -> Disable Charge to Room
                    optChargeToRoom.style.display = 'none';
                    optChargeToRoom.setAttribute('disabled', 'disabled');
                    if (paymentAccount.value === 'charge_to_room') {
                        // Default to cash or first available bank account
                        for (let opt of paymentAccount.options) {
                            if (opt.value !== 'charge_to_room') {
                                paymentAccount.value = opt.value;
                                break;
                            }
                        }
                    }
                }
                updatePaymentUI();
            });
        }

        function updatePaymentUI() {
            const selectedOpt = paymentAccount.options[paymentAccount.selectedIndex];
            const accType = selectedOpt ? selectedOpt.getAttribute('data-type') : '';

            if (selectedOpt && selectedOpt.value === 'charge_to_room') {
                paymentMethodHidden.value = 'charge_to_room';
                cashCalc.style.display = 'none';
            } else if (accType === 'cash') {
                paymentMethodHidden.value = 'cash';
                cashCalc.style.display = 'block';
                calcChange();
            } else if (accType === 'qris') {
                paymentMethodHidden.value = 'qris';
                cashCalc.style.display = 'none';
            } else {
                paymentMethodHidden.value = 'bank_transfer';
                cashCalc.style.display = 'none';
            }
        }

        paymentAccount.addEventListener('change', updatePaymentUI);

        function calcChange() {
            const paid = parseFloat(customerPaidInput.value) || 0;
            const grandTotal = calculateGrandTotal();
            const change = paid - grandTotal;

            if (customerPaidInput.value && change < 0) {
                paidWarning.style.display = 'block';
                changeAmountDisplay.textContent = 'Kurang: Rp ' + Math.abs(change).toLocaleString('id-ID');
                changeAmountDisplay.className = 'fw-bold text-danger fs-15';
            } else {
                paidWarning.style.display = 'none';
                changeAmountDisplay.textContent = 'Rp ' + Math.max(0, change).toLocaleString('id-ID');
                changeAmountDisplay.className = 'fw-bold text-success fs-15';
            }
        }

        if (customerPaidInput) {
            customerPaidInput.addEventListener('input', calcChange);
        }

        // Add to Cart
        document.querySelectorAll('.add-rental-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = parseFloat(this.getAttribute('data-price')) || 0;
                const stock = parseInt(this.getAttribute('data-stock')) || 0;
                const unit = this.getAttribute('data-unit');
                const deposit = parseFloat(this.getAttribute('data-deposit')) || 0;
                const qtyInput = document.getElementById('qty_' + id);
                const addQty = parseInt(qtyInput ? qtyInput.value : 1) || 1;

                if (stock <= 0) {
                    Toastify({
                        text: name + " sedang tidak tersedia (stok habis / seluruhnya sedang disewa)",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "linear-gradient(to right, #e63946, #d90429)" }
                    }).showToast();
                    return;
                }

                const currentQty = cart[id] ? cart[id].qty : 0;
                if ((currentQty + addQty) > stock) {
                    Toastify({
                        text: `Stok tidak mencukupi! Tersedia: ${stock} unit, saat ini di keranjang: ${currentQty}`,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "linear-gradient(to right, #f77f00, #d62828)" }
                    }).showToast();
                    return;
                }

                if (cart[id]) {
                    cart[id].qty += addQty;
                } else {
                    cart[id] = { id, name, price, stock, unit, qty: addQty, deposit };
                }

                if (qtyInput) qtyInput.value = 1;
                renderCart();

                Toastify({
                    text: name + " ditambahkan ke daftar sewa",
                    duration: 2000,
                    gravity: "top",
                    position: "right",
                    style: { background: "linear-gradient(to right, #0ab39c, #405189)" }
                }).showToast();
            });
        });

        function calculateGrandTotal() {
            let subtotal = 0;
            Object.values(cart).forEach(item => {
                subtotal += item.price * item.qty;
            });
            const discount = parseFloat(discountInput.value) || 0;
            return Math.max(0, subtotal - discount);
        }

        function renderCart() {
            const keys = Object.keys(cart);
            if (keys.length === 0) {
                cartItemsContainer.innerHTML = `
                    <tr id="emptyCartRow">
                        <td colspan="4" class="text-center text-muted py-4 fs-13">
                            <i class="ri-inbox-line fs-2 d-block mb-1 text-muted"></i>
                            Belum ada barang sewa yang dipilih.
                        </td>
                    </tr>
                `;
                cartCountBadge.textContent = '0 Item';
                displaySubtotal.textContent = 'Rp 0';
                displayGrandTotal.textContent = 'Rp 0';
                depositRow.style.display = 'none';
                btnSubmit.disabled = true;
                calcChange();
                return;
            }

            let html = '';
            let subtotal = 0;
            let totalDeposit = 0;
            let totalQty = 0;

            keys.forEach((id, index) => {
                const item = cart[id];
                const itemSubtotal = item.price * item.qty;
                subtotal += itemSubtotal;
                totalDeposit += (item.deposit * item.qty);
                totalQty += item.qty;

                html += `
                    <tr class="border-bottom">
                        <td class="ps-3">
                            <div class="fw-semibold text-dark fs-13">${item.name}</div>
                            <small class="text-muted">Rp ${item.price.toLocaleString('id-ID')} / ${item.unit}</small>
                            <input type="hidden" name="items[${index}][inventory_id]" value="${item.id}">
                            <input type="hidden" name="items[${index}][item_name]" value="${item.name}">
                            <input type="hidden" name="items[${index}][price_per_unit]" value="${item.price}">
                            <input type="hidden" name="items[${index}][quantity]" value="${item.qty}">
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="width: 80px; margin: 0 auto;">
                                <button type="button" class="btn btn-outline-secondary btn-cart-dec px-1" data-id="${item.id}">-</button>
                                <input type="text" class="form-control text-center px-1" value="${item.qty}" readonly>
                                <button type="button" class="btn btn-outline-secondary btn-cart-inc px-1" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-dark fs-13">
                            Rp ${itemSubtotal.toLocaleString('id-ID')}
                        </td>
                        <td class="text-center pe-2">
                            <button type="button" class="btn btn-sm btn-ghost-danger p-0 btn-cart-del" data-id="${item.id}" title="Hapus">
                                <i class="ri-delete-bin-line fs-14"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            cartItemsContainer.innerHTML = html;
            cartCountBadge.textContent = totalQty + ' Unit';
            displaySubtotal.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');

            if (totalDeposit > 0) {
                depositRow.style.display = 'flex';
                displayDeposit.textContent = 'Rp ' + totalDeposit.toLocaleString('id-ID');
            } else {
                depositRow.style.display = 'none';
            }

            const discount = parseFloat(discountInput.value) || 0;
            const grandTotal = Math.max(0, subtotal - discount);
            displayGrandTotal.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

            btnSubmit.disabled = false;
            calcChange();
            attachCartEvents();
        }

        function attachCartEvents() {
            document.querySelectorAll('.btn-cart-inc').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    if (cart[id]) {
                        if (cart[id].stock && cart[id].qty >= cart[id].stock) {
                            Toastify({
                                text: `Maksimal stok tercapai (${cart[id].stock} unit)`,
                                duration: 2000,
                                gravity: "top",
                                position: "right",
                                style: { background: "linear-gradient(to right, #f77f00, #d62828)" }
                            }).showToast();
                            return;
                        }
                        cart[id].qty++;
                        renderCart();
                    }
                });
            });

            document.querySelectorAll('.btn-cart-dec').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    if (cart[id]) {
                        if (cart[id].qty > 1) {
                            cart[id].qty--;
                        } else {
                            delete cart[id];
                        }
                        renderCart();
                    }
                });
            });

            document.querySelectorAll('.btn-cart-del').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    delete cart[id];
                    renderCart();
                });
            });
        }

        discountInput.addEventListener('input', function () {
            const grandTotal = calculateGrandTotal();
            displayGrandTotal.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
            calcChange();
        });

        // Initialize UI
        updatePaymentUI();
    });
</script>
@endsection
