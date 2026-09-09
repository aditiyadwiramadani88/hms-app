@extends('layouts.master')
@section('title')
    Work Orders
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Housekeeping
        @endslot
        @slot('title')
            Work Orders
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('work-orders.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <option value="cleaning" {{ request('type') == 'cleaning' ? 'selected' : '' }}>Cleaning</option>
                            <option value="maintenance" {{ request('type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Semua</option>
                            @foreach($obs as $ob)
                                <option value="{{ $ob->id }}" {{ request('assigned_to') == $ob->id ? 'selected' : '' }}>{{ $ob->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Work Orders Table -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title flex-grow-1 mb-0">Daftar Work Orders</h5>
            <a href="{{ route('work-orders.create') }}" class="btn btn-success">
                <i class="ri-add-line me-1"></i> Buat Work Order
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Kamar</th>
                            <th>Tipe</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Bonus</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workOrders as $wo)
                        <tr>
                            <td>{{ $wo->id }}</td>
                            <td class="fw-semibold">{{ $wo->room->room_number ?? '-' }}</td>
                            <td>
                                @if($wo->type === 'cleaning')
                                    <span class="badge bg-info-subtle text-info">Cleaning</span>
                                @elseif($wo->type === 'maintenance')
                                    <span class="badge bg-warning-subtle text-warning">Maintenance</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Other</span>
                                @endif
                            </td>
                            <td>{{ $wo->assignee->name ?? '-' }}</td>
                            <td>
                                @if($wo->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($wo->status === 'in_progress')
                                    <span class="badge bg-info">In Progress</span>
                                @else
                                    <span class="badge bg-success">Completed</span>
                                @endif
                            </td>
                            <td>Rp {{ number_format($wo->bonus_amount, 0, ',', '.') }}</td>
                            <td>{{ $wo->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('work-orders.show', $wo) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-eye-line"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada work order.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
