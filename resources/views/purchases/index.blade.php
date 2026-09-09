@extends('layouts.master')
@section('title')
    Purchase Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Procurement
        @endslot
        @slot('title')
            Purchase Management
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="purchaseList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-shopping-cart-2-line me-2 text-primary"></i>Purchase Orders</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('purchases.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Create Purchase Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0" id="purchaseTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">#</th>
                                    <th class="sort text-uppercase" data-sort="purchase_number">PO Number</th>
                                    <th class="sort text-uppercase" data-sort="supplier">Supplier</th>
                                    <th class="sort text-uppercase" data-sort="purchase_date">Date</th>
                                    <th class="sort text-uppercase" data-sort="total">Total Amount</th>
                                    <th class="sort text-uppercase" data-sort="status">Status</th>
                                    <th class="sort text-uppercase" data-sort="action">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($purchases ?? [] as $purchase)
                                <tr>
                                    <th scope="row">{{ $loop->iteration }}</th>
                                    <td class="fw-medium">
                                        <a href="{{ route('purchases.show', $purchase->id) }}" class="text-primary">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td>
                                    <td class="fw-medium">@money($purchase->total_amount)</td>
                                    <td>
                                        @if($purchase->status === 'received')
                                            <span class="badge bg-success-subtle text-success">Received</span>
                                        @elseif($purchase->status === 'draft')
                                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                                        @elseif($purchase->status === 'cancelled')
                                            <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                                <a href="{{ route('purchases.show', $purchase->id) }}" class="text-info d-inline-block">
                                                    <i class="ri-eye-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <a href="{{ route('purchases.edit', $purchase->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            @can('delete transactions')
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $purchase->id }}, '{{ $purchase->purchase_number }}')">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No purchases found. <a href="{{ route('purchases.create') }}" class="text-primary">Create a new purchase order</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $purchases->links() ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade flip" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                        colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                    </lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Purchase Order <span id="deleteName"></span>?</h4>
                        <p class="text-muted fs-15 mb-4">This action cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1 align-middle"></i> Cancel
                            </button>
                            <form id="deleteForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Yes, Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function confirmDelete(id, name) {
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteForm').action = '{{ route("purchases.destroy", ":id") }}'.replace(':id', id);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
@endsection
