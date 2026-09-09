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
                    <form method="POST" data-ajax="true" action="{{ route('inventory.mutation', $inventory) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="in">Stock In (+)</option>
                                <option value="out">Stock Out (-)</option>
                                <option value="adjustment">Adjustment (Set)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">Record Mutation</button>
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
@endsection