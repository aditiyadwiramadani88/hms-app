@extends('layouts.master')
@section('title')
    Buat Transaksi
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('tenant.transactions.index') }}">Transaksi</a>
        @endslot
        @slot('title')
            Buat Transaksi
        @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('tenant.transactions.store') }}" method="POST" id="posForm" enctype="multipart/form-data">
        @csrf
        <div class="row">
            {{-- Left: Product List --}}
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0"><i class="ri-store-line me-2 text-primary"></i>Pilih Produk</h5>
                    </div>
                    <div class="card-body">
                        {{-- Search & Filter --}}
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <div class="search-box">
                                    <input type="text" id="itemSearch" class="form-control" placeholder="Cari nama produk...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <select id="categoryFilter" class="form-select" style="width: 160px;">
                                    <option value="">Semua Kategori</option>
                                    @php $categories = $products->pluck('category')->filter()->unique()->sort(); @endphp
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Product Table --}}
                        <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top fs-11 text-uppercase">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-center" style="width: 100px;">Qty</th>
                                        <th class="text-center" style="width: 80px;">Add</th>
                                    </tr>
                                </thead>
                                <tbody id="productList">
                                    @forelse($products->where('is_active', true) as $product)
                                    <tr class="product-row" data-name="{{ strtolower($product->name) }}" data-category="{{ $product->category ?? '' }}">
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($product->photo)
                                                    <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="rounded" style="width: 36px; height: 36px; object-fit: cover;">
                                                @else
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                                        <i class="ri-image-line text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-medium">{{ $product->name }}</div>
                                                    @if($product->stock !== null)
                                                        <small class="text-muted">Stok: {{ $product->stock }}</small>
                                                    @else
                                                        <small class="text-muted">Stok: ∞</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $product->category ?? '-' }}</span></td>
                                        <td class="text-end fw-semibold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm qty-input text-center" value="1" min="1" id="qty_{{ $product->id }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-success add-item-btn"
                                                data-id="{{ $product->id }}"
                                                data-name="{{ $product->name }}"
                                                data-price="{{ $product->price }}"
                                                data-stock="{{ $product->stock }}">
                                                <i class="ri-add-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada produk. <a href="{{ route('tenant.products.index') }}">Tambah produk</a> dulu.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Cart & Payment --}}
            <div class="col-xl-4">
                {{-- Cart (MOVED UP) --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-shopping-cart-line me-2"></i>Keranjang</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="cartEmpty" class="text-center text-muted py-4 px-3">
                            <i class="ri-shopping-cart-line fs-2 d-block mb-2"></i>
                            Belum ada item. Klik tombol + untuk menambahkan.
                        </div>
                        <table class="table table-sm mb-0" id="cartTable" style="display:none;">
                            <tbody id="cartItems"></tbody>
                            <tfoot class="table-light border-top">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold fs-14">Total:</td>
                                    <td class="text-end fw-bold fs-14 text-primary" id="cartTotal">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Payment Method (MOVED DOWN) --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-2"></i>Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Metode Bayar</label>
                            <select class="form-select" name="payment_method" id="payment_method" required>
                                @php $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(); @endphp
                                @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->code }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Cash Calculator --}}
                        <div id="cashSection" class="p-3 bg-light rounded border border-dashed mb-3">
                            <label class="form-label fs-12 text-muted text-uppercase mb-1">Uang Diterima</label>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="customerPaid" placeholder="0">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-13">Kembalian:</span>
                                <span id="changeAmount" class="fw-bold text-primary fs-15">Rp 0</span>
                            </div>
                        </div>

                        {{-- Payment Proof (non-cash) --}}
                        <div id="proofSection" class="p-3 bg-warning-subtle rounded border border-dashed mb-3" style="display:none;">
                            <label class="form-label fs-12 text-muted text-uppercase mb-1">
                                <i class="ri-camera-line me-1"></i>Bukti Pembayaran (Opsional)
                            </label>
                            <input type="file" name="payment_proof" class="form-control form-control-sm" accept="image/*" id="paymentProofInput">
                            <small class="text-muted">Foto bukti QRIS/transfer (otomatis di-compress).</small>
                            <div id="proofPreview" class="mt-2" style="display:none;">
                                <img id="proofPreviewImg" src="" class="rounded border" style="max-height:80px;">
                                <small class="text-muted d-block" id="proofSizeInfo"></small>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fs-12 text-muted text-uppercase fw-semibold">Catatan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Opsional..."></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                <i class="ri-check-line me-1"></i> Simpan Transaksi
                            </button>
                            <a href="{{ route('tenant.transactions.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1"></i> Batal
                            </a>
                        </div>
                        <div class="text-center mt-3" id="printerSettings">
                            <small class="text-muted">
                                <i class="ri-bluetooth-line me-1"></i>
                                Printer: <strong id="printerName">Belum dipilih</strong>
                            </small>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="btnConnectPrinter" onclick="connectPrinter()" title="Hubungkan printer Bluetooth">
                                <i class="ri-bluetooth-connect-line text-primary"></i>
                            </button>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-danger" id="btnForgetPrinter" style="display:none;" onclick="ThermalPrinter.forget();updatePrinterDisplay()" title="Lupakan printer">
                                <i class="ri-close-line"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm ms-2" id="btnBtPage" style="display:none;" onclick="printLastTransactionBluetooth()">
                                <i class="ri-bluetooth-line me-1"></i> Print via Bluetooth
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="hiddenCartInputs"></div>
    </form>

    {{-- Add-on Selection Modal --}}
    <div class="modal fade" id="addonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title">
                        <i class="ri-add-circle-line me-2 text-warning"></i><span id="addonModalTitle">Pilih Add-on</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="addonModalBody">
                    {{-- Dynamic content --}}
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <span class="text-muted fs-13">Total: </span>
                        <span class="fw-bold text-primary fs-15" id="addonPreviewTotal">Rp 0</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success btn-sm" id="btnConfirmAddon">
                            <i class="ri-shopping-cart-line me-1"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Receipt Modal --}}
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="ri-checkbox-circle-fill text-success" style="font-size: 48px;"></i>
                    </div>
                    <h4 class="mb-1">Transaksi Berhasil!</h4>
                    <p class="text-muted mb-3" id="receiptNumber"></p>
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total</span>
                            <span class="fw-bold fs-16 text-primary" id="receiptTotal"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Metode</span>
                            <span class="fw-medium" id="receiptMethod"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Waktu</span>
                            <span class="fw-medium" id="receiptDate"></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="#" id="receiptPrintLink" target="_blank" class="btn btn-info">
                            <i class="ri-printer-line me-1"></i> Print Receipt
                        </a>
                        <button type="button" class="btn btn-outline-info" id="btnBtModal" onclick="printLastTransactionBluetooth()">
                            <i class="ri-bluetooth-line me-1"></i> Print Bluetooth
                        </button>
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            <i class="ri-add-line me-1"></i> Transaksi Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="{{ asset('js/thermal-printer.js') }}?v={{ filemtime(public_path('js/thermal-printer.js')) }}"></script>
    <script>
        function formatRp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0); }

        let lastTransaction = null;

        function updatePrinterDisplay() {
            const name = ThermalPrinter.getSavedName();
            const el = document.getElementById('printerSettings');
            if (!ThermalPrinter.isSupported()) { el.style.display = 'none'; return; }
            el.style.display = '';
            document.getElementById('printerName').textContent = name || 'Belum dipilih';
            document.getElementById('btnConnectPrinter').style.display = name ? 'none' : '';
            document.getElementById('btnForgetPrinter').style.display = name ? '' : 'none';
            document.getElementById('btnBtPage').style.display = (name && lastTransaction) ? '' : 'none';
        }

        async function connectPrinter() {
            const btn = document.getElementById('btnConnectPrinter');
            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mencari...';
            try {
                await ThermalPrinter.scanAndSave();
                updatePrinterDisplay();
            } catch(e) {
                // user cancelled or error
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        async function printLastTransactionBluetooth() {
            if (!lastTransaction) return;
            const t = lastTransaction;
            const btns = [
                document.getElementById('btnBtPage'),
                document.getElementById('btnBtModal')
            ].filter(Boolean);
            btns.forEach(b => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mencari printer...'; });

            const tenantName = @json($tenant->name ?? 'TENANT');
            const tenantAddr = @json($tenant->location_description ?? '');
            const tenantPhone = @json($tenant->phone ?? '');

            await ThermalPrinter.print({
                tenant_name: tenantName,
                tenant_address: tenantAddr,
                tenant_phone: tenantPhone,
                transaction_number: t.transaction_number || ('#' + t.id),
                datetime: t.created_at || new Date().toLocaleString('id-ID'),
                cashier: @json(auth()->user()->name ?? '-'),
                payment_method: t.payment_method,
                total: formatRp(t.total_amount || 0),
                items: (t.items || []).map(function(item) {
                    const addons = [];
                    if (item.addons_json && item.addons_total > 0) {
                        try {
                            const parsed = typeof item.addons_json === 'string' ? JSON.parse(item.addons_json) : item.addons_json;
                            (parsed || []).forEach(function(a) {
                                addons.push({ name: a.name, price: formatRp((a.price || 0) * (item.quantity || 1)) });
                            });
                        } catch(e) {}
                    }
                    return {
                        name: item.product_name,
                        qty: item.quantity,
                        price: formatRp(item.price || 0),
                        subtotal: formatRp(item.subtotal || 0),
                        addons: addons
                    };
                }),
            }, {
                onStatus: function(msg) {
                    btns.forEach(b => { b.innerHTML = '<i class="ri-bluetooth-line me-1"></i> ' + msg; });
                }
            }).finally(function() {
                btns.forEach(b => { b.disabled = false; b.innerHTML = '<i class="ri-bluetooth-line me-1"></i> Print via Bluetooth'; });
                updatePrinterDisplay();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updatePrinterDisplay();

            // === Add-on data from server ===
            // Each product's addon groups (product-specific + global)
            const productAddons = {};
            @foreach($products as $product)
                @php
                    $allGroups = $product->addonGroups->merge($globalAddonGroups)->sortBy('sort_order')->values();
                @endphp
                @if($allGroups->count() > 0)
                productAddons[{{ $product->id }}] = {!! $allGroups->map(fn($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'type' => $g->type,
                    'is_required' => $g->is_required,
                    'max_selections' => $g->max_selections,
                    'items' => $g->activeItems->map(fn($i) => [
                        'id' => $i->id,
                        'name' => $i->name,
                        'price' => (float) $i->price,
                        'is_default' => $i->is_default,
                    ])->values(),
                ]) !!};
                @endif
            @endforeach

            let cart = {};
            let pendingAddToCart = null; // { id, name, price, qty }

            const cartItemsEl = document.getElementById('cartItems');
            const cartTableEl = document.getElementById('cartTable');
            const cartEmptyEl = document.getElementById('cartEmpty');
            const cartTotalEl = document.getElementById('cartTotal');
            const submitBtn = document.getElementById('submitBtn');
            const hiddenInputs = document.getElementById('hiddenCartInputs');
            const customerPaid = document.getElementById('customerPaid');
            const changeAmount = document.getElementById('changeAmount');
            const cashSection = document.getElementById('cashSection');
            const paymentMethod = document.getElementById('payment_method');
            const addonModalBody = document.getElementById('addonModalBody');
            const addonPreviewTotal = document.getElementById('addonPreviewTotal');
            const addonModalTitle = document.getElementById('addonModalTitle');
            const addonModal = new bootstrap.Modal(document.getElementById('addonModal'));

            // Generate unique key for cart items with addons
            function cartKey(productId, addons) {
                if (!addons || !addons.length) return String(productId);
                const addonIds = addons.map(a => a.id).sort().join(',');
                return productId + '_' + addonIds;
            }

            function getTotal() {
                let total = 0;
                Object.values(cart).forEach(item => {
                    let itemTotal = item.price * item.qty;
                    if (item.addons && item.addons.length) {
                        const addonsCost = item.addons.reduce((sum, a) => sum + a.price, 0);
                        itemTotal += addonsCost * item.qty;
                    }
                    total += itemTotal;
                });
                return total;
            }

            function getItemLineTotal(item) {
                let baseTotal = item.price * item.qty;
                if (item.addons && item.addons.length) {
                    const addonsCost = item.addons.reduce((sum, a) => sum + a.price, 0);
                    baseTotal += addonsCost * item.qty;
                }
                return baseTotal;
            }

            function renderCart() {
                cartItemsEl.innerHTML = '';
                hiddenInputs.innerHTML = '';
                let index = 0;

                Object.values(cart).forEach(item => {
                    const lineTotal = getItemLineTotal(item);

                    // Build addon display
                    let addonHtml = '';
                    if (item.addons && item.addons.length) {
                        item.addons.forEach(a => {
                            addonHtml += `<div class="text-muted fs-11 ps-2">+ ${escapeHtml(a.name)} ${a.price > 0 ? '(Rp ' + new Intl.NumberFormat('id-ID').format(a.price) + ')' : '(Gratis)'}</div>`;
                        });
                    }

                    cartItemsEl.innerHTML += `
                        <tr>
                            <td class="ps-3" style="max-width:140px;">
                                <div class="fw-medium">${escapeHtml(item.name)}</div>
                                ${addonHtml}
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-xs btn-soft-warning qty-minus" data-key="${item.cartKey}" ${item.qty <= 1 ? 'disabled' : ''}>
                                        <i class="ri-subtract-line"></i>
                                    </button>
                                    <span class="fw-medium">${item.qty}</span>
                                    <button type="button" class="btn btn-xs btn-soft-success qty-plus" data-key="${item.cartKey}">
                                        <i class="ri-add-line"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-end">${formatRp(lineTotal)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-soft-danger remove-btn" data-key="${item.cartKey}">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>`;

                    // Hidden inputs for form submission
                    const addonsForSubmit = (item.addons || []).map(a => ({ addon_item_id: a.id, name: a.name, price: a.price }));
                    hiddenInputs.innerHTML += `
                        <input type="hidden" name="items[${index}][tenant_product_id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.qty}">
                        <input type="hidden" name="items[${index}][addons]" value='${JSON.stringify(addonsForSubmit)}'>`;
                    index++;
                });

                const total = getTotal();
                cartTotalEl.textContent = formatRp(total);
                cartTableEl.style.display = index > 0 ? '' : 'none';
                cartEmptyEl.style.display = index > 0 ? 'none' : '';
                submitBtn.disabled = index === 0;
                calculateChange();

                // Bind cart action buttons
                cartItemsEl.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        delete cart[this.dataset.key];
                        renderCart();
                    });
                });

                cartItemsEl.querySelectorAll('.qty-minus').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const key = this.dataset.key;
                        if (cart[key] && cart[key].qty > 1) {
                            cart[key].qty--;
                            renderCart();
                        }
                    });
                });

                cartItemsEl.querySelectorAll('.qty-plus').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const key = this.dataset.key;
                        if (cart[key]) {
                            cart[key].qty++;
                            renderCart();
                        }
                    });
                });
            }

            function calculateChange() {
                const total = getTotal();
                const paid = parseFloat(customerPaid.value) || 0;
                const change = paid - total;
                if (paid === 0) {
                    changeAmount.textContent = 'Rp 0';
                    changeAmount.className = 'fw-bold text-muted fs-15';
                } else if (change < 0) {
                    changeAmount.textContent = 'Kurang ' + formatRp(Math.abs(change));
                    changeAmount.className = 'fw-bold text-danger fs-15';
                } else {
                    changeAmount.textContent = formatRp(change);
                    changeAmount.className = 'fw-bold text-success fs-15';
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // === Add-on Modal ===
            function showAddonModal(productId, productName, productPrice, qty) {
                const groups = productAddons[productId];
                if (!groups || !groups.length) {
                    // No addons — add directly
                    addToCartDirect(productId, productName, productPrice, qty, []);
                    return;
                }

                pendingAddToCart = { id: productId, name: productName, price: productPrice, qty: qty };
                addonModalTitle.textContent = productName + ' — Pilih Add-on';

                // Build modal content
                let html = '';
                groups.forEach(group => {
                    const isRequired = group.is_required;
                    const maxSel = group.max_selections;
                    html += `<div class="mb-3 addon-group" data-group-id="${group.id}" data-type="${group.type}" data-required="${isRequired}" data-max="${maxSel || ''}">`;
                    html += `<div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">${escapeHtml(group.name)}
                            ${isRequired ? '<span class="text-danger">*</span>' : ''}
                            <span class="badge bg-light text-dark ms-1">${group.type === 'single' ? 'Pilih 1' : 'Multi'}</span>
                        </label>
                        ${maxSel ? '<small class="text-muted">Maks ' + maxSel + '</small>' : ''}
                    </div>`;

                    group.items.forEach(item => {
                        const inputType = group.type === 'single' ? 'radio' : 'checkbox';
                        const inputName = group.type === 'single' ? 'addon_group_' + group.id : 'addon_item_' + item.id;
                        const checked = item.is_default ? 'checked' : '';
                        const priceLabel = item.price > 0 ? '+Rp ' + new Intl.NumberFormat('id-ID').format(item.price) : 'Gratis';

                        html += `<div class="form-check mb-1">
                            <input class="form-check-input addon-input" type="${inputType}" name="${inputName}" value="${item.id}"
                                data-group-id="${group.id}" data-item-id="${item.id}" data-name="${escapeHtml(item.name)}" data-price="${item.price}"
                                data-type="${group.type}" ${checked}>
                            <label class="form-check-label d-flex justify-content-between w-100">
                                <span>${escapeHtml(item.name)}</span>
                                <span class="text-primary fw-semibold">${priceLabel}</span>
                            </label>
                        </div>`;
                    });

                    html += '</div>';
                });

                addonModalBody.innerHTML = html;
                updateAddonPreviewTotal();
                addonModal.show();

                // Bind change events for preview total
                addonModalBody.querySelectorAll('.addon-input').forEach(input => {
                    input.addEventListener('change', function() {
                        // For single type radio, uncheck others in same group is automatic
                        // For multi, enforce max_selections
                        const group = this.closest('.addon-group');
                        const max = parseInt(group.dataset.max) || 0;
                        const type = group.dataset.type;
                        if (type === 'multi' && max > 0) {
                            const checked = group.querySelectorAll('.addon-input:checked');
                            if (checked.length > max) {
                                this.checked = false;
                                alert('Maksimal ' + max + ' pilihan untuk grup ini.');
                                return;
                            }
                        }
                        updateAddonPreviewTotal();
                    });
                });
            }

            function updateAddonPreviewTotal() {
                if (!pendingAddToCart) return;
                const baseTotal = pendingAddToCart.price * pendingAddToCart.qty;
                let addonsTotal = 0;

                addonModalBody.querySelectorAll('.addon-input:checked').forEach(input => {
                    addonsTotal += parseFloat(input.dataset.price) || 0;
                });
                addonsTotal *= pendingAddToCart.qty;

                addonPreviewTotal.textContent = formatRp(baseTotal + addonsTotal);
            }

            function getSelectedAddons() {
                const addons = [];
                const seenGroups = {};

                addonModalBody.querySelectorAll('.addon-input:checked').forEach(input => {
                    const groupId = input.dataset.groupId;
                    const type = input.dataset.type;

                    if (type === 'single') {
                        // Only one per group (radio)
                        if (!seenGroups[groupId]) {
                            seenGroups[groupId] = true;
                            addons.push({
                                id: parseInt(input.dataset.itemId),
                                name: input.dataset.name,
                                price: parseFloat(input.dataset.price) || 0,
                            });
                        }
                    } else {
                        addons.push({
                            id: parseInt(input.dataset.itemId),
                            name: input.dataset.name,
                            price: parseFloat(input.dataset.price) || 0,
                        });
                    }
                });

                return addons;
            }

            function validateAddonSelection() {
                const groups = addonModalBody.querySelectorAll('.addon-group');
                for (const group of groups) {
                    const isRequired = group.dataset.required === '1' || group.dataset.required === 'true';
                    if (isRequired) {
                        const checked = group.querySelectorAll('.addon-input:checked');
                        if (checked.length === 0) {
                            const label = group.querySelector('.form-label')?.textContent || 'Grup ini';
                            alert(label.trim() + ' wajib dipilih.');
                            return false;
                        }
                    }
                }
                return true;
            }

            document.getElementById('btnConfirmAddon').addEventListener('click', function() {
                if (!validateAddonSelection()) return;
                if (!pendingAddToCart) return;

                const addons = getSelectedAddons();
                addToCartDirect(pendingAddToCart.id, pendingAddToCart.name, pendingAddToCart.price, pendingAddToCart.qty, addons);
                addonModal.hide();
                pendingAddToCart = null;
            });

            function addToCartDirect(id, name, price, qty, addons) {
                const key = cartKey(id, addons);

                if (cart[key]) {
                    cart[key].qty += qty;
                } else {
                    cart[key] = { id, name, price, qty, addons: addons || [], cartKey: key };
                }

                renderCart();

                let toastText = `"${name}" ditambahkan`;
                if (addons && addons.length) {
                    toastText += ' (+' + addons.length + ' add-on)';
                }
                Toastify({
                    text: toastText,
                    duration: 1500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: 'linear-gradient(to right, #0ab39c, #405189)' }
                }).showToast();
            }

            // Add item buttons — check for addons
            document.querySelectorAll('.add-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    const name = this.dataset.name;
                    const price = parseFloat(this.dataset.price);
                    const qtyInput = document.getElementById('qty_' + id);
                    const qty = parseInt(qtyInput.value) || 1;

                    qtyInput.value = 1;

                    if (productAddons[id] && productAddons[id].length) {
                        showAddonModal(id, name, price, qty);
                    } else {
                        addToCartDirect(id, name, price, qty, []);
                    }
                });
            });

            // Cash calculator
            customerPaid.addEventListener('input', calculateChange);

            // Show/hide cash section and proof section
            function isCashMethod(val) {
                var v = (val || '').toLowerCase();
                return v === 'cash' || v.indexOf('tunai') > -1;
            }
            paymentMethod.addEventListener('change', function() {
                var cash = isCashMethod(this.value);
                cashSection.style.display = cash ? 'block' : 'none';
                document.getElementById('proofSection').style.display = !cash ? 'block' : 'none';
            });
            // Initial state
            (function() {
                var cash = isCashMethod(paymentMethod.value);
                cashSection.style.display = cash ? 'block' : 'none';
                document.getElementById('proofSection').style.display = !cash ? 'block' : 'none';
            })();

            // Image compression for payment proof
            var compressedProofBlob = null;
            document.getElementById('paymentProofInput').addEventListener('change', function(e) {
                var file = e.target.files[0];
                compressedProofBlob = null;
                var preview = document.getElementById('proofPreview');
                if (!file) { preview.style.display = 'none'; return; }

                var reader = new FileReader();
                reader.onload = function(ev) {
                    var img = new Image();
                    img.onload = function() {
                        var maxWidth = 1200, maxHeight = 1200;
                        var width = img.width, height = img.height;
                        if (width > maxWidth || height > maxHeight) {
                            var ratio = Math.min(maxWidth / width, maxHeight / height);
                            width = Math.round(width * ratio);
                            height = Math.round(height * ratio);
                        }
                        var canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob(function(blob) {
                            compressedProofBlob = blob;
                            var sizeKB = Math.round(blob.size / 1024);
                            document.getElementById('proofPreviewImg').src = URL.createObjectURL(blob);
                            document.getElementById('proofSizeInfo').textContent = width + 'x' + height + ' • ' + sizeKB + ' KB';
                            preview.style.display = 'block';
                        }, 'image/jpeg', 0.7);
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            });

            // Form submit — custom AJAX with receipt modal
            document.getElementById('posForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (Object.keys(cart).length === 0) {
                    alert('Tambahkan minimal 1 item ke keranjang.');
                    return false;
                }

                // Cash validation
                if (isCashMethod(paymentMethod.value)) {
                    const total = getTotal();
                    const paid = parseFloat(customerPaid.value) || 0;
                    if (paid < total) {
                        alert('Uang diterima (Rp ' + new Intl.NumberFormat('id-ID').format(paid) + ') kurang dari total tagihan (Rp ' + new Intl.NumberFormat('id-ID').format(total) + ')');
                        customerPaid.focus();
                        return false;
                    }
                }

                // Disable button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Memproses...';

                // Submit via AJAX
                const formData = new FormData(this);
                // Replace payment_proof with compressed version
                if (compressedProofBlob) {
                    formData.delete('payment_proof');
                    formData.append('payment_proof', compressedProofBlob, 'proof.jpg');
                }
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showReceiptModal(res.data);
                        cart = {};
                        renderCart();
                        customerPaid.value = '';
                        calculateChange();
                        document.querySelector('textarea[name="notes"]').value = '';
                        var proofInput = document.getElementById('paymentProofInput');
                        if (proofInput) proofInput.value = '';
                        compressedProofBlob = null;
                        document.getElementById('proofPreview').style.display = 'none';
                        Toastify({ text: '✅ Transaksi berhasil!', duration: 3000, gravity: 'top', position: 'right', style: { background: '#0ab39c' } }).showToast();
                    } else {
                        alert(res.message || 'Gagal menyimpan transaksi.');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan Transaksi';
                })
                .catch(err => {
                    alert('Network error. Coba lagi.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan Transaksi';
                });
            });

            function showReceiptModal(transaction) {
                lastTransaction = transaction;
                const trxNumber = transaction.transaction_number || ('#' + transaction.id);
                const total = transaction.total_amount || 0;
                const method = transaction.payment_method || 'cash';

                document.getElementById('receiptNumber').textContent = trxNumber;
                document.getElementById('receiptTotal').textContent = formatRp(total);
                document.getElementById('receiptMethod').textContent = method.toUpperCase();
                document.getElementById('receiptDate').textContent = new Date().toLocaleString('id-ID');
                document.getElementById('receiptPrintLink').href = '/admin/tenant/transactions/' + transaction.id + '/print';

                updatePrinterDisplay();

                var receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            }

            // Search & filter
            const itemSearch = document.getElementById('itemSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const productRows = document.querySelectorAll('.product-row');

            function filterProducts() {
                const search = itemSearch.value.toLowerCase();
                const category = categoryFilter.value;
                productRows.forEach(row => {
                    const matchName = row.dataset.name.includes(search);
                    const matchCat = !category || row.dataset.category === category;
                    row.style.display = (matchName && matchCat) ? '' : 'none';
                });
            }

            itemSearch.addEventListener('input', filterProducts);
            categoryFilter.addEventListener('change', filterProducts);
        });

        // Override save/forget to auto-refresh UI
        const _origSave = ThermalPrinter.saveName.bind(ThermalPrinter);
        ThermalPrinter.saveName = function(name) { _origSave(name); updatePrinterDisplay(); };
        const _origForget = ThermalPrinter.forget.bind(ThermalPrinter);
        ThermalPrinter.forget = function() { _origForget(); updatePrinterDisplay(); };
    </script>
@endsection
