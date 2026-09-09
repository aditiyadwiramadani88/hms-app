@extends('layouts.master')
@section('title')
    Checker Dashboard - Verifikasi Task
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Housekeeping @endslot
        @slot('title') Checker / QC @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0">Task Menunggu Verifikasi</h4>
                <span class="badge bg-primary fs-6">{{ $tasks->count() }} task</span>
            </div>
        </div>
    </div>

    @if($tasks->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="ri-checkbox-circle-line display-4 text-success"></i>
            <h5 class="mt-3 text-muted">Semua task sudah diverifikasi</h5>
            <p class="text-muted">Tidak ada task yang menunggu review saat ini.</p>
        </div>
    </div>
    @else
    <div class="row">
        @foreach($tasks as $task)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary-subtle d-flex align-items-center">
                    <h6 class="mb-0 flex-grow-1">🚪 Kamar {{ $task->room->room_number ?? '-' }}</h6>
                    <span class="badge bg-primary">Menunggu</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Tipe:</small> {{ $task->room->roomType->name ?? '-' }}
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">OB:</small> {{ $task->assignedUser->name ?? '-' }}
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Checklist:</small>
                        {{ $task->checklistItems->where('is_done', true)->count() }}/{{ $task->checklistItems->count() }} selesai
                    </div>
                    <a href="{{ route('housekeeping.checker.tasks.show', $task) }}" class="btn btn-primary w-100">
                        <i class="ri-search-eye-line me-1"></i> Review Task
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Riwayat Verifikasi -->
    @if($history->isNotEmpty())
    <div class="mt-4">
        <h5 class="mb-3"><i class="ri-history-line me-2"></i>Riwayat Verifikasi</h5>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kamar</th>
                                <th>OB</th>
                                <th>Status</th>
                                <th>Terakhir Update</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $h)
                            <tr>
                                <td class="fw-semibold">{{ $h->room->room_number ?? '-' }}</td>
                                <td>{{ $h->assignedUser->name ?? '-' }}</td>
                                <td>
                                    @if($h->status === 'selesai')
                                        <span class="badge bg-success">✅ Approved</span>
                                    @elseif($h->status === 'revisi')
                                        <span class="badge bg-warning text-dark">🔄 Revisi</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $h->updated_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('housekeeping.checker.tasks.show', $h) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
