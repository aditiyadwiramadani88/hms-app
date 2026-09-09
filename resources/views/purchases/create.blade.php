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

    <form action="{{ route('purchases.store') }}" method="POST" data-ajax="true">
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
                                        <th scope="col" style="width: 150px;">Quantity</th>
                                        <th scope="col" style="width: 200px;">Unit Price</th>
                                        <th scope="col" class="text-end" style="width: 150px;">Subtotal</th>
                                        <th scope="col" class="text-end" style="width: 100px;">Action</th>
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
                placeholder: 'Type to search for an item...',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('api.inventory.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
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

            function addItem(id, name, price) {
                // Prevent adding the same item twice
                if ($(`#item-row-${id}`).length > 0) {
                    alert('Item already added.');
                    return;
                }

                const newRow = `
                    <tr id="item-row-${id}">
                        <td>
                            <input type="hidden" name="items[${itemCounter}][inventory_id]" value="${id}">
                            ${name}
                        </td>
                        <td>
                            <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="1" min="1" step="1">
                        </td>
                        <td>
                             <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control item-price" name="items[${itemCounter}][price]" value="${price}" min="0" step="100">
                             </div>
                        </td>
                        <td class="text-end item-subtotal">
                            ${formatRupiah(price)}
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

            $(document).on('input', '.item-quantity, .item-price', function() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
                const price = parseFloat(row.find('.item-price').val()) || 0;
                const subtotal = quantity * price;
                row.find('.item-subtotal').text(formatRupiah(subtotal));
                updateTotal();
            });

            function updateTotal() {
                let total = 0;
                $('.item-subtotal').each(function() {
                    total += parseRupiah($(this).text());
                });
                $('#total-amount').text(formatRupiah(total));
            }
            
            function formatRupiah(amount) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
            }

            function parseRupiah(rupiahString) {
                return parseFloat(rupiahString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;
            }
        });
    </script>
@endsection
