@extends('layouts.master')
@section('title')
    POS Inventory
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('title')
            POS Inventory
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">POS Inventory</h5>
                    <a href="{{ route('inventory.create') }}" class="btn btn-success">
                        <i class="ri-add-line align-bottom"></i> Add Item
                    </a>
                </div>
                <div class="card-body">
                    <form id="filter-form" method="GET" class="row g-3 mb-4" action="{{ route('inventory.index') }}">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search item..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-secondary w-100">Filter</button>
                        </div>
                    </form>

                    <div id="inventory-list">
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Gudang</th>
                                        <th>Min Stock</th>
                                        <th>Unit</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventories ?? [] as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->inventoryCategory->name ?? '-' }}</td>
                                        <td>
                                            <span class="{{ $item->stock <= $item->min_stock ? 'text-danger fw-bold' : '' }}">
                                                {{ $item->stock }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($item->warehouseStocks->isNotEmpty())
                                                @foreach($item->warehouseStocks as $ws)
                                                    <span class="badge bg-light text-dark border me-1 mb-1">
                                                        {{ $ws->warehouse->name ?? '?' }}: {{ $ws->stock }}
                                                    </span>
                                                @endforeach
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#quickTransferModal{{ $item->id }}" title="Pindah Stok">
                                                    <i class="ri-arrow-left-right-line"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->min_stock }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-secondary">Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                                        <td class="fw-bold">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->stock <= $item->min_stock)
                                            <span class="badge bg-danger">Low Stock</span>
                                            @else
                                            <span class="badge bg-success">In Stock</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('inventory.show', $item) }}" class="btn btn-sm btn-info">Mutations</a>
                                            <a href="{{ route('inventory.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('inventory.destroy', $item) }}" method="POST" data-ajax="true" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No inventory items found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end">Total ({{ $totals->count }} items)</td>
                                        <td>{{ $totals->stock }}</td>
                                        <td></td>
                                        <td>{{ $totals->min_stock }}</td>
                                        <td colspan="5"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        {{ $inventories->withQueryString()->links() }}

                        @foreach($inventories ?? [] as $item)
                            @if($item->warehouseStocks->isNotEmpty())
                            <div class="modal fade" id="quickTransferModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('warehouse.transfer.store') }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Quick Transfer: {{ $item->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2">
                                                    <small class="text-muted">Stok saat ini:</small>
                                                    @foreach($item->warehouseStocks as $ws)
                                                        <span class="badge bg-light text-dark border me-1">{{ $ws->warehouse->name ?? '?' }}: {{ $ws->stock }}</span>
                                                    @endforeach
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Dari Gudang</label>
                                                    <select name="from_warehouse_id" class="form-select" required>
                                                        @if($item->warehouseStocks->count() > 1)
                                                            <option value="">-- Pilih --</option>
                                                        @endif
                                                        @foreach($item->warehouseStocks as $ws)
                                                            <option value="{{ $ws->warehouse_id }}" {{ $item->warehouseStocks->count() == 1 ? 'selected' : '' }}>{{ $ws->warehouse->name ?? '?' }} (Stok: {{ $ws->stock }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Ke Gudang</label>
                                                    <select name="to_warehouse_id" class="form-select" required>
                                                        <option value="">-- Pilih --</option>
                                                        @foreach($warehouses as $wh)
                                                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->type == 'display' ? 'Etalase' : 'Gudang' }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Jumlah Pindah</label>
                                                    <input type="number" name="items[0][quantity]" class="form-control" required min="1" value="1">
                                                </div>
                                                <input type="hidden" name="items[0][inventory_id]" value="{{ $item->id }}">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary" data-submit-protect="true">Pindahkan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
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

        // AJAX for Filtering and Pagination
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const listContainer = document.getElementById('inventory-list');

            function fetchInventory(url) {
                listContainer.style.opacity = '0.5';

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newList = doc.getElementById('inventory-list');
                    if (newList) {
                        listContainer.innerHTML = newList.innerHTML;
                    }
                    listContainer.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching inventory:', error);
                    listContainer.style.opacity = '1';
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const url = new URL(form.action || window.location.href);
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                url.search = params.toString();

                window.history.pushState({}, '', url);
                fetchInventory(url);
            });

            document.addEventListener('click', function(e) {
                const target = e.target.closest('.pagination a');
                if (target) {
                    e.preventDefault();
                    const url = target.href;
                    window.history.pushState({}, '', url);
                    fetchInventory(url);
                }
            });

            window.addEventListener('popstate', function() {
                fetchInventory(window.location.href);
                
                const params = new URLSearchParams(window.location.search);
                form.querySelector('input[name="search"]').value = params.get('search') || '';
                form.querySelector('select[name="category"]').value = params.get('category') || '';
            });
            
            // Auto submit form on select change
            form.querySelector('select[name="category"]').addEventListener('change', function() {
                form.dispatchEvent(new Event('submit'));
            });
        });
    </script>
@endsection