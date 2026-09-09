@extends('layouts.master')
@section('title')
    Purchase Details
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Purchases
        @endslot
        @slot('title')
            Purchase Details
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                     <div class="d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0">PO #{{ $purchase->purchase_number }}</h5>
                        <div class="flex-shrink-0">
                            <a href="{{ route('purchases.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Supplier Details:</h6>
                            <p class="text-muted mb-1"><strong>Name:</strong> {{ $purchase->supplier->name }}</p>
                            <p class="text-muted mb-1"><strong>Code:</strong> {{ $purchase->supplier->code }}</p>
                            <p class="text-muted"><strong>Address:</strong> {{ $purchase->supplier->address }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Purchase Info:</h6>
                            <p class="text-muted mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d F Y') }}</p>
                            <p class="text-muted mb-1"><strong>Status:</strong> 
                                @if($purchase->status === 'received')
                                    <span class="badge bg-success-subtle text-success">Received</span>
                                @elseif($purchase->status === 'draft')
                                    <span class="badge bg-warning-subtle text-warning">Draft</span>
                                @elseif($purchase->status === 'cancelled')
                                    <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                @endif
                            </p>
                             <p class="text-muted mb-0"><strong>Created By:</strong> {{ $purchase->user->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <h6 class="mb-1">Total Amount:</h6>
                            <h4 class="fw-bold">@money($purchase->total_amount)</h4>
                        </div>
                    </div>

                    <div class="table-responsive table-card mt-4">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Item Name</th>
                                    <th scope="col" class="text-end">Quantity</th>
                                    <th scope="col" class="text-end">Price</th>
                                    <th scope="col" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->inventory->name ?? 'Item not found' }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">@money($item->price)</td>
                                        <td class="text-end">@money($item->subtotal)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                             <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">@money($purchase->total_amount)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
