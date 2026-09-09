@extends('layouts.master')
@section('title')
    Point of Sale
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown { z-index: 1060; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            POS
        @endslot
        @slot('title')
            Point of Sale
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="posList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-shopping-cart-fill me-2 text-primary"></i>POS Orders</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('pos.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> New Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('pos.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-4">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Search order number, guest, or room...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <select class="form-control" name="payment_status" data-choices data-choices-search-false>
                                    <option value="">All Payment Status</option>
                                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="charge_to_room" {{ request('payment_status') === 'charge_to_room' ? 'selected' : '' }}>Charge to Room</option>
                                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <input type="date" class="form-control" name="date_from"
                                       value="{{ request('date_from') }}" placeholder="From Date">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <input type="date" class="form-control" name="date_to"
                                       value="{{ request('date_to') }}" placeholder="To Date">
                            </div>
                            <div class="col-xxl-3 col-sm-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                        <i class="ri-filter-3-line me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('pos.index') }}" class="btn btn-soft-secondary">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                    <a href="{{ route('pos.export.pdf', request()->all()) }}" class="btn btn-danger" title="Print/Export periode ini">
                                        <i class="ri-file-pdf-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- POS Orders Table --}}
                <div class="card-body">
                    @php
                        $sortUrl = fn($col) => request()->fullUrlWithQuery([
                            'sort' => $col,
                            'direction' => (request('sort') === $col && request('direction') === 'asc') ? 'desc' : 'asc',
                            'page' => null,
                        ]);
                        $sortIcon = fn($col) => request('sort') !== $col
                            ? 'ri-expand-up-down-line text-muted'
                            : (request('direction') === 'asc' ? 'ri-sort-asc' : 'ri-sort-desc');
                    @endphp
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="posTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                        </div>
                                    </th>
                                    <th class="text-uppercase">
                                        <a href="{{ $sortUrl('order_number') }}" class="text-muted text-decoration-none">Order Number <i class="{{ $sortIcon('order_number') }}"></i></a>
                                    </th>
                                    <th class="text-uppercase">Guest / Room</th>
                                    <th class="text-uppercase">
                                        <a href="{{ $sortUrl('created_at') }}" class="text-muted text-decoration-none">Date <i class="{{ $sortIcon('created_at') }}"></i></a>
                                    </th>
                                    <th class="text-uppercase">
                                        <a href="{{ $sortUrl('total_amount') }}" class="text-muted text-decoration-none">Total <i class="{{ $sortIcon('total_amount') }}"></i></a>
                                    </th>
                                    <th class="text-uppercase">Payment Method</th>
                                    <th class="text-uppercase">Status</th>
                                    <th class="text-uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($orders ?? [] as $order)
                                <tr>
                                    <th scope="row">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="checkAll" value="{{ $order->id }}">
                                        </div>
                                    </th>
                                    <td class="fw-medium">
                                        <span class="text-primary">{{ $order->order_number ?? '#' . $order->id }}</span>
                                    </td>
                                    <td>
                                        @if($order->booking_id)
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        <i class="ri-hotel-bed-fill"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="fw-medium">{{ $order->booking->guest->name ?? 'N/A' }}</span>
                                                    <br><small class="text-muted">Room {{ $order->booking->room->room_number ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Walk-in</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->created_at ? $order->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                                    <td class="fw-medium">{{ number_format($order->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        @if($order->payment_method === 'cash')
                                            <span class="badge bg-success-subtle text-success"><i class="ri-money-dollar-circle-line me-1"></i>Cash</span>
                                        @elseif($order->payment_method === 'card')
                                            <span class="badge bg-info-subtle text-info"><i class="ri-bank-card-line me-1"></i>Card</span>
                                        @elseif($order->payment_method === 'qris')
                                            <span class="badge bg-primary-subtle text-primary"><i class="ri-qr-code-line me-1"></i>QRIS</span>
                                        @elseif($order->payment_method === 'charge_to_room')
                                            <span class="badge bg-warning-subtle text-warning"><i class="ri-hotel-bed-line me-1"></i>Room Charge</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ ucfirst($order->payment_method ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->payment_status === 'paid')
                                            <span class="badge bg-success-subtle text-success">Paid</span>
                                        @elseif($order->payment_status === 'unpaid')
                                            <span class="badge bg-danger-subtle text-danger">Unpaid</span>
                                        @elseif($order->payment_status === 'charge_to_room')
                                            <span class="badge bg-warning-subtle text-warning">Charge to Room</span>
                                        @elseif($order->payment_status === 'partial')
                                            <span class="badge bg-info-subtle text-info">Partial</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ ucfirst($order->payment_status ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                                <a href="{{ route('pos.show', $order->id) ?? '#' }}" class="text-primary d-inline-block">
                                                    <i class="ri-eye-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                                                <a href="{{ route('pos.print', $order->id) }}" target="_blank" class="text-info d-inline-block">
                                                    <i class="ri-printer-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <form action="{{ route('pos.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this POS order?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 border-0 d-inline-block" data-submit-protect="true">
                                                        <i class="ri-delete-bin-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ri-shopping-cart-line fs-1 d-block mb-2"></i>
                                        No POS orders found. <a href="{{ route('pos.create') }}" class="text-primary">Create a new order</a> to get started.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Toastify({
                    text: "{{ session('success') }}",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: { background: "linear-gradient(to right, #0ab39c, #405189)" }
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
                    style: { background: "linear-gradient(to right, #f06548, #f7b84b)" }
                }).showToast();
            @endif
        });
    </script>
@endsection
