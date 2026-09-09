@extends('layouts.master')
@section('title')
    New Custom Invoice
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
        @slot('title') New Custom Invoice @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('custom-invoices.store') }}" method="POST" data-ajax="true">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-file-add-line me-2 text-primary"></i>Custom Invoice Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i> Buat invoice untuk booking dari OTA/agent. Invoice ini tidak terikat pada stok kamar hotel.
                        </div>

                        <div class="mb-3">
                            <label for="guest_id" class="form-label">Guest (Tamu) <span class="text-danger">*</span></label>
                            <select class="form-control" id="guest_id" name="guest_id" required>
                                <option value="">Cari nama tamu...</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="source" class="form-label">Source / Agent <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="source" name="source" placeholder="Contoh: Traveloka, Agoda, etc." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_name" class="form-label">Nama Kamar / Layanan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="room_name" name="room_name" placeholder="Contoh: Executive Suite Room" required>
                                <small class="text-muted">Teks ini yang akan muncul sebagai nama item di Invoice.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="check_in" class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="{{ get_hotel_date() }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="check_out" class="form-label">Tanggal Keluar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_out" name="check_out" value="{{ date('Y-m-d', strtotime(get_hotel_date() . ' +1 day')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nights_display" class="form-label">Malam</label>
                                <input type="text" class="form-control" id="nights_display" value="1" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adults" class="form-label">Dewasa <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="adults" name="adults" value="1" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="children" class="form-label">Anak-anak</label>
                                <input type="number" class="form-control" id="children" name="children" value="0" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sell_price" class="form-label text-primary">Harga Jual (Sell Price) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="sell_price" name="sell_price" placeholder="Harga yang ditagih ke tamu" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="agent_commission" class="form-label text-muted">Komisi Agent (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="agent_commission" name="agent_commission" value="0" placeholder="Untuk tracking margin">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan / Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan untuk invoice ini..."></textarea>
                        </div>

                        <hr class="border-dashed">

                        <div class="mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="create_booking" name="create_booking" value="1">
                                <label class="form-check-label fw-medium" for="create_booking">Buat juga sebagai Booking Room</label>
                            </div>
                            <small class="text-muted">Centang jika booking ini juga membutuhkan kamar fisik di hotel.</small>
                        </div>

                        <div id="booking_room_section" class="d-none">
                            <div class="alert alert-warning">
                                <i class="ri-door-open-line me-2"></i> Booking akan dibuat di tabel <strong>bookings</strong> dan terkait dengan invoice ini.
                            </div>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Pilih Kamar <span class="text-danger">*</span></label>
                                <select class="form-select" id="room_id" name="room_id">
                                    <option value="">-- Pilih Kamar --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->roomType->name ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        <h5 class="fs-14 mb-3">Initial Payment (Opsional)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nominal Bayar</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="down_payment" value="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Akun Pembayaran</label>
                                <select class="form-select" name="bank_account_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('custom-invoices.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary px-4">Simpan Custom Invoice</button>
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

            $('#create_booking').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#booking_room_section').removeClass('d-none');
                    $('#room_id').prop('required', true);
                } else {
                    $('#booking_room_section').addClass('d-none');
                    $('#room_id').prop('required', false).val('');
                }
            });

            function calcNights() {
                var ci = new Date($('#check_in').val());
                var co = new Date($('#check_out').val());
                if (!isNaN(ci) && !isNaN(co) && co > ci) {
                    var diff = Math.ceil((co - ci) / (1000 * 60 * 60 * 24));
                    $('#nights_display').val(diff);
                }
            }

            $('#check_in, #check_out').on('change', calcNights);
            calcNights();
        });
    </script>
@endsection
