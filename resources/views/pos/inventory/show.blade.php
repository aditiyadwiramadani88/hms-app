@extends('layouts.master')
@section('title')
    {{ $inventory->name }} - Mutations
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('li_2')
            <a href="{{ route('inventory.index') }}">POS Inventory</a>
        @endslot
        @slot('title')
            {{ $inventory->name }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Item Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Name</td>
                            <td class="fw-bold">{{ $inventory->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Category</td>
                            <td>{{ $inventory->category }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Current Stock</td>
                            <td>
                                <span class="{{ $inventory->stock <= $inventory->min_stock ? 'text-danger' : 'text-success' }} fw-bold">
                                    {{ $inventory->stock }} {{ $inventory->unit }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted align-top">Lokasi Stok</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @php
                                        $hasStocks = $inventory->warehouseStocks && $inventory->warehouseStocks->isNotEmpty();
                                    @endphp
                                    @if($hasStocks)
                                        @foreach($inventory->warehouseStocks as $ws)
                                            <span class="badge {{ ($ws->warehouse->type ?? '') === 'display' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-dark border' }}">
                                                {{ $ws->warehouse->name ?? 'Gudang' }}: {{ $ws->stock }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            ETALASE: {{ $inventory->stock }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Min Stock</td>
                            <td>{{ $inventory->min_stock }} {{ $inventory->unit }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Price</td>
                            <td>Rp {{ number_format($inventory->price_per_unit, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                    <a href="{{ route('inventory.edit', $inventory) }}" class="btn btn-warning w-100 mt-3">Edit Item</a>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Mutation</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('inventory.mutation', $inventory) }}" id="mutationForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Tipe Mutasi</label>
                            <select name="type" id="mutationType" class="form-select" required>
                                <option value="in">Stock In (+)</option>
                                <option value="out">Stock Out (-)</option>
                                <option value="adjustment">Adjustment (Set)</option>
                                <option value="transfer">Mutasi Antar Etalase & Gudang</option>
                            </select>
                        </div>

                        {{-- Normal Warehouse Selection (In/Out/Adjustment) --}}
                        <div class="mb-3" id="singleWarehouseGroup">
                            <label class="form-label">Gudang / Etalase</label>
                            <select name="warehouse_id" class="form-select">
                                @foreach($warehouses as $wh)
                                    @php
                                        $whStock = $inventory->warehouseStocks->where('warehouse_id', $wh->id)->first()?->stock;
                                        if ($whStock === null && $wh->type === 'display') {
                                            $whStock = $inventory->stock;
                                        }
                                    @endphp
                                    <option value="{{ $wh->id }}">
                                        {{ $wh->name }} {{ $wh->type === 'display' ? '(Etalase)' : '' }} (Stok: {{ $whStock ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Transfer Fields (Origin & Destination) --}}
                        <div id="transferWarehouseGroup" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Dari (Asal Mutasi) <span class="text-danger">*</span></label>
                                <select name="from_warehouse_id" id="mutationFromWh" class="form-select">
                                    <option value="">-- Pilih Asal Mutasi --</option>
                                    @foreach($warehouses as $wh)
                                        @php
                                            $whStock = $inventory->warehouseStocks->where('warehouse_id', $wh->id)->first()?->stock;
                                            if ($whStock === null && $wh->type === 'display') {
                                                $whStock = $inventory->stock;
                                            }
                                            $avail = $whStock ?? 0;
                                        @endphp
                                        <option value="{{ $wh->id }}" data-stock="{{ $avail }}">
                                            {{ $wh->name }} {{ $wh->type === 'display' ? '(Etalase)' : '' }} - Tersedia: {{ $avail }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ke (Tujuan Mutasi) <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" id="mutationToWh" class="form-select">
                                    <option value="">-- Pilih Tujuan Mutasi --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">
                                            {{ $wh->name }} {{ $wh->type === 'display' ? '(Etalase)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah (Quantity) <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="mutationQty" class="form-control" min="1" required>
                            <small class="text-muted" id="qtyHint"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan mutasi..."></textarea>
                        </div>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">Simpan Mutasi</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mutation History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>User</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutations ?? [] as $mutation)
                                <tr>
                                    <td>{{ $mutation->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        @switch($mutation->type)
                                            @case('in')
                                            <span class="badge bg-success">IN</span>
                                            @break
                                            @case('out')
                                            <span class="badge bg-danger">OUT</span>
                                            @break
                                            @case('adjustment')
                                            <span class="badge bg-warning">ADJUST</span>
                                            @break
                                            @case('transfer')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">TRANSFER</span>
                                            @break
                                            @default
                                            <span class="badge bg-secondary">{{ strtoupper($mutation->type) }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $mutation->quantity }}</td>
                                    <td>{{ $mutation->user->name ?? '-' }}</td>
                                    <td>{{ $mutation->notes ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No mutations yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $mutations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('mutationType');
            const singleWhGroup = document.getElementById('singleWarehouseGroup');
            const transferWhGroup = document.getElementById('transferWarehouseGroup');
            const fromWhSelect = document.getElementById('mutationFromWh');
            const toWhSelect = document.getElementById('mutationToWh');
            const qtyInput = document.getElementById('mutationQty');
            const qtyHint = document.getElementById('qtyHint');

            function updateFormVisibility() {
                const isTransfer = typeSelect.value === 'transfer';
                if (isTransfer) {
                    singleWhGroup.style.display = 'none';
                    transferWhGroup.style.display = 'block';
                    fromWhSelect.setAttribute('required', 'required');
                    toWhSelect.setAttribute('required', 'required');
                    updateStockLimit();
                } else {
                    singleWhGroup.style.display = 'block';
                    transferWhGroup.style.display = 'none';
                    fromWhSelect.removeAttribute('required');
                    toWhSelect.removeAttribute('required');
                    qtyInput.removeAttribute('max');
                    qtyHint.textContent = '';
                }
            }

            function updateStockLimit() {
                if (typeSelect.value !== 'transfer') return;
                const selectedOption = fromWhSelect.options[fromWhSelect.selectedIndex];
                const availableStock = selectedOption ? parseInt(selectedOption.dataset.stock || 0) : 0;
                
                if (fromWhSelect.value) {
                    qtyInput.max = availableStock;
                    qtyHint.textContent = 'Maksimal transfer: ' + availableStock;
                    if (availableStock <= 0) {
                        qtyHint.className = 'text-danger';
                        qtyHint.textContent = 'Stok di lokasi ini 0, tidak bisa melakukan transfer.';
                    } else {
                        qtyHint.className = 'text-muted';
                    }
                } else {
                    qtyInput.removeAttribute('max');
                    qtyHint.textContent = '';
                }

                // Disable the selected fromWh in toWh
                const currentFromId = fromWhSelect.value;
                Array.from(toWhSelect.options).forEach(opt => {
                    if (opt.value && opt.value === currentFromId) {
                        opt.disabled = true;
                        if (toWhSelect.value === opt.value) {
                            toWhSelect.value = '';
                        }
                    } else {
                        opt.disabled = false;
                    }
                });
            }

            typeSelect.addEventListener('change', updateFormVisibility);
            fromWhSelect.addEventListener('change', updateStockLimit);
            updateFormVisibility();
        });
    </script>
@endsection