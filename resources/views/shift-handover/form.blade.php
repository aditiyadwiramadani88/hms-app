@extends('layouts.master')
@section('title', 'Form Serah Terima Shift')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Form Serah Terima Shift</h4>
            <div>
                @include('shift-handover.partials.status-badge', ['status' => $handover->status])
            </div>
        </div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" data-ajax="true" action="{{ $handover->status === 'draft' ? route('shift-handovers.submit', $handover) : '#' }}" id="handoverForm">
    @csrf

    @include('shift-handover.partials.financial-summary', ['financialSummary' => $handover->financial_summary])

    @include('shift-handover.partials.transaction-detail', ['transactionDetails' => $handover->transaction_details])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Kas Fisik</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Jumlah Kas Fisik yang Diserahkan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control mask-money" name="cash_amount"
                            id="cash_amount" value="{{ old('cash_amount', number_format($handover->cash_amount ?? 0, 0, ',', '.')) }}"
                            data-net-balance="{{ $handover->financial_summary['net_balance'] ?? 0 }}"
                            required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sisa Kas (Sistem)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control" value="{{ number_format($handover->financial_summary['net_balance'] ?? 0, 0, ',', '.') }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selisih</label>
                    <div id="cashDifference" class="form-control-plaintext fw-bold fs-5">Rp 0</div>
                </div>
            </div>

            <div id="discrepancySection" class="mt-3 d-none">
                <label class="form-label">Catatan Penjelasan Selisih <span class="text-danger">*</span></label>
                <textarea class="form-control" name="discrepancy_notes" rows="2" maxlength="500"
                    placeholder="Jelaskan penyebab selisih kas (min 10 karakter)">{{ old('discrepancy_notes', $handover->discrepancy_notes) }}</textarea>
                <small class="text-muted">Minimal 10 karakter</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Checklist Serah Terima</h5>
        </div>
        <div class="card-body">
            @include('shift-handover.partials.checklist-items', [
                'items' => $handover->items,
                'mode' => 'form',
                'disabled' => $handover->status !== 'draft'
            ])
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Catatan</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Catatan untuk Petugas Masuk</label>
                <textarea class="form-control" name="notes" rows="3" maxlength="2000"
                    placeholder="Catatan penting untuk shift berikutnya...">{{ old('notes', $handover->notes) }}</textarea>
                <small class="text-muted">Maksimal 2000 karakter</small>
            </div>
        </div>
    </div>

    @if($handover->status === 'draft')
    <div class="row">
        <div class="col-12 text-end">
            <a href="{{ route('shift-handovers.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="button" class="btn btn-info" id="saveDraftBtn">
                <i class="ri-save-line"></i> Simpan Draft
            </button>
            <button type="submit" data-submit-protect="true" class="btn btn-primary" id="submitBtn">
                <i class="ri-send-plane-line"></i> Submit Serah Terima
            </button>
        </div>
    </div>
    @endif
</form>

<form id="saveDraftForm" method="POST" data-ajax="true" action="{{ route('shift-handovers.update', $handover) }}" style="display:none;">
    @csrf
    @method('PUT')
</form>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function formatNumber(n) {
        return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function unformatNumber(s) {
        return parseInt(s.replace(/\./g, '')) || 0;
    }

    function updateCashDifference() {
        var cashAmount = unformatNumber($('#cash_amount').val());
        var netBalance = parseFloat($('#cash_amount').data('net-balance')) || 0;
        var diff = cashAmount - netBalance;
        var diffDisplay = 'Rp ' + Math.abs(diff).toLocaleString('id-ID');
        if (diff > 0) {
            $('#cashDifference').text('+' + diffDisplay).removeClass('text-danger').addClass('text-success');
        } else if (diff < 0) {
            $('#cashDifference').text('-' + diffDisplay).removeClass('text-success').addClass('text-danger');
        } else {
            $('#cashDifference').text('Rp 0').removeClass('text-success text-danger');
        }

        if (Math.abs(diff) > 0.01) {
            $('#discrepancySection').removeClass('d-none');
        } else {
            $('#discrepancySection').addClass('d-none');
        }
    }

    $('#cash_amount').on('input', function() {
        var val = $(this).val().replace(/\./g, '');
        if (val.match(/^\d+$/)) {
            $(this).val(formatNumber(val));
        }
        updateCashDifference();
    });

    $('#saveDraftBtn').on('click', function() {
        var form = $('#saveDraftForm');
        var data = $('#handoverForm').serialize();
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: data + '&_method=PUT',
            success: function() {
                alert('Draft berhasil disimpan');
            },
            error: function() {
                alert('Gagal menyimpan draft');
            }
        });
    });

    $('#handoverForm').on('submit', function(e) {
        var cashAmount = unformatNumber($('#cash_amount').val());
        if (cashAmount < 0) {
            alert('Jumlah kas fisik tidak valid');
            e.preventDefault();
            return;
        }

        var allRequiredChecked = true;
        var uncheckedRequired = [];
        $('.checklist-required').each(function() {
            if ($(this).data('required') === true || $(this).data('required') === 'true') {
                if (!$(this).is(':checked')) {
                    allRequiredChecked = false;
                    var label = $(this).closest('.d-flex').find('label').text().trim();
                    uncheckedRequired.push(label);
                }
            }
        });

        if (!allRequiredChecked) {
            alert('Item wajib berikut belum dicentang:\n- ' + uncheckedRequired.join('\n- '));
            e.preventDefault();
            return;
        }

        var diff = unformatNumber($('#cash_amount').val()) - (parseFloat($('#cash_amount').data('net-balance')) || 0);
        if (Math.abs(diff) > 0.01) {
            var discNotes = $('textarea[name="discrepancy_notes"]').val().trim();
            if (discNotes.length < 10) {
                alert('Catatan penjelasan selisih wajib diisi minimal 10 karakter');
                e.preventDefault();
                return;
            }
        }
    });
});
</script>
@endpush
