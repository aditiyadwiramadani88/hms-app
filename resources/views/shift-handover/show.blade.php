@extends('layouts.master')
@section('title', 'Detail Serah Terima Shift')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Serah Terima Shift</h4>
            <div class="d-flex gap-2 align-items-center">
                @include('shift-handover.partials.status-badge', ['status' => $shiftHandover->status])
                @can('handover.export')
                <a href="{{ route('shift-handovers.export.pdf', $shiftHandover) }}" class="btn btn-secondary btn-sm">
                    <i class="ri-file-pdf-line"></i> Export PDF
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted d-block">Tanggal</small>
                <strong>{{ $shiftHandover->handover_date->format('d/m/Y') }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Shift Keluar</small>
                <strong>{{ $shiftHandover->outgoingShift->name ?? '-' }}</strong>
                <small class="d-block text-muted">
                    {{ $shiftHandover->outgoingShift ? \Carbon\Carbon::parse($shiftHandover->outgoingShift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($shiftHandover->outgoingShift->end_time)->format('H:i') : '' }}
                </small>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Shift Masuk</small>
                <strong>{{ $shiftHandover->incomingShift->name ?? '-' }}</strong>
                <small class="d-block text-muted">
                    {{ $shiftHandover->incomingShift ? \Carbon\Carbon::parse($shiftHandover->incomingShift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($shiftHandover->incomingShift->end_time)->format('H:i') : '' }}
                </small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4">
                <small class="text-muted d-block">Petugas Keluar</small>
                <strong>{{ $shiftHandover->outgoingEmployee->name ?? '-' }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Petugas Masuk</small>
                <strong>{{ $shiftHandover->incomingEmployee->name ?? '-' }}</strong>
                @if($shiftHandover->confirmed_at)
                <small class="d-block text-muted">{{ $shiftHandover->confirmed_at->format('d/m/Y H:i') }}</small>
                @endif
            </div>
            @if($shiftHandover->submitted_at)
            <div class="col-md-4">
                <small class="text-muted d-block">Waktu Submit</small>
                <strong>{{ $shiftHandover->submitted_at->format('d/m/Y H:i') }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>

@include('shift-handover.partials.financial-summary', ['financialSummary' => $shiftHandover->financial_summary])

@include('shift-handover.partials.transaction-detail', ['transactionDetails' => $shiftHandover->transaction_details])

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Checklist Serah Terima</h5>
    </div>
    <div class="card-body p-0">
        @include('shift-handover.partials.checklist-items', [
            'items' => $shiftHandover->items,
            'mode' => 'display'
        ])
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Kas Fisik</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted d-block">Kas Fisik Diserahkan</small>
                <strong>@money($shiftHandover->cash_amount ?? 0)</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Sisa Kas (Sistem)</small>
                <strong>@money($shiftHandover->financial_summary['net_balance'] ?? 0)</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Selisih</small>
                @php
                    $cashAmt = (float)($shiftHandover->cash_amount ?? 0);
                    $netBal = (float)($shiftHandover->financial_summary['net_balance'] ?? 0);
                    $diff = $cashAmt - $netBal;
                @endphp
                <strong class="{{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : '') }}">
                    {{ $diff >= 0 ? '+' : '' }}@money($diff)
                </strong>
                @if($shiftHandover->cash_discrepancy && $shiftHandover->discrepancy_notes)
                <div class="mt-2">
                    <small class="text-muted d-block">Catatan Selisih:</small>
                    <p class="mb-0">{{ $shiftHandover->discrepancy_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($shiftHandover->notes)
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Catatan Petugas Keluar ({{ $shiftHandover->outgoingEmployee->name ?? '-' }})</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $shiftHandover->notes }}</p>
    </div>
</div>
@endif

@if($shiftHandover->incoming_notes)
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Catatan Petugas Masuk ({{ $shiftHandover->incomingEmployee->name ?? '-' }})</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $shiftHandover->incoming_notes }}</p>
    </div>
</div>
@endif

@if($canConfirm && $isIncoming)
<div class="card">
    <div class="card-body">
        <div class="d-flex gap-2 justify-content-end">
            <form method="POST" data-ajax="true" action="{{ route('shift-handovers.confirm', $shiftHandover) }}" class="d-inline">
                @csrf
                <button type="submit" data-submit-protect="true" class="btn btn-success" onclick="return confirm('Konfirmasi penerimaan serah terima ini?')">
                    <i class="ri-check-double-line"></i> Konfirmasi Terima
                </button>
            </form>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disputeModal">
                <i class="ri-close-circle-line"></i> Dispute
            </button>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12 text-end">
        <a href="{{ route('shift-handovers.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>

@if($canConfirm && $isIncoming)
<div class="modal fade" id="disputeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" data-ajax="true" action="{{ route('shift-handovers.dispute', $shiftHandover) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispute Serah Terima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Alasan Dispute <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="incoming_notes" rows="3" minlength="10" required
                            placeholder="Jelaskan alasan dispute (min 10 karakter)"></textarea>
                        <small class="text-muted">Minimal 10 karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" data-submit-protect="true" class="btn btn-danger">Submit Dispute</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
