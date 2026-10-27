@extends('layouts.master')
@section('title')
    Checker Dashboard - Verifikasi Task
@endsection
@section('css')
<style>
    #roomTypePills .nav-link {
        transition: all 0.2s ease-in-out;
        color: #495057 !important;
        background-color: #ffffff !important;
        border: 1px solid #dcdfe4 !important;
        cursor: pointer;
    }
    #roomTypePills .nav-link:hover {
        background-color: #f3f6f9 !important;
        color: #405189 !important;
        border-color: #405189 !important;
    }
    #roomTypePills .nav-link.active {
        background-color: #405189 !important;
        color: #ffffff !important;
        border-color: #405189 !important;
        box-shadow: 0 4px 12px rgba(64, 81, 137, 0.35) !important;
    }
    #roomTypePills .nav-link.active i,
    #roomTypePills .nav-link.active span {
        color: #ffffff !important;
    }
    #roomTypePills .nav-link.active .badge {
        background-color: #ffffff !important;
        color: #405189 !important;
        border: 1px solid #ffffff !important;
        font-weight: 700 !important;
    }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Housekeeping @endslot
        @slot('title') Checker / QC @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0">Task Menunggu Verifikasi</h4>
                    <span class="badge bg-primary fs-6">{{ $tasks->count() }} task</span>
                    @if(!$showAll)
                        <span class="badge bg-info-subtle text-info">Hari Ini ({{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }})</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">Semua Periode</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
                        @if($showAll)
                            <a href="{{ route('housekeeping.checker.dashboard') }}" class="btn btn-sm btn-outline-secondary text-nowrap">Filter Hari Ini</a>
                        @else
                            <a href="{{ route('housekeeping.checker.dashboard', ['all' => 1]) }}" class="btn btn-sm btn-outline-secondary text-nowrap">Lihat Semua</a>
                        @endif
                    </form>
                    <a href="{{ route('reports.housekeeping') }}" class="btn btn-sm btn-outline-info text-nowrap">
                        <i class="ri-file-chart-line me-1"></i> Ke Laporan
                    </a>
                </div>
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
    {{-- Nav Pills per Tipe Kamar --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <span class="fs-12 text-uppercase fw-bold text-muted">
                    <i class="ri-filter-3-line me-1 text-primary"></i> Kategori Tipe Kamar:
                </span>
                <span class="text-muted fs-12">
                    Total: <strong class="text-dark">{{ $tasks->count() }} task</strong> terbagi dalam <strong class="text-dark">{{ $tasksGrouped->count() }} tipe kamar</strong>
                </span>
            </div>
            <style>
                #roomTypePills .nav-link {
                    transition: all 0.2s ease-in-out;
                    color: #495057 !important;
                    background-color: #ffffff !important;
                    border: 1px solid #dcdfe4 !important;
                    cursor: pointer;
                }
                #roomTypePills .nav-link:hover {
                    background-color: #f3f6f9 !important;
                    color: #405189 !important;
                    border-color: #405189 !important;
                }
                #roomTypePills .nav-link.active {
                    background-color: #405189 !important;
                    color: #ffffff !important;
                    border-color: #405189 !important;
                    box-shadow: 0 4px 12px rgba(64, 81, 137, 0.35) !important;
                }
                #roomTypePills .nav-link.active i,
                #roomTypePills .nav-link.active span {
                    color: #ffffff !important;
                }
                #roomTypePills .nav-link.active .badge {
                    background-color: #ffffff !important;
                    color: #405189 !important;
                    border: 1px solid #ffffff !important;
                    font-weight: 700 !important;
                }
            </style>
            <ul class="nav nav-pills flex-wrap gap-2" id="roomTypePills" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-3 py-2 fw-medium d-flex align-items-center gap-2 border" 
                            id="pills-all-tab" 
                            data-bs-toggle="pill" 
                            data-bs-target="#pills-all" 
                            type="button" 
                            role="tab" 
                            aria-controls="pills-all" 
                            aria-selected="true">
                        <i class="ri-apps-2-line"></i>
                        <span>Semua Tipe</span>
                        <span class="badge bg-primary text-white rounded-pill">{{ $tasks->count() }}</span>
                    </button>
                </li>
                @foreach($tasksGrouped as $roomTypeName => $typeTasks)
                    @php
                        $tabId = 'pills-type-' . \Illuminate\Support\Str::slug($roomTypeName);
                    @endphp
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-3 py-2 fw-medium d-flex align-items-center gap-2 border" 
                                id="{{ $tabId }}-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#{{ $tabId }}" 
                                type="button" 
                                role="tab" 
                                aria-controls="{{ $tabId }}" 
                                aria-selected="false">
                            <i class="ri-hotel-bed-line"></i>
                            <span>{{ $roomTypeName }}</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill border">{{ $typeTasks->count() }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content" id="roomTypePillsContent">
        {{-- Tab 1: Semua Tipe (Grouped by Room Type Section) --}}
        <div class="tab-pane fade show active" id="pills-all" role="tabpanel" aria-labelledby="pills-all-tab">
            @foreach($tasksGrouped as $roomTypeName => $typeTasks)
            <div class="card mb-4 border shadow-sm">
                <div class="card-header bg-light-subtle py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary p-2 rounded-circle fs-13">
                            <i class="ri-hotel-bed-line"></i>
                        </span>
                        <div>
                            <h5 class="card-title mb-0 fs-15 fw-bold text-dark">{{ $roomTypeName }}</h5>
                            <small class="text-muted">Kategori Tipe Kamar</small>
                        </div>
                    </div>
                    <span class="badge bg-primary fs-12 px-3 py-2 rounded-pill">
                        {{ $typeTasks->count() }} task menunggu
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        @foreach($typeTasks as $task)
                            @include('housekeeping.checker.task-card', ['task' => $task])
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Individual Tabs per Room Type --}}
        @foreach($tasksGrouped as $roomTypeName => $typeTasks)
            @php
                $tabId = 'pills-type-' . \Illuminate\Support\Str::slug($roomTypeName);
            @endphp
            <div class="tab-pane fade" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-light-subtle py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle fs-13">
                                <i class="ri-hotel-bed-line"></i>
                            </span>
                            <div>
                                <h5 class="card-title mb-0 fs-15 fw-bold text-dark">{{ $roomTypeName }}</h5>
                                <small class="text-muted">Menampilkan khusus task untuk tipe kamar ini</small>
                            </div>
                        </div>
                        <span class="badge bg-primary fs-12 px-3 py-2 rounded-pill">
                            {{ $typeTasks->count() }} task menunggu
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            @foreach($typeTasks as $task)
                                @include('housekeeping.checker.task-card', ['task' => $task])
                            @endforeach
                        </div>
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
                                <th>Waktu Verifikasi</th>
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
