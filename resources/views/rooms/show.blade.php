@extends('layouts.master')
@section('title')
    Detail Kamar {{ $room->room_number }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('rooms.index') }}">Kamar</a>
        @endslot
        @slot('title')
            Detail Kamar {{ $room->room_number }}
        @endslot
    @endcomponent

    @php
        $rawStatus = strtolower($room->status ?? 'available');
        $badgeClass = 'bg-success-subtle text-success';
        $statusText = 'Ready / Available';

        if (in_array($rawStatus, ['available', 'clean', 'ready'])) {
            $badgeClass = 'bg-success-subtle text-success';
            $statusText = 'Ready / Clean';
        } elseif (in_array($rawStatus, ['occupied', 'in-house', 'checkin'])) {
            $badgeClass = 'bg-warning-subtle text-warning';
            $statusText = 'Occupied / In-House';
        } elseif (in_array($rawStatus, ['checkout', 'dirty', 'cleanup', 'clean up', 'cleaning'])) {
            $badgeClass = 'bg-danger-subtle text-danger';
            $statusText = 'Dirty / Need Cleaning';
        } elseif (in_array($rawStatus, ['maintenance', 'out_of_order', 'out of order', 'ooo'])) {
            $badgeClass = 'bg-secondary-subtle text-secondary';
            $statusText = 'Maintenance / OOO';
        } else {
            $badgeClass = 'bg-light text-muted';
            $statusText = ucfirst($rawStatus);
        }
    @endphp

    {{-- Header Profile Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-lg bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center fw-bold fs-24 shadow-sm" style="width: 68px; height: 68px;">
                                <i class="ri-hotel-bed-line"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="mb-0 fw-bold text-dark fs-20 fs-md-24">Kamar {{ $room->room_number }}</h3>
                                    <span class="badge {{ $badgeClass }} fs-12 px-2 py-1">{{ $statusText }}</span>
                                    @if($room->is_kos)
                                        <span class="badge bg-info-subtle text-info fs-11">Kamar Kos</span>
                                    @endif
                                </div>
                                <p class="text-muted mb-0 mt-1 fs-13">
                                    <span class="fw-semibold text-dark">{{ $room->roomType->name ?? 'Standard Room' }}</span>
                                    <span class="mx-1">•</span>
                                    Lantai {{ $room->floor ?? '1' }}
                                    <span class="mx-1">•</span>
                                    Kapasitas: {{ $room->max_occupancy ?? 2 }} Tamu
                                </p>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <a href="{{ route('bookings.calendar') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-calendar-2-line me-1"></i> Kalender Operasional
                            </a>
                            <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="btn btn-primary btn-sm">
                                <i class="ri-add-line me-1"></i> Reservasi Kamar Ini
                            </a>
                            @can('manage system')
                                <a href="{{ route('rooms.edit', $room->id) }}" class="btn btn-soft-primary btn-sm">
                                    <i class="ri-pencil-line me-1"></i> Edit Kamar
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Information Section --}}
    <div class="row">
        {{-- Left: Specifications & History --}}
        <div class="col-xl-8">
            {{-- Room Specifications --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-information-line me-2 text-primary"></i>Spesifikasi Kamar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0 fs-13">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-2" style="width: 35%;">Nomor Kamar:</td>
                                    <td class="fw-bold text-dark py-2">{{ $room->room_number }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Tipe Kamar:</td>
                                    <td class="fw-semibold text-dark py-2">{{ $room->roomType->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Lantai:</td>
                                    <td class="text-dark py-2">Lantai {{ $room->floor ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Kapasitas Maksimal:</td>
                                    <td class="text-dark py-2">{{ $room->max_occupancy ?? 2 }} Orang</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Biaya Tambahan Orang (Extra Person):</td>
                                    <td class="text-dark py-2">Rp {{ number_format($room->price_extra_person ?? 0, 0, ',', '.') }} / malam</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Housekeeper / Staf Ditugaskan:</td>
                                    <td class="text-dark py-2">
                                        @if($room->assignedStaff)
                                            <span class="badge bg-light text-dark border">
                                                <i class="ri-user-star-line me-1 text-primary"></i>{{ $room->assignedStaff->name }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">Belum ada staf yang ditugaskan</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Catatan Kamar:</td>
                                    <td class="text-dark py-2">{{ $room->notes ?: '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Room Pricing Matrix --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-money-dollar-circle-line me-2 text-primary"></i>Tarif & Harga Kamar
                    </h5>
                    <span class="badge bg-primary-subtle text-primary">IDR (Rupiah)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori Tarif</th>
                                    <th>Harga Kamar (Room Only)</th>
                                    <th>Harga Termasuk Sarapan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-dark">Umum (Public / Regular)</td>
                                    <td class="fw-bold text-primary">Rp {{ number_format($room->price_public ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-dark">Rp {{ number_format($room->price_breakfast_public ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-dark">Sales / Corporate / OTA</td>
                                    <td class="fw-bold text-info">Rp {{ number_format($room->price_sales ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-dark">Rp {{ number_format($room->price_breakfast_sales ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-dark">High Season (Weekend / Libur)</td>
                                    <td class="fw-bold text-warning">Rp {{ number_format($room->price_high_season ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-dark">Rp {{ number_format($room->price_breakfast_high_season ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @if($room->is_kos)
                                    <tr class="table-info-subtle">
                                        <td class="fw-semibold text-dark"><i class="ri-home-4-line me-1 text-primary"></i>Tarif Kos (Bulanan)</td>
                                        <td class="fw-bold text-success" colspan="2">Rp {{ number_format($room->price_kos ?? 0, 0, ',', '.') }} / bulan</td>
                                    </tr>
                                    @if($room->yearly_price > 0)
                                        <tr class="table-info-subtle">
                                            <td class="fw-semibold text-dark"><i class="ri-calendar-line me-1 text-primary"></i>Tarif Kos (Tahunan)</td>
                                            <td class="fw-bold text-success" colspan="2">Rp {{ number_format($room->yearly_price ?? 0, 0, ',', '.') }} / tahun</td>
                                        </tr>
                                    @endif
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent Bookings History --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-history-line me-2 text-primary"></i>Riwayat Reservasi Terkini
                    </h5>
                    <span class="text-muted fs-12">{{ $room->bookings->count() }} reservasi tercatat</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-12">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Nama Tamu</th>
                                    <th>Check In – Check Out</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($room->bookings as $bk)
                                    @php
                                        $in = \Carbon\Carbon::parse($bk->check_in);
                                        $out = \Carbon\Carbon::parse($bk->check_out);
                                        $nights = max(1, $out->diffInDays($in));
                                        
                                        $stClass = 'bg-secondary-subtle text-secondary';
                                        if ($bk->status === 'confirmed') $stClass = 'bg-success-subtle text-success';
                                        elseif ($bk->status === 'checked_in') $stClass = 'bg-primary-subtle text-primary';
                                        elseif ($bk->status === 'checked_out') $stClass = 'bg-dark-subtle text-dark';
                                        elseif ($bk->status === 'cancelled') $stClass = 'bg-danger-subtle text-danger';
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-primary">#{{ $bk->id }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $bk->guest->name ?? 'Guest' }}</div>
                                            @if(!empty($bk->guest->phone))
                                                <small class="text-muted">{{ $bk->guest->phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $in->format('d M Y') }}</div>
                                            <small class="text-muted">s/d {{ $out->format('d M Y') }}</small>
                                        </td>
                                        <td>{{ $nights }} malam</td>
                                        <td>
                                            <span class="badge {{ $stClass }}">{{ ucfirst(str_replace('_', ' ', $bk->status)) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('bookings.show', $bk->id) }}" class="btn btn-sm btn-soft-primary" title="Lihat Detail Reservasi">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="ri-calendar-event-line fs-24 d-block mb-1"></i>
                                            Belum ada data reservasi untuk kamar ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Maintenance Logs (If Any) --}}
            @if($room->maintenanceLogs && $room->maintenanceLogs->isNotEmpty())
                <div class="card border shadow-sm">
                    <div class="card-header bg-light-subtle py-3 border-bottom">
                        <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                            <i class="ri-tools-line me-2 text-primary"></i>Log Pemeliharaan & Perbaikan
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-12">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Kendala / Issue</th>
                                        <th>Prioritas</th>
                                        <th>Status</th>
                                        <th>Pelapor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($room->maintenanceLogs as $ml)
                                        <tr>
                                            <td>{{ $ml->created_at->format('d M Y H:i') }}</td>
                                            <td class="fw-medium text-dark">{{ $ml->issue }}</td>
                                            <td>
                                                <span class="badge {{ $ml->priority === 'high' ? 'bg-danger' : ($ml->priority === 'medium' ? 'bg-warning' : 'bg-info') }}">
                                                    {{ ucfirst($ml->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $ml->status === 'resolved' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($ml->status) }}
                                                </span>
                                            </td>
                                            <td class="text-muted">{{ $ml->reportedBy->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: Photo & Type Details --}}
        <div class="col-xl-4">
            {{-- Room Image Card --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-image-line me-2 text-primary"></i>Foto Kamar
                    </h5>
                </div>
                <div class="card-body text-center p-3">
                    @if($room->image)
                        <img src="{{ asset('storage/' . $room->image) }}" alt="Kamar {{ $room->room_number }}" class="img-fluid rounded border shadow-xs" style="max-height: 240px; width: 100%; object-fit: cover;">
                    @else
                        <div class="bg-light rounded p-5 d-flex flex-column align-items-center justify-content-center border border-dashed">
                            <i class="ri-hotel-bed-line fs-48 text-muted opacity-50 mb-2"></i>
                            <span class="text-muted fs-12">Belum ada foto kamar yang diunggah.</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Room Type Info --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-hotel-line me-2 text-primary"></i>Tipe Kamar: {{ $room->roomType->name ?? 'Standard' }}
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted fs-13 mb-3">
                        {{ $room->roomType->description ?: 'Tipe kamar standar hotel dengan fasilitas lengkap dan kenyamanan prima.' }}
                    </p>

                    @if($room->roomType && $room->roomType->base_price)
                        <div class="d-flex justify-content-between align-items-center py-2 border-top fs-13">
                            <span class="text-muted">Base Price (Standar):</span>
                            <span class="fw-bold text-dark">Rp {{ number_format($room->roomType->base_price, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if($room->checklistTemplates && $room->checklistTemplates->isNotEmpty())
                        <div class="mt-3 pt-3 border-top">
                            <h6 class="fs-12 fw-bold text-dark text-uppercase mb-2">Checklist Kebersihan:</h6>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($room->checklistTemplates as $ct)
                                    <span class="badge bg-light text-dark border">
                                        <i class="ri-checkbox-circle-line text-success me-1"></i>{{ $ct->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Navigation Actions --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light-subtle py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-links-line me-2 text-primary"></i>Aksi Cepat
                    </h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('bookings.calendar') }}" class="btn btn-outline-secondary text-start w-100">
                        <i class="ri-calendar-2-line me-2 text-primary"></i> Buka Kalender Operasional
                    </a>
                    <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="btn btn-soft-primary text-start w-100">
                        <i class="ri-add-circle-line me-2 text-primary"></i> Buat Reservasi di Kamar Ini
                    </a>
                    <a href="{{ route('rooms.index') }}" class="btn btn-light text-start w-100 border">
                        <i class="ri-hotel-bed-line me-2 text-muted"></i> Kembali ke Daftar Kamar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
