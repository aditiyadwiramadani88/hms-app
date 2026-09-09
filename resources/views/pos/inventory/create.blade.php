@extends('layouts.master')
@section('title')
    Add Inventory Item
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('li_2')
            POS Inventory
        @endslot
        @slot('title')
            Add Item
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            {{-- Error Alerts --}}
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Inventory Item</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('inventory.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Foto Produk</label>
                            <input type="file" name="image" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <small class="text-muted">Max 2MB. Format: JPG, PNG, WebP</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Initial Stock</label>
                                <input type="number" name="stock" class="form-control" min="0" value="{{ old('stock', 0) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Stock Alert</label>
                                <input type="number" name="min_stock" class="form-control" min="0" value="{{ old('min_stock', 10) }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" class="form-control" value="{{ old('unit') }}" placeholder="e.g. pcs, pack, liter" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Harga Beli (Rp)</label>
                                <input type="number" name="purchase_price" class="form-control" value="{{ old('purchase_price', 0) }}" min="0" step="100" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="number" name="price_per_unit" class="form-control" value="{{ old('price_per_unit') }}" min="0" step="100" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <hr class="text-muted">

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_refundable" class="form-check-input" id="is_refundable" {{ old('is_refundable') ? 'checked' : '' }} onchange="toggleDepositAmount()">
                                <label class="form-check-label" for="is_refundable">Item ini Refundable (Deposit)</label>
                            </div>
                        </div>

                        <div class="mb-3" id="deposit_amount_container" style="display: {{ old('is_refundable') ? 'block' : 'none' }};">
                            <label class="form-label">Jumlah Deposit (Rp)</label>
                            <input type="number" name="deposit_amount" class="form-control" min="0" step="100" value="{{ old('deposit_amount') }}">
                            <small class="text-muted">Kosongkan atau isi 0 jika jumlah deposit sama dengan Price per Unit</small>
                        </div>

                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Create Item</button>
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function toggleDepositAmount() {
            const isChecked = document.getElementById('is_refundable').checked;
            document.getElementById('deposit_amount_container').style.display = isChecked ? 'block' : 'none';
        }

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
    </script>
@endsection