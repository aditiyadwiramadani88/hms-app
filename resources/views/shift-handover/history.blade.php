@extends('layouts.master')
@section('title', 'Riwayat Serah Terima Shift')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Riwayat Serah Terima Shift</h4>
            <div class="page-title-right">
                <a href="{{ route('shift-handovers.index') }}" class="btn btn-primary btn-sm">
                    <i class="ri-arrow-left-line"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('shift-handovers.history') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Shift</label>
                <select class="form-select" name="shift_id">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                        {{ $shift->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Karyawan</label>
                <select class="form-select" name="employee_id">
                    <option value="">Semua</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Menunggu</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Terkonfirmasi</option>
                    <option value="disputed" {{ request('status') == 'disputed' ? 'selected' : '' }}>Dispute</option>
                </select>
            </div>
            <div class="col-md-12 d-flex justify-content-end">
                <button type="submit" data-submit-protect="true" class="btn btn-primary"><i class="ri-filter-line"></i> Filter</button>
                <a href="{{ route('shift-handovers.history') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Shift Keluar</th>
                        <th>Shift Masuk</th>
                        <th>Petugas Keluar</th>
                        <th>Petugas Masuk</th>
                        <th>Status</th>
                        <th class="text-end">Kas Diserahkan</th>
                        <th>Waktu Submit</th>
                        <th>Waktu Konfirmasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($handovers as $h)
                    <tr role="button" class="clickable-row" data-href="{{ route('shift-handovers.show', $h) }}" style="cursor: pointer;">
                        <td>{{ $h->handover_date->format('d/m/Y') }}</td>
                        <td>{{ $h->outgoingShift->name ?? '-' }}</td>
                        <td>{{ $h->incomingShift->name ?? '-' }}</td>
                        <td>{{ $h->outgoingEmployee->name ?? '-' }}</td>
                        <td>{{ $h->incomingEmployee->name ?? '-' }}</td>
                        <td>@include('shift-handover.partials.status-badge', ['status' => $h->status])</td>
                        <td class="text-end">{{ $h->cash_amount ? number_format($h->cash_amount, 0, ',', '.') : '-' }}</td>
                        <td>{{ $h->submitted_at ? $h->submitted_at->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $h->confirmed_at ? $h->confirmed_at->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ri-inbox-line fs-2"></i>
                            <p class="mt-2">Tidak ada data serah terima ditemukan untuk kriteria filter tersebut</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($handovers->hasPages())
    <div class="card-footer">
        {{ $handovers->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.clickable-row').on('click', function() {
        window.location = $(this).data('href');
    });
});
</script>
@endpush
