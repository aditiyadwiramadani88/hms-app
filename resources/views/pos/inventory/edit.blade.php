@extends('layouts.master')
@section('title')
    Edit {{ $inventory->name }}
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
            Edit {{ $inventory->name }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Inventory Item</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" data-ajax="true" action="{{ route('inventory.update', $inventory) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Foto Produk</label>
                            @if($inventory->image)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $inventory->image) }}" alt="{{ $inventory->name }}" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                            @endif
                            <input type="file" name="image" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <small class="text-muted">Max 2MB. Kosongkan jika tidak ingin mengganti foto.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $inventory->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $inventory->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" min="0" value="{{ $inventory->stock }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Stock Alert</label>
                                <input type="number" name="min_stock" class="form-control" min="0" value="{{ $inventory->min_stock }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" class="form-control" value="{{ $inventory->unit }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Harga Beli (Rp)</label>
                                <input type="number" name="purchase_price" class="form-control" min="0" step="100" value="{{ $inventory->purchase_price }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="number" name="price_per_unit" class="form-control" min="0" step="100" value="{{ $inventory->price_per_unit }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" {{ $inventory->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <hr class="text-muted">

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_refundable" class="form-check-input" id="is_refundable" {{ old('is_refundable', $inventory->is_refundable) ? 'checked' : '' }} onchange="toggleDepositAmount()">
                                <label class="form-check-label" for="is_refundable">Item ini Refundable (Deposit)</label>
                            </div>
                        </div>

                        <div class="mb-3" id="deposit_amount_container" style="display: {{ old('is_refundable', $inventory->is_refundable) ? 'block' : 'none' }};">
                            <label class="form-label">Jumlah Deposit (Rp)</label>
                            <input type="number" name="deposit_amount" class="form-control" min="0" step="100" value="{{ old('deposit_amount', (float)$inventory->deposit_amount > 0 ? (float)$inventory->deposit_amount : '') }}">
                            <small class="text-muted">Kosongkan atau isi 0 jika jumlah deposit sama dengan Price per Unit</small>
                        </div>

                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Item</button>
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

        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
@endsection
