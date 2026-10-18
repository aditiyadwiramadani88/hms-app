@extends('layouts.master')
@section('title')
    Edit Invoice {{ $customInvoice->invoice_number }}
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 0.25rem !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px !important; padding-left: 12px !important; color: #495057 !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Custom Invoices @endslot
        @slot('title') Edit {{ $customInvoice->invoice_number }} @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('custom-invoices.update', $customInvoice->id) }}" method="POST" data-ajax="true">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-edit-box-line me-2 text-primary"></i>Edit Custom Invoice</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" value="{{ $customInvoice->invoice_number }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="guest_id" class="form-label">Guest (Tamu) <span class="text-danger">*</span></label>
                            <select class="form-control" id="guest_id" name="guest_id" required>
                                @if($customInvoice->guest)
                                    <option value="{{ $customInvoice->guest_id }}" selected>{{ $customInvoice->guest->name }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="source" class="form-label">Source / Agent <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="source" name="source" value="{{ old('source', $customInvoice->source) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_name" class="form-label">Nama Kamar / Layanan <span class="text-danger">*</span></label>
                                <select class="form-control" id="room_name" name="room_name" required>
                                    <option value="">-- Pilih Kamar / Layanan --</option>
                                    @php
                                        $currentVal = old('room_name', $customInvoice->room_name);
                                        $matched = false;
                                    @endphp
                                    @foreach($rooms ?? [] as $room)
                                        @php
                                            $roomLabel = 'Kamar ' . $room->room_number . ($room->roomType ? ' - ' . $room->roomType->name : '');
                                            $isSelected = ($currentVal === $roomLabel || $currentVal === $room->room_number);
                                            if ($isSelected) $matched = true;
                                        @endphp
                                        <option value="{{ $roomLabel }}" data-price="{{ (int)$room->price_public }}" {{ $isSelected ? 'selected' : '' }}>
                                            {{ $roomLabel }}
                                        </option>
                                    @endforeach
                                    @if(!$matched && !empty($currentVal))
                                        <option value="{{ $currentVal }}" selected>{{ $currentVal }}</option>
                                    @endif
                                </select>
                                <small class="text-muted">Pilih dari list kamar general data (bisa ketik nama baru jika layanan custom).</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="check_in" class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $customInvoice->check_in->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="check_out" class="form-label">Tanggal Keluar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $customInvoice->check_out->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nights_display" class="form-label">Malam</label>
                                <input type="text" class="form-control" id="nights_display" value="{{ $customInvoice->nights }}" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adults" class="form-label">Dewasa <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="adults" name="adults" value="{{ old('adults', $customInvoice->adults) }}" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="children" class="form-label">Anak-anak</label>
                                <input type="number" class="form-control" id="children" name="children" value="{{ old('children', $customInvoice->children) }}" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sell_price" class="form-label text-primary fw-semibold">Harga Jual (Tarif Per Malam) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="sell_price" name="sell_price" value="{{ old('sell_price', $customInvoice->sell_price) }}" placeholder="Tarif per malam" required>
                                </div>
                                <div class="p-2 mt-2 bg-light-subtle rounded border d-flex justify-content-between align-items-center">
                                    <span class="fs-12 text-muted">
                                        Total: <strong id="preview_nights">{{ $customInvoice->nights }}</strong> malam &times; <strong id="preview_rate">Rp 0</strong>
                                    </span>
                                    <span class="fs-13 fw-bold text-primary" id="preview_total">Rp 0</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="agent_commission" class="form-label text-muted">Komisi Agent (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="agent_commission" name="agent_commission" value="{{ old('agent_commission', $customInvoice->agent_commission) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan / Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $customInvoice->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('custom-invoices.show', $customInvoice->id) }}" class="btn btn-light">Batal</a>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary px-4">Update Invoice</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#guest_id').select2({
                ajax: { 
                    url: '{{ route("guests.search") }}', 
                    dataType: 'json', 
                    delay: 250, 
                    data: (p) => ({ q: p.term }), 
                    processResults: (d) => ({ results: d }), 
                    cache: true 
                },
                placeholder: 'Cari tamu...', 
                minimumInputLength: 2
            });

            $('#room_name').select2({
                placeholder: '-- Pilih Kamar / Layanan --',
                tags: true,
                allowClear: true
            });

            $('#room_name').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var price = selectedOption.data('price');
                if (price && (!$('#sell_price').val() || $('#sell_price').val() == '0')) {
                    $('#sell_price').val(price);
                }
                updateInvoiceCalculation();
            });

            function formatRupiah(num) {
                return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
            }

            function updateInvoiceCalculation() {
                var nights = parseInt($('#nights_display').val()) || 1;
                var rate = parseFloat($('#sell_price').val()) || 0;
                var total = nights * rate;

                $('#preview_nights').text(nights);
                $('#preview_rate').text(formatRupiah(rate));
                $('#preview_total').text(formatRupiah(total));
            }

            function calcNights() {
                var ci = new Date($('#check_in').val());
                var co = new Date($('#check_out').val());
                if (!isNaN(ci) && !isNaN(co) && co > ci) {
                    var diff = Math.ceil((co - ci) / (1000 * 60 * 60 * 24));
                    $('#nights_display').val(diff);
                }
                updateInvoiceCalculation();
            }

            $('#sell_price').on('input change', updateInvoiceCalculation);
            $('#check_in, #check_out').on('change', calcNights);
            calcNights();
        });
    </script>
@endsection
