@extends('layouts.master')
@section('title', 'Serah Terima Shift')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Serah Terima Shift</h4>
            <div class="page-title-right">
                <a href="{{ route('shift-handovers.history') }}" class="btn btn-secondary btn-sm">
                    <i class="ri-history-line"></i> Riwayat
                </a>
                @can('handover.manage-templates')
                <a href="{{ route('handover-templates.index') }}" class="btn btn-secondary btn-sm">
                    <i class="ri-settings-3-line"></i> Kelola Template
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    @if($mySchedule && $mySchedule->shift)
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Shift Anda Hari Ini</h5>
                <p class="mb-1"><strong>Shift:</strong> {{ $mySchedule->shift->name }}</p>
                <p class="mb-1"><strong>Jam:</strong>
                    {{ \Carbon\Carbon::parse($mySchedule->shift->start_time)->format('H:i') }} -
                    {{ \Carbon\Carbon::parse($mySchedule->shift->end_time)->format('H:i') }}
                </p>

                @if($existingDraft)
                    <a href="{{ route('shift-handovers.show', $existingDraft) }}" class="btn btn-warning mt-3">
                        <i class="ri-edit-box-line"></i> Lanjutkan Draft Serah Terima
                    </a>
                @elseif($canCreate)
                    <form method="POST" data-ajax="true" data-ajax-success="redirectToNewHandover" action="{{ route('shift-handovers.create') }}">
                        @csrf
                        <button type="submit" data-submit-protect="true" class="btn btn-primary mt-3">
                            <i class="ri-add-line"></i> Buat Serah Terima
                        </button>
                    </form>
                @else
                    <p class="text-muted mt-3">Anda sudah membuat serah terima untuk shift ini hari ini.</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Menunggu Konfirmasi Anda</h5>
            </div>
            <div class="card-body p-0">
                @if($pendingHandovers->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($pendingHandovers as $pending)
                    <a href="{{ route('shift-handovers.show', $pending) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $pending->outgoingEmployee->name ?? '-' }}</strong>
                            <small class="d-block text-muted">
                                {{ $pending->items->count() }} item |
                                {{ $pending->created_at->format('H:i') }}
                                @if($pending->cash_amount > 0)
                                    | <span class="text-success fw-medium">Uang: Rp {{ number_format($pending->cash_amount, 0, ',', '.') }}</span>
                                @endif
                            </small>
                        </div>
                        <span class="badge bg-warning text-dark">Menunggu</span>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="ri-inbox-line fs-1"></i>
                    <p class="mt-2">Tidak ada serah terima yang menunggu konfirmasi</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($lastHandover)
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Serah Terima Terakhir (Referensi)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal</small>
                <strong>{{ $lastHandover->handover_date->format('d/m/Y') }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Petugas Keluar</small>
                <strong>{{ $lastHandover->outgoingEmployee->name ?? '-' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Petugas Masuk</small>
                <strong>{{ $lastHandover->incomingEmployee->name ?? '-' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Sisa Kas</small>
                <strong>@money($lastHandover->financial_summary['net_balance'] ?? 0)</strong>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
<script>
    function redirectToNewHandover(result) {
        window.location.href = "{{ route('shift-handovers.index') }}/" + result.data.id;
    }
</script>
@endsection
