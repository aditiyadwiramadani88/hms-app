@extends('layouts.master')
@section('title')
    Voucher Management
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('title')
            Voucher Management
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-ticket-2-line me-2 text-primary"></i>Vouchers</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('vouchers.create') }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Add New Voucher
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Value</th>
                                    <th scope="col">Usage</th>
                                    <th scope="col">Validity</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $voucher)
                                <tr>
                                    <td class="fw-medium text-primary">{{ $voucher->code }}</td>
                                    <td>{{ $voucher->name }}</td>
                                    <td>{{ ucfirst($voucher->type) }}</td>
                                    <td>
                                        @if($voucher->type === 'percentage')
                                            {{ number_format($voucher->value, 0) }}%
                                        @else
                                            @money($voucher->value)
                                        @endif
                                    </td>
                                    <td>{{ $voucher->usage_count }} / {{ $voucher->usage_limit ?? '∞' }}</td>
                                    <td>
                                        {{ $voucher->valid_from ? $voucher->valid_from->format('d M Y') : 'Start' }} - 
                                        {{ $voucher->valid_until->format('d M Y') }}
                                    </td>
                                    <td>
                                        @if($voucher->isValid())
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Invalid/Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ route('vouchers.show', $voucher->id) }}" class="text-info d-inline-block" title="View Usage">
                                                    <i class="ri-eye-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <a href="{{ route('vouchers.edit', $voucher->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display:inline-block;" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No vouchers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $vouchers->links() }}
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
