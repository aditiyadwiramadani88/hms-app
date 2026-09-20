@extends('layouts.master')
@section('title')
    Perpanjang {{ $booking->stay_type === 'monthly' ? 'Kost' : 'Booking' }} - #{{ $booking->id }}
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Bookings
        @endslot
        @slot('li_2')
            <a href="{{ route('bookings.show', $booking->id) }}">Booking #{{ $booking->id }}</a>
        @endslot
        @slot('title')
            Perpanjang {{ $booking->stay_type === 'monthly' ? 'Kost' : 'Booking' }}
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Perpanjang Masa {{ $booking->stay_type === 'monthly' ? 'Kost' : 'Menginap' }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-1">Tamu</p>
                                <h5 class="mb-0">{{ $booking->guest->name ?? 'N/A' }}</h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted mb-1">Kamar</p>
                                <h5 class="mb-0">{{ $room?->room_number ?? 'N/A' }}</h5>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-1">Check-in</p>
                                <h5 class="mb-0">{{ $booking->check_in->format('d M Y') }}</h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted mb-1">Check-out Saat Ini</p>
                                <h5 class="mb-0">{{ $booking->check_out->format('d M Y') }}</h5>
                            </div>
                        </div>
                    </div>

                    @if($booking->stay_type === 'monthly')
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            Harga per bulan: <strong>Rp {{ number_format($pricePerMonth, 0, ',', '.') }}</strong>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            Estimasi harga per malam: <strong>Rp {{ number_format($pricePerNight, 0, ',', '.') }}</strong>
                            <br><small class="text-muted">* Harga dapat berubah sesuai dynamic pricing (hari libur/akhir pekan).</small>
                        </div>
                    @endif

                    @if(!empty($isBlocked) && $isBlocked)
                        <div class="alert alert-danger">
                            <i class="ri-error-warning-line me-2"></i>
                            <strong>Kamar Tidak Dapat Diperpanjang:</strong> Kamar ini sudah dipesan oleh reservasi lain (#{{ $nextBooking->id }} - {{ $nextBooking->guest?->name ?? 'Tamu' }}) mulai tanggal <strong>{{ $booking->check_out->format('d M Y') }}</strong>.
                            <div class="mt-2">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-arrow-left-line me-1"></i> Buka Booking & Pindah Kamar
                                </a>
                            </div>
                        </div>
                    @elseif(!empty($maxNewCheckOut))
                        <div class="alert alert-warning">
                            <i class="ri-information-line me-2"></i>
                            <strong>Batas Maksimal:</strong> Kamar ini memiliki reservasi tamu lain (#{{ $nextBooking->id }}) mulai tanggal <strong>{{ \Carbon\Carbon::parse($maxNewCheckOut)->format('d M Y') }}</strong>. Perpanjangan maksimal sampai tanggal tersebut.
                        </div>
                    @endif

                    <form action="{{ route('bookings.extend.process', $booking->id) }}" method="POST" data-ajax="true">
                        @csrf

                        <div class="mb-3">
                            <label for="new_check_out" class="form-label">Perpanjang Sampai</label>
                            <input type="date" class="form-control" id="new_check_out" name="new_check_out" 
                                   min="{{ $minNewCheckOut }}" 
                                   @if(!empty($maxNewCheckOut)) max="{{ $maxNewCheckOut }}" @endif 
                                   {{ !empty($isBlocked) && $isBlocked ? 'disabled' : 'required' }}>
                            <div class="form-text">
                                @if(!empty($maxNewCheckOut))
                                    Maksimal s/d {{ \Carbon\Carbon::parse($maxNewCheckOut)->format('d M Y') }}
                                @else
                                    Pilih tanggal check-out baru untuk melihat rincian biaya perpanjangan
                                @endif
                            </div>
                        </div>

                        {{-- Loading Indicator --}}
                        <div id="extend_preview_loading" class="text-center py-2 text-muted mb-3" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            <span class="fs-13">Menghitung rincian subtotal perpanjangan...</span>
                        </div>

                        {{-- Error Alert --}}
                        <div id="extend_preview_error" class="alert alert-danger py-2 mb-3" style="display: none;"></div>

                        {{-- Subtotal Preview Box --}}
                        <div id="extend_preview_box" class="card border border-primary-subtle bg-light mb-3" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="fs-13 fw-semibold text-primary mb-2">
                                    <i class="ri-calculator-line me-1"></i> Rincian Subtotal Perpanjangan
                                </h6>
                                <table class="table table-sm table-borderless mb-0 fs-13">
                                    <tr>
                                        <td class="text-muted ps-0 py-1">Durasi Tambahan:</td>
                                        <td class="text-end fw-medium py-1" id="prev_additional_days">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0 py-1">Biaya Kamar:</td>
                                        <td class="text-end fw-medium py-1" id="prev_room_cost">-</td>
                                    </tr>
                                    <tr id="prev_breakfast_row" style="display: none;">
                                        <td class="text-muted ps-0 py-1">Biaya Sarapan:</td>
                                        <td class="text-end fw-medium py-1" id="prev_breakfast_cost">-</td>
                                    </tr>
                                    <tr id="prev_tax_row" style="display: none;">
                                        <td class="text-muted ps-0 py-1">Pajak (Tax):</td>
                                        <td class="text-end fw-medium py-1" id="prev_tax_cost">-</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold ps-0 py-2 text-success">Total Biaya Tambahan:</td>
                                        <td class="text-end fw-bold py-2 text-success fs-14" id="prev_total_additional">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0 py-1">Tagihan Saat Ini:</td>
                                        <td class="text-end text-muted py-1" id="prev_current_total">-</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold ps-0 py-2 fs-14">Grand Total Baru:</td>
                                        <td class="text-end fw-bold py-2 fs-15 text-primary" id="prev_grand_total">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="btnSubmitExtend" data-submit-protect="true" class="btn btn-primary" {{ !empty($isBlocked) && $isBlocked ? 'disabled' : '' }}>
                                <i class="ri-check-line me-1"></i> Simpan Perpanjangan
                            </button>
                            <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Rincian subtotal akan otomatis dihitung saat memilih tanggal check-out baru.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Total tagihan (Grand Total) dan invoice akan langsung diperbarui setelah disimpan.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Untuk harian, tarif malam dihitung berdasarkan rata-rata tarif kamar booking awal.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Untuk bulanan (kost), tarif perpanjangan dihitung secara prorata harian (harga kost / 30 hari).</li>
                        @if($booking->include_breakfast)
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Booking ini menyertakan sarapan, sehingga biaya sarapan otomatis dihitung untuk hari perpanjangan.</li>
                        @endif
                        <li class="mb-0"><i class="ri-check-double-line text-success me-2"></i> Perubahan ini akan mencatat periode inap baru secara otomatis di invoice.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        @if(session('success'))
            Toastify({ text: "{{ session('success') }}", duration: 5000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
        @endif
        @if(session('error'))
            Toastify({ text: "{{ session('error') }}", duration: 5000, gravity: "top", position: "right", style: { background: "#f06548" } }).showToast();
        @endif

        function formatRupiah(num) {
            return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        const inputNewCheckout = document.getElementById('new_check_out');
        const previewBox = document.getElementById('extend_preview_box');
        const previewLoading = document.getElementById('extend_preview_loading');
        const previewError = document.getElementById('extend_preview_error');
        const submitBtn = document.getElementById('btnSubmitExtend');

        function fetchExtendPreview() {
            if (!inputNewCheckout) return;
            const val = inputNewCheckout.value;
            if (!val) {
                previewBox.style.display = 'none';
                previewError.style.display = 'none';
                return;
            }

            previewLoading.style.display = 'block';
            previewBox.style.display = 'none';
            previewError.style.display = 'none';

            fetch(`{{ route('bookings.extend.preview', $booking->id) }}?new_check_out=${val}`)
                .then(res => res.json())
                .then(res => {
                    previewLoading.style.display = 'none';
                    if (!res.success) {
                        previewError.textContent = res.message || 'Gagal menghitung biaya perpanjangan.';
                        previewError.style.display = 'block';
                        if (submitBtn) submitBtn.disabled = true;
                        return;
                    }

                    const d = res.data;
                    document.getElementById('prev_additional_days').textContent = d.additional_days + (d.stay_type === 'monthly' ? ' hari' : ' malam');
                    document.getElementById('prev_room_cost').textContent = formatRupiah(d.room_additional_cost);

                    if (d.include_breakfast && d.breakfast_additional_cost > 0) {
                        document.getElementById('prev_breakfast_row').style.display = '';
                        document.getElementById('prev_breakfast_cost').textContent = formatRupiah(d.breakfast_additional_cost);
                    } else {
                        document.getElementById('prev_breakfast_row').style.display = 'none';
                    }

                    if (d.tax_additional > 0) {
                        document.getElementById('prev_tax_row').style.display = '';
                        document.getElementById('prev_tax_cost').textContent = formatRupiah(d.tax_additional);
                    } else {
                        document.getElementById('prev_tax_row').style.display = 'none';
                    }

                    document.getElementById('prev_total_additional').textContent = '+ ' + formatRupiah(d.total_additional);
                    document.getElementById('prev_current_total').textContent = formatRupiah(d.current_total);
                    document.getElementById('prev_grand_total').textContent = formatRupiah(d.new_grand_total);

                    previewBox.style.display = 'block';
                    if (submitBtn) submitBtn.disabled = false;
                })
                .catch(err => {
                    previewLoading.style.display = 'none';
                    previewError.textContent = 'Terjadi kesalahan saat memproses perhitungan perpanjangan.';
                    previewError.style.display = 'block';
                });
        }

        if (inputNewCheckout) {
            inputNewCheckout.addEventListener('change', fetchExtendPreview);
            if (inputNewCheckout.value) {
                fetchExtendPreview();
            }
        }
    </script>
@endsection