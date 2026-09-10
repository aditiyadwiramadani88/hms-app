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
                                    Pilih tanggal check-out baru
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary" {{ !empty($isBlocked) && $isBlocked ? 'disabled' : '' }}>
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
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Biaya tambahan akan otomatis dihitung dan ditambahkan ke tagihan.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Total tagihan (Grand Total) akan diperbarui setelah disimpan.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Untuk harian, harga mengikuti sistem <em>dynamic pricing</em>.</li>
                        <li class="mb-2"><i class="ri-check-double-line text-success me-2"></i> Untuk bulanan, dihitung per bulan (pembulatan ke atas).</li>
                        <li class="mb-0"><i class="ri-check-double-line text-success me-2"></i> Perubahan ini akan mencatat periode inap baru di invoice.</li>
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
    </script>
@endsection