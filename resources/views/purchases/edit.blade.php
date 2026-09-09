@extends('layouts.master')
@section('title')
    Edit Purchase Order {{ $purchase->purchase_number }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Purchases
        @endslot
        @slot('title')
            Edit Purchase Order
        @endslot
    @endcomponent

    <form action="{{ route('purchases.update', $purchase) }}" method="POST" data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Purchase Order Details</h5>
                    </div>
                    <div class="card-body">
                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" value="{{ $purchase->supplier->name ?? 'N/A' }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}" readonly>
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
                            <table class="table table-nowrap table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Unit Price</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items as $item)
                                    <tr>
                                        <td>{{ $item->inventory->name ?? 'Unknown Item' }}</td>
                                        <td>{{ $item->quantity }} {{ $item->inventory->unit ?? '' }}</td>
                                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                 <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="purchase_status" class="form-label">Update Status</label>
                             <select class="form-select" name="status" id="purchase_status" {{ $purchase->status == 'received' ? 'disabled' : '' }}>
                                <option value="draft" {{ $purchase->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="received" {{ $purchase->status == 'received' ? 'selected' : '' }}>Received</option>
                            </select>
                            @if($purchase->status == 'received')
                                <input type="hidden" name="status" value="received">
                                <small class="text-muted mt-2 d-block">Received purchase orders cannot be changed back to draft.</small>
                            @endif
                        </div>
                        @if($purchase->status == 'received')
                        <div class="mb-3">
                            <label class="form-label">Penempatan / Gudang</label>
                            <input type="text" class="form-control" value="{{ $purchase->warehouse->name ?? '-' }}" readonly>
                        </div>
                        @else
                        <div class="mb-3" id="warehouse_field">
                            <label for="warehouse_id" class="form-label">Penempatan / Gudang <span class="text-danger">*</span></label>
                            <select class="form-select" name="warehouse_id" id="warehouse_id">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Wajib diisi saat status diubah ke Received.</small>
                        </div>
                        @endif
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary" {{ $purchase->status == 'received' ? 'disabled' : '' }}>
                                <i class="ri-save-line me-1 align-bottom"></i> Update Status
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@if($purchase->status != 'received')
@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
@endsection
@endif
