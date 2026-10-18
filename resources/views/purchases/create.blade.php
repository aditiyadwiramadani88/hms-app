@extends('layouts.master')
@section('title')
    Create Purchase Order
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Purchases
        @endslot
        @slot('title')
            Create Purchase Order
        @endslot
    @endcomponent

    <form action="{{ route('purchases.store') }}" method="POST" data-ajax="true" data-ajax-redirect="{{ route('purchases.index') }}" id="createPurchaseForm">
        @csrf
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Purchase Order Details</h5>
                    </div>
                    <div class="card-body">
                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id" required>
                                    <option value="">Select a supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control flatpickr-input @error('purchase_date') is-invalid @enderror" 
                                       id="purchase_date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                         <h5 class="card-title mb-0"><i class="ri-add-circle-line me-2"></i>Add Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-12 mb-3">
                                <label for="item_search" class="form-label">Search Inventory Item</label>
                                <select class="form-control" id="item_search"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                     <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-list-check me-2"></i>Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-nowrap table-borderless mb-0" id="items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col" style="width: 140px;">Quantity</th>
                                        <th scope="col" style="width: 260px;">Unit Price (Total)</th>
                                        <th scope="col" class="text-end" style="width: 150px;">Subtotal</th>
                                        <th scope="col" class="text-end" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="items-list">
                                    <!-- Dynamic items will be appended here -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total</td>
                                        <td class="text-end fw-bold" id="total-amount">Rp 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                 <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="purchase_status" class="form-label">Set Status</label>
                             <select class="form-select" name="status" id="purchase_status">
                                <option value="draft" selected>Draft</option>
                                <option value="received">Received</option>
                            </select>
                        </div>
                        <div class="mb-3" id="warehouse_field" style="display: none;">
                            <label for="warehouse_id" class="form-label">Penempatan / Gudang <span class="text-danger">*</span></label>
                            <select class="form-select" name="warehouse_id" id="warehouse_id">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Wajib diisi kalau status langsung Received.</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                <i class="ri-save-line me-1 align-bottom"></i> Save Purchase
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('.flatpickr-input');
            $('#supplier_id').select2();

            var statusSelect = document.getElementById('purchase_status');
            var warehouseField = document.getElementById('warehouse_field');
            var warehouseSelect = document.getElementById('warehouse_id');
            function toggleWarehouseField() {
                var isReceived = statusSelect.value === 'received';
                warehouseField.style.display = isReceived ? '' : 'none';
                warehouseSelect.required = isReceived;
            }
            statusSelect.addEventListener('change', toggleWarehouseField);
            toggleWarehouseField();

            let itemCounter = 0;

            $('#item_search').select2({
                placeholder: 'Cari atau pilih item inventaris...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('api.inventory.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term || '',
                            _token: "{{ csrf_token() }}",
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on('select2:select', function(e) {
                var data = e.params.data;
                addItem(data.id, data.text, data.price);
                $(this).val(null).trigger('change');
            });

            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
            }

            function formatRupiah(amount) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);
            }

            function addItem(id, name, price) {
                // Prevent adding the same item twice
                if ($(`#item-row-${id}`).length > 0) {
                    alert('Item sudah ditambahkan ke daftar.');
                    return;
                }

                const numericPrice = parseFloat(price) || 0;

                const newRow = `
                    <tr id="item-row-${id}" data-base-price="${numericPrice}">
                        <td>
                            <input type="hidden" name="items[${itemCounter}][inventory_id]" value="${id}">
                            <input type="hidden" name="items[${itemCounter}][is_total_price]" value="1">
                            <input type="hidden" class="item-unit-price-hidden" name="items[${itemCounter}][unit_price]" value="${numericPrice}">
                            <span class="fw-medium">${name}</span>
                            <div class="text-muted fs-11 mt-1">
                                <span class="badge bg-light text-secondary border">Harga Satuan: Rp ${formatNumber(numericPrice)}</span>
                            </div>
                        </td>
                        <td>
                            <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="1" min="1" step="1">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control item-price" name="items[${itemCounter}][price]" value="${numericPrice}" min="0" step="100">
                                <button type="button" class="btn btn-outline-secondary btn-reset-price" title="Kembalikan ke hitungan harga master" style="display:none;">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted item-calc-info fs-11">Auto: 1 × Rp ${formatNumber(numericPrice)}</small>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle item-manual-badge fs-10" style="display:none;">Edit Manual</span>
                            </div>
                        </td>
                        <td class="text-end item-subtotal fw-semibold">
                            ${formatRupiah(numericPrice)}
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-danger remove-item">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#items-list').append(newRow);
                itemCounter++;
                updateTotal();
            }

            $(document).on('click', '.remove-item', function() {
                $(this).closest('tr').remove();
                updateTotal();
            });

            // Recalculate price when quantity changes
            $(document).on('input change', '.item-quantity', function() {
                const row = $(this).closest('tr');
                const quantity = Math.max(1, parseFloat(row.find('.item-quantity').val()) || 1);
                const basePrice = parseFloat(row.data('base-price')) || 0;
                const isManual = row.data('is-manual') === true;

                if (isManual) {
                    const manualUnitRate = parseFloat(row.data('manual-unit-rate')) || basePrice;
                    const newTotal = Math.round(manualUnitRate * quantity);
                    row.find('.item-price').val(newTotal);
                    row.find('.item-unit-price-hidden').val(manualUnitRate);
                    row.find('.item-calc-info').text('Manual: ' + quantity + ' × Rp ' + formatNumber(manualUnitRate));
                } else {
                    const newTotal = Math.round(basePrice * quantity);
                    row.find('.item-price').val(newTotal);
                    row.find('.item-unit-price-hidden').val(basePrice);
                    row.find('.item-calc-info').text('Auto: ' + quantity + ' × Rp ' + formatNumber(basePrice));
                }

                updateTotal();
            });

            // Handle manual price editing
            $(document).on('input change', '.item-price', function() {
                const row = $(this).closest('tr');
                const quantity = Math.max(1, parseFloat(row.find('.item-quantity').val()) || 1);
                const enteredTotal = parseFloat(row.find('.item-price').val()) || 0;
                const basePrice = parseFloat(row.data('base-price')) || 0;
                const autoExpected = Math.round(basePrice * quantity);

                if (Math.abs(enteredTotal - autoExpected) > 0.01) {
                    row.data('is-manual', true);
                    const unitRate = quantity > 0 ? (enteredTotal / quantity) : enteredTotal;
                    row.data('manual-unit-rate', unitRate);
                    row.find('.item-unit-price-hidden').val(unitRate);
                    row.find('.item-manual-badge').show();
                    row.find('.btn-reset-price').show();
                    row.find('.item-calc-info').text('Manual: @ Rp ' + formatNumber(unitRate) + ' / unit');
                } else {
                    row.data('is-manual', false);
                    row.removeData('manual-unit-rate');
                    row.find('.item-unit-price-hidden').val(basePrice);
                    row.find('.item-manual-badge').hide();
                    row.find('.btn-reset-price').hide();
                    row.find('.item-calc-info').text('Auto: ' + quantity + ' × Rp ' + formatNumber(basePrice));
                }

                updateTotal();
            });

            // Reset manual price back to auto master calculation
            $(document).on('click', '.btn-reset-price', function() {
                const row = $(this).closest('tr');
                const quantity = Math.max(1, parseFloat(row.find('.item-quantity').val()) || 1);
                const basePrice = parseFloat(row.data('base-price')) || 0;
                const autoTotal = Math.round(basePrice * quantity);

                row.data('is-manual', false);
                row.removeData('manual-unit-rate');
                row.find('.item-price').val(autoTotal);
                row.find('.item-unit-price-hidden').val(basePrice);
                row.find('.item-manual-badge').hide();
                row.find('.btn-reset-price').hide();
                row.find('.item-calc-info').text('Auto: ' + quantity + ' × Rp ' + formatNumber(basePrice));

                updateTotal();
            });

            function updateTotal() {
                let total = 0;
                $('#items-list tr').each(function() {
                    const row = $(this);
                    const lineTotal = parseFloat(row.find('.item-price').val()) || 0;
                    row.find('.item-subtotal').text(formatRupiah(lineTotal));
                    total += lineTotal;
                });
                $('#total-amount').text(formatRupiah(total));
            }

            // Validate that items exist before submit
            $('#createPurchaseForm').on('submit', function(e) {
                if ($('#items-list tr').length === 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    alert('Silakan tambahkan minimal 1 item produk/inventaris sebelum menyimpan Purchase Order.');
                    return false;
                }
            });
        });
    </script>
@endsection
