@extends('layouts.master')
@section('title')
    Create POS Order
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('pos.index') }}">POS</a>
        @endslot
        @slot('title')
            Create Order
        @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('pos.store') }}" method="POST" data-ajax="true" id="posCreateForm">
        @csrf
        <div class="row">
            {{-- Left: Items --}}
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0"><i class="ri-shopping-basket-2-line me-2 text-primary"></i>Select Items</h5>
                    </div>
                    <div class="card-body">
                        {{-- Search & Filter --}}
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <div class="search-box">
                                    <input type="text" id="itemSearch" class="form-control" placeholder="Search item name...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <select id="categoryFilter" class="form-select" style="width: 160px;">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Models\InventoryCategory::where('hotel_id', active_hotel_id())->get() as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Item Table --}}
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top fs-11 text-uppercase">
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th>Category</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center" style="width: 110px;">Qty</th>
                                        <th class="text-center" style="width: 90px;">Add</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryList">
                                    @forelse($inventoryItems as $item)
                                    <tr class="item-row" data-name="{{ strtolower($item->name) }}" data-category="{{ $item->category?->name ?? 'Uncategorized' }}">
                                        <td class="ps-3">
                                            <div class="fw-medium">{{ $item->name }}</div>
                                            <small class="text-muted">Stock: {{ $item->stock }} {{ $item->unit }}</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $item->category?->name ?? '-' }}</span></td>
                                        <td class="text-end fw-semibold text-primary">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm qty-input text-center" value="1" min="1" id="qty_{{ $item->id }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-success add-item-btn"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-price="{{ $item->price_per_unit }}"
                                                data-unit="{{ $item->unit }}">
                                                <i class="ri-add-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No inventory items available.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Order Summary --}}
            <div class="col-xl-4">
                {{-- Customer Info --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-line me-2"></i>Customer / Room</h5>
                    </div>
                    <div class="card-body">
                        @if($booking)
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="guest_id" value="{{ $booking->guest_id }}">
                            <input type="hidden" name="room_id" value="{{ $booking->room_id }}">
                            <div class="alert alert-info border-0 mb-2">
                                <i class="ri-hotel-bed-line me-2"></i>
                                <strong>{{ $booking->guest->name ?? 'N/A' }}</strong> — Room {{ $booking->room->room_number ?? 'N/A' }}
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Room (Occupied)</label>
                                <select class="form-select" name="room_id" id="room_select">
                                    <option value="">Walk-in / No Room</option>
                                    @foreach($occupiedBookings as $occupiedBooking)
                                        <option value="{{ $occupiedBooking->room->id }}" data-booking="{{ $occupiedBooking->id }}" data-guest="{{ $occupiedBooking->guest->name ?? '' }}">
                                            Room {{ $occupiedBooking->room->room_number }} — {{ $occupiedBooking->room->roomType->name ?? '' }} ({{ $occupiedBooking->guest->name ?? 'No guest' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Guest (Optional)</label>
                                <select class="form-select" name="guest_id">
                                    <option value="">Walk-in Guest</option>
                                    @foreach($guests as $guest)
                                        <option value="{{ $guest->id }}">{{ $guest->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="booking_id" id="booking_id_hidden" value="">
                        @endif
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-2"></i>Payment</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Payment Account</label>
                            <select class="form-select" name="bank_account_id" id="payment_account">
                                <option value="charge_to_room" data-type="room">Charge to Room</option>
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
                                    <option value="{{ $account->id }}" data-type="{{ $accountType }}">
                                        {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="payment_method" id="payment_method_hidden" value="">
                        </div>

                        {{-- Cash Calculator --}}
                        <div id="cash_calculator_section" class="p-3 bg-light rounded border border-dashed mb-3" style="display:none;">
                            <div class="d-flex align-items-center mb-2">
                                <label class="form-label fs-12 text-muted text-uppercase mb-0 flex-grow-1">Uang Diterima</label>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="customer_paid" id="customer_paid" placeholder="Masukkan uang tamu...">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-13">Kembalian:</span>
                                <span id="change_amount" class="fw-bold text-primary fs-15">Rp 0</span>
                            </div>
                            <div id="insufficient_paid_warning" class="text-danger fs-12 mt-2" style="display:none;">
                                <i class="ri-error-warning-line align-middle"></i> Uang diterima kurang dari total order.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="discount_amount" id="discount_amount" value="0" min="0">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Order Cart --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-shopping-cart-line me-2"></i>Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="cartEmpty" class="text-center text-muted py-4 px-3">
                            <i class="ri-shopping-cart-line fs-2 d-block mb-2"></i>
                            No items added yet.
                        </div>
                        <table class="table table-sm mb-0" id="cartTable" style="display:none;">
                            <tbody id="cartItems"></tbody>
                            <tfoot class="table-light border-top">
                                <tr>
                                    <td colspan="2" class="text-end fw-semibold">Subtotal:</td>
                                    <td class="text-end fw-bold" id="cartSubtotal">Rp 0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end fw-semibold">Discount:</td>
                                    <td class="text-end text-success fw-bold" id="cartDiscount">- Rp 0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end fw-bold fs-14">Total:</td>
                                    <td class="text-end fw-bold fs-14 text-primary" id="cartTotal">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="ri-check-line me-1"></i> Place Order
                            </button>
                            <a href="{{ route('pos.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden cart items container --}}
        <div id="hiddenCartInputs"></div>
    </form>
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#room_select').select2();

            // Keep booking_id in sync with the chosen room so "Charge to Room"
            // has something to attach to -- the select only carries room_id,
            // booking_id lives in the option's data-booking attribute.
            $('#room_select').on('change', function () {
                const selected = $(this).find('option:selected');
                $('#booking_id_hidden').val(selected.data('booking') || '');
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let cart = {};

            const cartItemsEl = document.getElementById('cartItems');
            const cartTableEl = document.getElementById('cartTable');
            const cartEmptyEl = document.getElementById('cartEmpty');
            const cartSubtotalEl = document.getElementById('cartSubtotal');
            const cartDiscountEl = document.getElementById('cartDiscount');
            const cartTotalEl = document.getElementById('cartTotal');
            const submitBtn = document.getElementById('submitBtn');
            const hiddenCartInputs = document.getElementById('hiddenCartInputs');
            const discountInput = document.getElementById('discount_amount');

            function formatRupiah(amount) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
            }

            function renderCart() {
                cartItemsEl.innerHTML = '';
                hiddenCartInputs.innerHTML = '';
                let subtotal = 0;
                let index = 0;

                Object.values(cart).forEach(item => {
                    const lineTotal = item.price * item.qty;
                    subtotal += lineTotal;

                    cartItemsEl.innerHTML += `
                        <tr>
                            <td class="ps-3 fw-medium" style="max-width:120px;">${item.name}</td>
                            <td class="text-center">x${item.qty}</td>
                            <td class="text-end">${formatRupiah(lineTotal)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-soft-danger remove-item-btn" data-id="${item.id}">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>`;

                    hiddenCartInputs.innerHTML += `
                        <input type="hidden" name="items[${index}][inventory_id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][item_name]" value="${item.name}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.qty}">
                        <input type="hidden" name="items[${index}][price_per_unit]" value="${item.price}">`;
                    index++;
                });

                const discount = parseFloat(discountInput.value) || 0;
                const total = Math.max(0, subtotal - discount);

                cartSubtotalEl.textContent = formatRupiah(subtotal);
                cartDiscountEl.textContent = '- ' + formatRupiah(discount);
                cartTotalEl.textContent = formatRupiah(total);

                const hasItems = index > 0;
                cartTableEl.style.display = hasItems ? '' : 'none';
                cartEmptyEl.style.display = hasItems ? 'none' : '';
                submitBtn.disabled = !hasItems;

                if (window._posCalculateChange) window._posCalculateChange();

                cartItemsEl.querySelectorAll('.remove-item-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        delete cart[this.dataset.id];
                        renderCart();
                    });
                });
            }

            document.querySelectorAll('.add-item-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const price = parseFloat(this.dataset.price);
                    const qtyInput = document.getElementById('qty_' + id);
                    const qty = parseInt(qtyInput.value) || 1;

                    if (cart[id]) {
                        cart[id].qty += qty;
                    } else {
                        cart[id] = { id, name, price, qty };
                    }

                    qtyInput.value = 1;
                    renderCart();

                    Toastify({
                        text: `"${name}" added to cart`,
                        duration: 1500,
                        gravity: 'top',
                        position: 'right',
                        style: { background: 'linear-gradient(to right, #0ab39c, #405189)' }
                    }).showToast();
                });
            });

            if (discountInput) {
                discountInput.addEventListener('input', renderCart);
            }

            // Payment account logic
            const paymentAccountSelect = document.getElementById('payment_account');
            const paymentMethodHidden = document.getElementById('payment_method_hidden');
            const cashSection = document.getElementById('cash_calculator_section');
            const customerPaidInput = document.getElementById('customer_paid');
            const changeAmountEl = document.getElementById('change_amount');

            function getCartTotal() {
                let subtotal = 0;
                Object.values(cart).forEach(item => subtotal += item.price * item.qty);
                const discount = parseFloat(discountInput.value) || 0;
                return Math.max(0, subtotal - discount);
            }

            function syncPaymentMethod() {
                if (!paymentAccountSelect) return;
                const selected = paymentAccountSelect.options[paymentAccountSelect.selectedIndex];
                const val = paymentAccountSelect.value;
                const type = selected.dataset.type || '';

                if (val === '') {
                    paymentMethodHidden.value = '';
                    cashSection.style.display = 'none';
                } else if (val === 'charge_to_room') {
                    paymentMethodHidden.value = 'charge_to_room';
                    cashSection.style.display = 'none';
                } else if (type === 'cash') {
                    paymentMethodHidden.value = 'cash';
                    cashSection.style.display = 'block';
                } else if (type === 'qris') {
                    paymentMethodHidden.value = 'qris';
                    cashSection.style.display = 'none';
                } else {
                    paymentMethodHidden.value = 'bank_transfer';
                    cashSection.style.display = 'none';
                }

                if (window._posCalculateChange) window._posCalculateChange();
            }

            function isCashUnderpaid() {
                if (!customerPaidInput || cashSection.style.display === 'none') return false;
                const paid = parseFloat(customerPaidInput.value) || 0;
                return paid < getCartTotal();
            }

            function calculateChange() {
                if (!customerPaidInput) return;
                const total = getCartTotal();
                const paid = parseFloat(customerPaidInput.value) || 0;
                const change = Math.max(0, paid - total);
                changeAmountEl.textContent = formatRupiah(change);
                const underpaid = isCashUnderpaid();
                changeAmountEl.classList.toggle('text-danger', underpaid);
                changeAmountEl.classList.toggle('text-primary', !underpaid);
                document.getElementById('insufficient_paid_warning').style.display = underpaid ? '' : 'none';
                if (submitBtn && cartItemsEl.children.length > 0) {
                    submitBtn.disabled = underpaid;
                }
            }
            window._posCalculateChange = calculateChange;

            if (paymentAccountSelect) {
                paymentAccountSelect.addEventListener('change', syncPaymentMethod);
                syncPaymentMethod();
            }
            if (customerPaidInput) {
                customerPaidInput.addEventListener('input', calculateChange);
            }

            // Search & filter
            const itemSearch = document.getElementById('itemSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const itemRows = document.querySelectorAll('.item-row');

            function filterItems() {
                const searchVal = itemSearch.value.toLowerCase();
                const categoryVal = categoryFilter.value;
                itemRows.forEach(row => {
                    const matchesSearch = row.dataset.name.includes(searchVal);
                    const matchesCategory = !categoryVal || row.dataset.category === categoryVal;
                    row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
                });
            }

            if (itemSearch) itemSearch.addEventListener('input', filterItems);
            if (categoryFilter) categoryFilter.addEventListener('change', filterItems);

            @if(session('error'))
                Toastify({
                    text: "{{ session('error') }}",
                    duration: 3000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    style: { background: 'linear-gradient(to right, #f06548, #f7b84b)' }
                }).showToast();
            @endif
        });
    </script>
@endsection
