@extends('layouts.master')
@section('title')
    New Custom Booking (Mark Up)
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
        @slot('li_1') Bookings @endslot
        @slot('title') New Custom Booking @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('bookings.store-custom') }}" method="POST" data-ajax="true">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-edit-box-line me-2 text-primary"></i>Custom Order / Markup Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i> Untuk booking via agent/reseller dengan harga markup. Gunakan form ini untuk membuat record booking "Kustom" yang tidak terikat pada stok kamar asli.
                        </div>

                        <div class="mb-3">
                            <label for="guest_id" class="form-label">Guest (Tamu) <span class="text-danger">*</span></label>
                            <select class="form-control" id="guest_id" name="guest_id" required>
                                <option value="">Cari nama tamu...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="custom_room_name" class="form-label">Nama Kamar / Layanan (Tampil di Invoice) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="custom_room_name" name="custom_room_name" placeholder="Contoh: Executive Suite Room" required>
                            <small class="text-muted">Teks ini yang akan muncul sebagai nama item di Invoice.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in" class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="{{ get_hotel_date() }}" {{ (auth()->user()->can('manage reservations') || auth()->user()->can('bookings.create')) ? '' : 'min="'.get_hotel_date().'"' }} required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="check_out" class="form-label">Tanggal Keluar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_out" name="check_out" value="{{ date('Y-m-d', strtotime(get_hotel_date() . ' +1 day')) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="real_price" class="form-label text-success">Harga Asli Hotel (Internal) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="real_price" name="real_price" placeholder="Harga yang masuk ke laporan" required>
                                </div>
                                <small class="text-muted">Nominal ini yang akan masuk ke Laporan Pendapatan.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="total_price" class="form-label text-primary">Harga di Invoice (Markup) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="total_price" name="total_price" placeholder="Harga yang tampil di Invoice" required>
                                </div>
                                <small class="text-muted">Nominal ini yang akan ditagihkan ke tamu.</small>
                            </div>
                        </div>

                        <div id="markup_summary" class="alert alert-warning py-2 d-none">
                            <i class="ri-money-dollar-circle-line me-1"></i> 
                            Estimasi Markup (Titipan Tamu): <strong id="markup_value">Rp 0</strong>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan Internal / Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Contoh: Harga asli 100rb, markup 100rb. Sisa dikembalikan ke tamu."></textarea>
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
                        <a href="{{ route('bookings.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary px-4">Simpan Custom Booking</button>
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

            const realPrice = document.getElementById('real_price');
            const totalPrice = document.getElementById('total_price');
            const summary = document.getElementById('markup_summary');
            const val = document.getElementById('markup_value');

            function calc() {
                const r = parseFloat(realPrice.value) || 0;
                const t = parseFloat(totalPrice.value) || 0;
                const m = t - r;
                
                if (m > 0) {
                    summary.classList.remove('d-none');
                    val.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(m);
                } else {
                    summary.classList.add('d-none');
                }
            }

            realPrice.addEventListener('input', calc);
            totalPrice.addEventListener('input', calc);
        });
    </script>
@endsection
