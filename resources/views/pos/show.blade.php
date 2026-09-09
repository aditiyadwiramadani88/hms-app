@extends('layouts.master')
@section('title')
    POS Order #{{ $order->order_number }}
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            POS
        @endslot
        @slot('title')
            Order Details
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Order {{ $order->order_number }}</h5>
                    <div class="flex-shrink-0">
                        @if($order->payment_status === 'unpaid' && $order->status !== 'cancelled')
                            <button type="button" class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="ri-add-line align-middle me-1"></i> Add Item
                            </button>
                        @endif
                        <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }} text-uppercase ms-1">
                            {{ $order->payment_status }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">Product Details</th>
                                    <th scope="col">Item Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col" class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <h5 class="fs-14 text-body">{{ $item->item_name }}</h5>
                                                <p class="text-muted mb-0">Category: <span class="fw-medium">{{ $item->inventory?->category?->name ?? 'N/A' }}</span></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                <tr class="border-top border-top-dashed">
                                    <td colspan="2"></td>
                                    <td colspan="2" class="fw-medium p-0">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td>Sub Total :</td>
                                                    <td class="text-end">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                                                </tr>
                                                @if($order->discount_amount > 0)
                                                <tr>
                                                    <td>Discount :</td>
                                                    <td class="text-end text-success">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                                </tr>
                                                @endif
                                                <tr class="border-top border-top-dashed">
                                                    <th scope="row">Total :</th>
                                                    <th class="text-end">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            @if($order->notes)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">{{ $order->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-xl-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Info</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title rounded bg-soft-primary text-primary fs-16">
                                    {{ substr($order->guest->name ?? 'C', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fs-14 mb-1">{{ $order->guest->name ?? 'Walk-in Customer' }}</h6>
                            <p class="text-muted mb-0">Customer</p>
                        </div>
                    </div>
                    @if($order->booking_id)
                    <div class="alert alert-info border-0 mb-0">
                        <i class="ri-hotel-bed-line me-2"></i> Linked to <strong>Room {{ $order->booking->room->room_number ?? 'N/A' }}</strong>
                        <div class="mt-2">
                            <a href="{{ route('bookings.show', $order->booking_id) }}" class="btn btn-sm btn-info w-100">View Booking</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($order->payment_status === 'unpaid' && $order->status !== 'cancelled')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="ri-money-dollar-circle-line me-1"></i> Process Payment
                            </button>
                        @endif
                        <a href="{{ route('pos.print', $order->id) }}" target="_blank" class="btn btn-soft-secondary">
                            <i class="ri-printer-line me-1"></i> Print Receipt
                        </a>
                        <a href="{{ route('pos.index') }}" class="btn btn-soft-primary">
                            <i class="ri-arrow-left-line me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Audit Info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <span class="text-muted">Created By:</span><br>
                            <span class="fw-medium">{{ $order->user->name ?? 'System' }}</span>
                        </li>
                        <li>
                            <span class="text-muted">Date:</span><br>
                            <span class="fw-medium">{{ $order->created_at->format('d M Y, H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title">
                        <i class="ri-shopping-basket-2-line me-1"></i> Add Items to POS Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Search & Filter Bar --}}
                    <div class="p-3 border-bottom bg-light">
                        <div class="row g-2">
                            <div class="col">
                                <div class="search-box">
                                    <input type="text" id="itemSearch" class="form-control" placeholder="Search item name...">
                                </div>
                            </div>
                            <div class="col-auto">
                                <select id="categoryFilter" class="form-select" style="width: 150px;">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Models\InventoryCategory::where('is_active', true)->get() as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Item List --}}
                    <div class="item-list-container" style="max-height: 400px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-3">Item Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center" style="width: 100px;">Qty</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryList">
                                    @foreach($inventoryItems as $item)
                                    <tr class="item-row" data-name="{{ strtolower($item->name) }}" data-category="{{ $item->category?->name ?? 'Uncategorized' }}">
                                        <td class="ps-3">
                                            <div class="fw-medium">{{ $item->name }}</div>
                                            <small class="text-muted">{{ $item->unit }} | Stock: {{ $item->stock }}</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $item->category?->name ?? '-' }}</span></td>
                                        <td class="text-end fw-semibold text-primary">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm qty-input" value="1" min="1" id="qty_{{ $item->id }}">
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('pos.add-item', $order->id) }}" method="POST" data-ajax="true">
                                                @csrf
                                                <input type="hidden" name="inventory_id" value="{{ $item->id }}">
                                                <input type="hidden" name="quantity" class="final-qty" value="1">
                                                <button type="submit" data-submit-protect="true" class="btn btn-sm btn-success">
                                                    <i class="ri-add-fill"></i> Add
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    @if($order->payment_status === 'unpaid')
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="ri-money-dollar-circle-line me-1"></i> Process POS Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pos.payment', $order->id) }}" method="POST" data-ajax="true" id="formProcessPayment">
                    @csrf
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</h2>
                            <p class="text-muted">Total Amount Due</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Payment Account</label>
                            <select class="form-select" name="bank_account_id" id="payment_account" required>
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
                                        {{ $account->name }} (Balance: Rp {{ number_format($account->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Amount to Pay</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" id="amount_to_pay" 
                                       value="{{ $order->total_amount }}" required>
                            </div>
                        </div>

                        {{-- Cash Calculator Section --}}
                        <div id="cash_calculator_section" class="p-3 bg-light rounded border mb-3">
                            <h6 class="fs-13 mb-3 text-uppercase fw-bold"><i class="ri-calculator-line me-1"></i> Cashier Calculator</h6>
                            <div class="mb-2">
                                <label class="form-label fs-12 text-muted">Customer Paid</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="customer_paid" placeholder="0">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-top-dashed">
                                <span class="fs-13 fw-medium">Change (Kembalian)</span>
                                <span class="fs-16 fw-bold text-primary" id="change_amount">Rp 0</span>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Notes</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="e.g. POS Order #{{ $order->order_number }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toast Notifications
            @if(session('success'))
                Toastify({
                    text: "{{ session('success') }}",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: "linear-gradient(to right, #0ab39c, #405189)",
                    }
                }).showToast();
            @endif

            @if(session('error'))
                Toastify({
                    text: "{{ session('error') }}",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: "linear-gradient(to right, #f06548, #f7b84b)",
                    }
                }).showToast();
            @endif

            const amountInput = document.getElementById('amount_to_pay');
            const paidInput = document.getElementById('customer_paid');
            const changeDisplay = document.getElementById('change_amount');
            const accountSelect = document.getElementById('payment_account');
            const cashSection = document.getElementById('cash_calculator_section');

            function formatRupiah(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount).replace('IDR', 'Rp');
            }

            function calculateChange() {
                if (!amountInput || !paidInput) return;
                const toPay = parseFloat(amountInput.value) || 0;
                const paid = parseFloat(paidInput.value) || 0;
                
                if (paid >= toPay) {
                    const change = paid - toPay;
                    changeDisplay.textContent = formatRupiah(change);
                    changeDisplay.classList.remove('text-danger');
                    changeDisplay.classList.add('text-primary');
                } else {
                    changeDisplay.textContent = 'Rp 0';
                    if (paid > 0) {
                        changeDisplay.classList.add('text-danger');
                        changeDisplay.classList.remove('text-primary');
                    }
                }
            }

            function toggleCashCalculator() {
                if (!accountSelect || !cashSection) return;
                const selectedOption = accountSelect.options[accountSelect.selectedIndex];
                const isCash = selectedOption.getAttribute('data-type') === 'cash';
                cashSection.style.display = isCash ? 'block' : 'none';
            }

            if (amountInput) {
                amountInput.addEventListener('input', calculateChange);
                amountInput.addEventListener('keyup', calculateChange);
            }
            if (paidInput) {
                paidInput.addEventListener('input', calculateChange);
                paidInput.addEventListener('keyup', calculateChange);
            }
            if (accountSelect) {
                accountSelect.addEventListener('change', toggleCashCalculator);
                toggleCashCalculator();
            }

            // Item Search & Filter Logic
            const itemSearch = document.getElementById('itemSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const itemRows = document.querySelectorAll('.item-row');

            function filterItems() {
                if (!itemSearch) return;
                const searchVal = itemSearch.value.toLowerCase();
                const categoryVal = categoryFilter.value.toLowerCase();

                itemRows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const category = row.getAttribute('data-category').toLowerCase();
                    
                    const matchesSearch = name.includes(searchVal);
                    const matchesCategory = !categoryVal || category === categoryVal;

                    row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
                });
            }

            if(itemSearch) itemSearch.addEventListener('input', filterItems);
            if(categoryFilter) categoryFilter.addEventListener('change', filterItems);

            // Sync Qty Input to Hidden Field
            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    row.querySelector('.final-qty').value = this.value;
                });
            });
        });
    </script>
@endsection
