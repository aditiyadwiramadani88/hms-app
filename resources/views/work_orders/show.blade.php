@extends('layouts.master')
@section('title')
    Work Order #{{ $workOrder->id }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('work-orders.index') }}">Work Orders</a>
        @endslot
        @slot('title')
            Work Order #{{ $workOrder->id }}
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
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Detail Work Order</h5>
                    @if($workOrder->status === 'pending')
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">Pending</span>
                    @elseif($workOrder->status === 'in_progress')
                        <span class="badge bg-info fs-6 px-3 py-2">In Progress</span>
                    @else
                        <span class="badge bg-success fs-6 px-3 py-2">Completed</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Kamar</p>
                            <h5>{{ $workOrder->room->room_number ?? '-' }}</h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Tipe Kamar</p>
                            <h5>{{ $workOrder->room->roomType->name ?? '-' }}</h5>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Tipe Work Order</p>
                            <h5>
                                @if($workOrder->type === 'cleaning') 🧹 Cleaning
                                @elseif($workOrder->type === 'maintenance') 🔧 Maintenance
                                @else 📋 Other
                                @endif
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Assigned To</p>
                            <h5>{{ $workOrder->assignee->name ?? '-' }}</h5>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Bonus</p>
                            <h5>Rp {{ number_format($workOrder->bonus_amount, 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Selesai Pada</p>
                            <h5>{{ $workOrder->completed_at ? $workOrder->completed_at->format('d/m/Y H:i') : '-' }}</h5>
                        </div>
                    </div>
                    @if($workOrder->notes)
                    <div class="mb-3">
                        <p class="mb-1 text-muted">Catatan</p>
                        <p>{{ $workOrder->notes }}</p>
                    </div>
                    @endif

                    <!-- Actions -->
                    <hr>
                    <div class="d-flex gap-2">
                        @if($workOrder->status === 'pending')
                        <form method="POST" data-ajax="true" action="{{ route('work-orders.start', $workOrder) }}">
                            @csrf
                            <button type="submit" data-submit-protect="true" class="btn btn-info">
                                <i class="ri-play-line me-1"></i> Mulai
                            </button>
                        </form>
                        @endif

                        @if($workOrder->status === 'in_progress')
                        <form method="POST" data-ajax="true" action="{{ route('work-orders.complete', $workOrder) }}">
                            @csrf
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-check-line me-1"></i> Selesai
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Dibuat</small>
                        <div class="fw-medium">{{ $workOrder->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($workOrder->status !== 'pending')
                    <div class="mb-3">
                        <small class="text-muted">Dimulai</small>
                        <div class="fw-medium">{{ $workOrder->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                    @if($workOrder->completed_at)
                    <div class="mb-3">
                        <small class="text-muted">Selesai</small>
                        <div class="fw-medium">{{ $workOrder->completed_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
