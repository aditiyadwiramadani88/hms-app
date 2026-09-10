@extends('layouts.master')
@section('title')
    Booking Details
@endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Bookings
        @endslot
        @slot('title')
            Booking Details
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0">Booking #{{ $booking->id }}</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'info') }} text-uppercase">
                                {{ $booking->status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Guest</p>
                            <h5 class="fs-14 mb-0">{{ $booking->guest->name ?? 'N/A' }}</h5>
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Room</p>
                            <h5 class="fs-14 mb-0">
                                @if($booking->is_custom)
                                    <span class="text-info"><i class="ri-edit-box-line me-1"></i>{{ $booking->custom_room_name }}</span>
                                @else
                                    {{ $booking->room?->room_number ?? 'N/A' }}
                                @endif
                            </h5>
                            <small class="text-muted">{{ !$booking->is_custom ? ($booking->room?->roomType?->name ?? '') : 'Custom Booking' }}</small>
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Check-in</p>
                            <h5 class="fs-14 mb-0">{{ $booking->check_in ? $booking->check_in->format('d M Y') : 'N/A' }}</h5>
                            @if($booking->check_in_time)
                            <small class="text-muted">{{ $booking->check_in_time }} WIB</small>
                            @endif
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Check-out</p>
                            <h5 class="fs-14 mb-0">{{ $booking->check_out ? $booking->check_out->format('d M Y') : 'N/A' }}</h5>
                            @if($booking->check_out_time)
                            <small class="text-muted">{{ $booking->check_out_time }} WIB</small>
                            @endif
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Sumber</p>
                            <h5 class="fs-14 mb-0">
                                @php
                                    $src = null;
                                    if ($booking->bookingSource) {
                                        $src = ['label' => $booking->bookingSource->name, 'color' => $booking->bookingSource->color];
                                    } else {
                                        $sourceLabels = [
                                            'walk_in' => ['label' => 'Walk-in', 'color' => 'secondary'],
                                            'whatsapp' => ['label' => 'WhatsApp', 'color' => 'success'],
                                            'traveloka' => ['label' => 'Traveloka', 'color' => 'primary'],
                                            'agoda' => ['label' => 'Agoda', 'color' => 'danger'],
                                            'booking_com' => ['label' => 'Booking.com', 'color' => 'info'],
                                            'tiket_com' => ['label' => 'Tiket.com', 'color' => 'warning'],
                                            'airbnb' => ['label' => 'Airbnb', 'color' => 'danger'],
                                            'instagram' => ['label' => 'Instagram', 'color' => 'warning'],
                                            'other' => ['label' => 'Lainnya', 'color' => 'secondary'],
                                        ];
                                        $src = $sourceLabels[$booking->source] ?? ['label' => ucfirst($booking->source ?? 'Walk-in'), 'color' => 'secondary'];
                                    }
                                    $textColor = in_array($src['color'], ['light', 'white']) ? 'dark' : $src['color'];
                                @endphp
                                <span class="badge bg-secondary-subtle text-secondary">{{ $src['label'] }}</span>
                            </h5>
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Occupancy</p>
                            <h5 class="fs-14 mb-0">{{ $booking->adults }} Adult, {{ $booking->children }} Child</h5>
                        </div>
                        <div class="col-lg col-6">
                            <p class="text-muted mb-2 text-uppercase fw-semibold">Breakfast</p>
                            <h5 class="fs-14 mb-0">
                                @if($booking->include_breakfast)
                                    <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Include</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                @endif
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing Breakdown --}}
            @if(!auth()->user()->hasRole('Front Page Only'))
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-md-between gap-3">
                    <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-fill me-2 text-success"></i>Pricing Breakdown</h5>
                    <div class="d-flex flex-wrap gap-2">
                        @can('bookings.edit')
                        <button type="button" class="btn btn-soft-warning btn-sm" id="btnAuditPricing" data-booking-id="{{ $booking->id }}">
                            <i class="ri-search-eye-line align-middle me-1"></i> Audit Pricing
                        </button>
                        @endcan
                        @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                            @can('bookings.discount')
                            <button type="button" class="btn btn-soft-danger btn-sm" data-bs-toggle="modal" data-bs-target="#applyDiscountModal">
                                <i class="ri-percent-line align-middle me-1"></i> Manual Discount / Voucher
                            </button>
                            @endcan
                            @can('bookings.charge')
                            <button type="button" class="btn btn-soft-info btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomChargeModal">
                                <i class="ri-add-circle-line align-middle me-1"></i> Add Custom Charge
                            </button>
                            <button type="button" class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="ri-add-line align-middle me-1"></i> Add Rental Item
                            </button>
                                @endif
                            @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-nowrap align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td>{{ $booking->is_custom ? 'Custom Item/Room' : 'Room Rate' }} ({{ $booking->is_custom ? $booking->custom_room_name : ($booking->room?->room_number ?? 'N/A') }})</td>
                                    <td class="text-end fw-medium">
                                        @if($booking->is_custom)
                                            Rp {{ number_format($booking->base_price, 0, ',', '.') }} (Total)
                                        @elseif($booking->stay_type === 'monthly')
                                            Rp {{ number_format($booking->room->price_kos ?? $booking->room?->roomType?->base_price ?? 0, 0, ',', '.') }} / bulan (Kost)
                                        @else
                                            @php
                                                $nightlyRate = $booking->room?->roomType?->base_price ?? 0;
                                                if ($booking->pricing_breakdown && is_array($booking->pricing_breakdown)) {
                                                    $firstNight = collect($booking->pricing_breakdown)->first(fn($d) => is_array($d) && isset($d['price']));
                                                    if ($firstNight) $nightlyRate = $firstNight['price'];
                                                } elseif ($booking->base_price && $booking->check_in && $booking->check_out) {
                                                    $nights = $booking->check_in->diffInDays($booking->check_out);
                                                    if ($nights > 0) $nightlyRate = $booking->base_price / $nights;
                                                }
                                            @endphp
                                            Rp {{ number_format($nightlyRate, 0, ',', '.') }} / night
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ $booking->stay_type === 'monthly' ? 'Duration' : 'Number of Nights' }}</td>
                                    <td class="text-end fw-medium">
                                        @if($booking->stay_type === 'monthly')
                                            @php $totalNights = $booking->check_in->diffInDays($booking->check_out); @endphp
                                            {{ (int) round($totalNights / 30) }} Bulan ({{ $totalNights }} Malam)
                                        @else
                                            {{ $booking->check_in->diffInDays($booking->check_out) }}
                                        @endif
                                    </td>
                                </tr>

                                {{-- Detailed Breakdown --}}
                                @if($booking->pricing_breakdown && is_array($booking->pricing_breakdown))
                                    @if($booking->stay_type === 'monthly')
                                    {{-- Monthly: Summary only, no per-night table --}}
                                    @php
                                        $periods = $booking->pricing_breakdown['periods'] ?? null;
                                        $totalNights = $booking->check_in->diffInDays($booking->check_out);
                                        $totalMonths = max(1, (int) round($totalNights / 30));
                                        $nightlyEntries = collect($booking->pricing_breakdown)->filter(fn($item) => is_array($item) && (isset($item['date']) || isset($item['night'])));
                                    @endphp
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="2" class="fw-semibold pt-2">Monthly Breakdown</td>
                                    </tr>
                                    @if($periods && is_array($periods) && count($periods) > 1)
                                        @foreach($periods as $idx => $p)
                                        @php
                                            $pNights = (int) ($p['nights'] ?? 0);
                                            $pMonths = (int) round($pNights / 30);
                                        @endphp
                                        <tr class="fs-13">
                                            <td class="ps-3 text-muted">
                                                @if(isset($p['label']))
                                                    <span class="badge bg-warning-subtle text-warning me-1">{{ $p['label'] }}</span>
                                                @else
                                                    <span class="text-muted">Periode {{ $idx + 1 }}:</span>
                                                @endif
                                                {{ \Carbon\Carbon::parse($p['start'])->format('d M Y') }} ... {{ \Carbon\Carbon::parse($p['end'])->format('d M Y') }}
                                            </td>
                                            <td class="text-end text-muted">{{ $pMonths }} Bulan ({{ $pNights }} malam)</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr class="fs-13">
                                            <td class="ps-3 text-muted">
                                                {{ \Carbon\Carbon::parse($nightlyEntries->first()['date'] ?? $booking->check_in)->format('d M Y') }}
                                                ...
                                                {{ \Carbon\Carbon::parse($nightlyEntries->last()['date'] ?? $booking->check_out)->format('d M Y') }}
                                            </td>
                                            <td class="text-end text-muted">{{ $totalMonths }} Bulan ({{ $totalNights }} malam)</td>
                                        </tr>
                                    @endif
                                    @else
                                    {{-- Daily: Collapsible per-night table --}}
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="2" class="fw-semibold py-2 d-flex justify-content-between align-items-center">
                                            <span>Per-Night Breakdown</span>
                                            <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#fullBreakdown" aria-expanded="false">
                                                View Details <i class="ri-arrow-down-s-line align-middle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="p-0">
                                            <div class="collapse" id="fullBreakdown">
                                                <div class="bg-light-subtle p-2 rounded mb-2 border border-dashed">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tbody>
                                                            @if(is_array($booking->pricing_breakdown))
                                                            @foreach($booking->pricing_breakdown as $key => $day)
                                                            @if(is_array($day) && (isset($day['date']) || isset($day['night'])))
                                                            <tr class="fs-12">
                                                                <td class="text-muted">
                                                                    @if(isset($day['date']))
                                                                        {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} ({{ $day['day_of_week'] ?? '' }})
                                                                    @else
                                                                        Malam {{ $day['night'] }}
                                                                    @endif
                                                                </td>
                                                                <td class="text-end text-muted">Rp {{ number_format($day['price'] ?? 0, 0, ',', '.') }}</td>
                                                            </tr>
                                                            @endif
                                                            @endforeach
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="ps-3 fs-13 text-muted mb-2 summary-text">
                                                @php
                                                    $breakdown = is_array($booking->pricing_breakdown) ? collect($booking->pricing_breakdown)->filter(fn($item) => is_array($item) && (isset($item['date']) || isset($item['night']))) : collect();
                                                    $periods = $booking->pricing_breakdown["periods"] ?? null;
                                                @endphp
                                                @if($periods && is_array($periods) && count($periods) > 1)
                                                    @foreach($periods as $idx => $p)
                                                    <div class="mb-1">
                                                        @if(isset($p["label"]))
                                                            <span class="badge bg-warning-subtle text-warning me-1">{{ $p["label"] }}</span>
                                                        @else
                                                            <span class="text-muted">Periode {{ $idx + 1 }}:</span>
                                                        @endif
                                                        {{ \Carbon\Carbon::parse($p["start"])->format("d M Y") }}
                                                        <span class="mx-1">...</span>
                                                        {{ \Carbon\Carbon::parse($p["end"])->format("d M Y") }}
                                                        <span class="badge bg-info-subtle text-info ms-1">{{ $p["nights"] }} Nights</span>
                                                    </div>
                                                    @endforeach
                                                @else
                                                    @php
                                                        $firstDay = $breakdown->first();
                                                        $lastDay = $breakdown->last();
                                                    @endphp
                                                    @if($firstDay && is_array($firstDay) && isset($firstDay['date']))
                                                    {{ \Carbon\Carbon::parse($firstDay['date'])->format('d M Y') }}
                                                    <span class="mx-2 text-primary">...</span>
                                                    {{ \Carbon\Carbon::parse($lastDay['date'])->format('d M Y') }}
                                                    <span class="badge bg-info-subtle text-info ms-2">{{ $breakdown->count() }} Nights</span>
                                                    @elseif($breakdown->isNotEmpty())
                                                    <span class="badge bg-info-subtle text-info">{{ $breakdown->count() }} Nights</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                @endif

                                <tr class="border-top border-top-dashed">
                                    <td>Room Subtotal</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($booking->base_price, 0, ',', '.') }}</td>
                                </tr>

                                @if($booking->include_breakfast)
                                @php
                                    $breakfastTotal = $booking->pricing_breakdown['breakfast_total'] ?? 0;
                                    $nights = $booking->check_in->diffInDays($booking->check_out);
                                    $breakfastPerNight = $nights > 0 ? $breakfastTotal / $nights : $breakfastTotal;
                                @endphp
                                <tr>
                                    <td>
                                        <i class="ri-restaurant-line me-1 text-warning"></i>Breakfast ({{ $nights }} malam × Rp {{ number_format($breakfastPerNight, 0, ',', '.') }})
                                    </td>
                                    <td class="text-end fw-medium">Rp {{ number_format($breakfastTotal, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                <!-- Vehicle Rentals -->
                                @php $vehicleRentals = \App\Models\VehicleRental::where('booking_id', $booking->id)->get(); @endphp
                                @if($vehicleRentals->count() > 0)
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="2" class="fw-semibold py-2">Sewa Kendaraan</td>
                                    </tr>
                                    @foreach($vehicleRentals as $rental)
                                    <tr class="fs-12">
                                        <td class="ps-3 text-muted">
                                            - {{ $rental->vehicle_plate_number }} ({{ $rental->renter_name }})
                                        </td>
                                        <td class="text-end text-muted">Rp {{ number_format($rental->rental_days * $rental->daily_price, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                @endif

                                {{-- Unified Extra Charges (Manual + POS) --}}
                                @if($extraCharges->count() > 0 || $posOrders->count() > 0)
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="2" class="fw-semibold py-2">Extra Charges (Snacks/Services)</td>
                                    </tr>

                                    @foreach($extraCharges as $charge)
                                    <tr class="fs-12">
                                        <td class="ps-3 text-muted">
                                            - {{ $charge->description }}
                                            @can('bookings.charge.delete')
                                                @php
                                                    $chIsLocked = $booking->transactions()
                                                        ->where('type', 'payment')
                                                        ->where('status', 'success')
                                                        ->where('created_at', '>=', $charge->created_at)
                                                        ->exists();
                                                @endphp
                                                @if(!$chIsLocked && $booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                                <form action="{{ route('bookings.delete-charge', [$booking->id, $charge->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline ms-2" onsubmit="return confirm('Hapus charge &quot;{{ $charge->description }} - Rp {{ number_format($charge->amount, 0, ',', '.') }}&quot;? Total tagihan booking akan berkurang.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0 m-0 fs-11" style="text-decoration: none;">
                                                        <i class="ri-delete-bin-line"></i> hapus
                                                    </button>
                                                </form>
                                                @endif
                                            @endcan
                                        </td>
                                        <td class="text-end text-muted">Rp {{ number_format($charge->amount, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach

                                    @foreach($posOrders as $order)
                                        @foreach($order->items as $item)
                                        <tr class="fs-12">
                                            <td class="ps-3 text-muted">
                                                - {{ $item->item_name }} (x{{ $item->quantity }})
                                                @php
                                                    $isLocked = $booking->transactions()
                                                        ->where('type', 'payment')
                                                        ->where('status', 'success')
                                                        ->where('created_at', '>=', $item->created_at)
                                                        ->exists();
                                                @endphp
                                                @if(!$isLocked && $booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                                    @can('bookings.delete.unlimited')
                                                    <form action="{{ route('pos.delete-item', [$order->id, $item->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline ms-2" onsubmit="return confirm('Hapus item ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0 m-0 fs-11" style="text-decoration: none;">
                                                            <i class="ri-delete-bin-line"></i> hapus
                                                        </button>
                                                    </form>
                                                    @endcan
                                                @endif
                                            </td>
                                            <td class="text-end text-muted">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    @endforeach
                                @endif

                                {{-- Deposit Items Section --}}
                                @if($depositCharges->count() > 0)
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="2" class="fw-semibold py-2">Deposit Items (Refundable)</td>
                                    </tr>
                                    @foreach($depositCharges as $deposit)
                                    @php
                                        $isRefunded = $depositRefunds->contains(function($r) use ($deposit) {
                                            return str_contains($r->description, $deposit->description);
                                        });
                                    @endphp
                                    <tr class="fs-12">
                                        <td class="ps-3 text-muted">
                                            - {{ $deposit->description }}
                                            @if($isRefunded)
                                                <span class="badge bg-success-subtle text-success ms-1 fs-10">Sudah Dikembalikan</span>
                                            @else
                                                @php
                                                    $hasPayment = $booking->transactions()->where('type', 'payment')->where('status', 'success')->exists();
                                                @endphp
                                                @if($hasPayment && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                                <form action="{{ route('bookings.refund-deposit', [$booking->id, $deposit->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-confirm="Kembalikan deposit ini?" class="d-inline ms-2">
                                                    @csrf
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-primary p-0 m-0 fs-11" style="text-decoration: none;">
                                                        <i class="ri-refund-line"></i> Refund
                                                    </button>
                                                </form>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end text-muted">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="fs-13">
                                        <td class="ps-3 fw-medium">Total Deposit</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($totalRefunded > 0)
                                    <tr class="fs-13 text-success">
                                        <td class="ps-3">Sudah Dikembalikan</td>
                                        <td class="text-end">- Rp {{ number_format($totalRefunded, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="fs-13 text-warning">
                                        <td class="ps-3 fw-medium">Outstanding Deposit</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($depositOutstanding, 0, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    @php
                                        $hasPayment = $booking->transactions()->where('type', 'payment')->where('status', 'success')->exists();
                                    @endphp
                                    @if($hasPayment && $booking->status !== 'cancelled' && $booking->status !== 'no_show' && $depositOutstanding > 0)
                                    <tr>
                                        <td colspan="2" class="ps-3 py-1">
                                            <form action="{{ route('bookings.refund-all-deposits', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-confirm="Kembalikan semua deposit yang outstanding?" class="d-inline">
                                                @csrf
                                                <button type="submit" data-submit-protect="true" class="btn btn-sm btn-soft-primary">
                                                    <i class="ri-refund-2-line me-1"></i> Refund Semua Deposit
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endif
                                @endif

                                @if(($booking->deposit_amount ?? 0) > 0)
                                <tr class="border-top border-top-dashed">
                                    <td>
                                        Security Deposit (Jaminan)
                                        @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                            @can('bookings.payment.edit')
                                            <button type="button" class="btn btn-link text-warning p-0 m-0 fs-11 ms-1" style="text-decoration: none; line-height: 1;" data-bs-toggle="modal" data-bs-target="#editDepositModal">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            @endcan
                                            @can('bookings.payment.delete')
                                            <form action="{{ route('bookings.delete-deposit', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline" onsubmit="return confirm('Hapus deposit jaminan Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0 m-0 fs-11" style="text-decoration: none; line-height: 1;">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                    </td>
                                    <td class="text-end fw-medium">Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                @if(($booking->discount_amount ?? 0) > 0)
                                <tr class="text-success">
                                    <td>Discount @if($booking->voucher_code)({{ $booking->voucher_code }})@endif</td>
                                    <td class="text-end fw-medium">- Rp {{ number_format($booking->discount_amount ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                @if(($booking->tax_amount ?? 0) > 0)
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($booking->tax_amount ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                <tr class="border-top border-top-dashed fs-15">
                                    <th scope="row">Grand Total</th>
                                    <th class="text-end">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                                </tr>
                                @if(($totalRefunded ?? 0) > 0)
                                <tr class="text-success fs-13">
                                    <td>Deposit Sudah Dikembalikan</td>
                                    <td class="text-end">- Rp {{ number_format($totalRefunded, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="fs-13">
                                    <td class="fw-medium">Total Bersih (setelah refund deposit)</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($grandTotal - $totalRefunded, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr class="table-light">
                                    <th scope="row">Remaining Balance</th>
                                    <th class="text-end text-danger fs-16">Rp {{ number_format($remainingBalance, 0, ',', '.') }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
                @endif

                {{-- Transactions and POS --}}
                @if(!auth()->user()->hasRole('Front Page Only'))
                <div class="card">
                <div class="card-header border-0">
                    <h5 class="card-title mb-0"><i class="ri-bank-card-fill me-2 text-info"></i>Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Account</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($booking->transactions->where('type', 'payment') as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at ? $transaction->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ ucfirst($transaction->payment_method ?? 'N/A') }}</span>
                                    </td>
                                    <td>{{ $transaction->bankAccount->name ?? '-' }}</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <span class="badge bg-{{ $transaction->status === 'success' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }} text-uppercase">{{ $transaction->status }}</span>
                                            @if($transaction->status === 'success')
                                                @can('bookings.payment.edit.unlimited')
                                                <button type="button" class="btn btn-soft-warning btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $transaction->id }}">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                @elsecan('bookings.payment.edit')
                                                    @php
                                                        $payEditDeadline = $transaction->created_at->addHours(24);
                                                        $payEditRemaining = now()->diffInSeconds($payEditDeadline, false);
                                                    @endphp
                                                    @if($payEditRemaining > 0)
                                                    <button type="button" class="btn btn-soft-warning btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $transaction->id }}">
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                    @endif
                                                @endcan
                                                @can('bookings.payment.delete.unlimited')
                                                <form action="{{ route('bookings.delete-payment', [$booking->id, $transaction->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline" onsubmit="return confirm('Hapus transaksi pembayaran ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-soft-danger btn-sm btn-icon">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                                @elsecan('bookings.payment.delete')
                                                    @php
                                                        $payDelDeadline = $transaction->created_at->addHours(24);
                                                        $payDelRemaining = now()->diffInSeconds($payDelDeadline, false);
                                                    @endphp
                                                    @if($payDelRemaining > 0)
                                                    <form action="{{ route('bookings.delete-payment', [$booking->id, $transaction->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline" onsubmit="return confirm('Hapus transaksi pembayaran ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" data-submit-protect="true" class="btn btn-soft-danger btn-sm btn-icon">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                @if($transaction->status === 'success')
                                <!-- Edit Payment Modal for Transaction {{ $transaction->id }} -->
                                <div class="modal fade" id="editPaymentModal{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning-subtle">
                                                <h5 class="modal-title">Edit Payment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('bookings.edit-payment', [$booking->id, $transaction->id]) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted text-uppercase fw-semibold fs-11">Payment Account</label>
                                                        <select class="form-select" name="bank_account_id" required>
                                                            @foreach($bankAccounts as $account)
                                                                <option value="{{ $account->id }}" {{ $transaction->bank_account_id == $account->id ? 'selected' : '' }}>
                                                                    {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted text-uppercase fw-semibold fs-11">Amount</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" class="form-control" name="amount" value="{{ (int)$transaction->amount }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted text-uppercase fw-semibold fs-11">Notes</label>
                                                        <textarea class="form-control" name="description" rows="2">{{ $transaction->description }}</textarea>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label text-muted text-uppercase fw-semibold fs-11">Payment Date</label>
                                                        <input type="datetime-local" class="form-control" name="payment_date" value="{{ $transaction->created_at ? $transaction->created_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" data-submit-protect="true" class="btn btn-warning">Update Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No payments recorded yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Vehicle Logs & Registered Vehicles Section --}}
            @php
                $vehicleLogs = \App\Models\VehicleLog::where(function($q) use ($booking) {
                    $q->where('booking_id', $booking->id);
                    if ($booking->room) {
                        $q->orWhere(function($sq) use ($booking) {
                            $sq->where('destination_room', $booking->room->room_number)
                               ->where('hotel_id', active_hotel_id())
                               ->whereDate('time_in', '>=', $booking->check_in?->format('Y-m-d'))
                               ->whereDate('time_in', '<=', $booking->check_out?->format('Y-m-d'));
                        });
                    }
                })->with('securityIn')->latest('time_in')->get();

                $guestVehicles = $booking->guest_id ? \App\Models\GuestVehicle::where('guest_id', $booking->guest_id)->where('is_active', true)->get() : collect();
            @endphp
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <ul class="nav nav-tabs-custom nav-success mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active All py-3" data-bs-toggle="tab" href="#kendaraan-terdaftar" role="tab">
                                <i class="ri-car-line me-1 align-bottom"></i> Terdaftar ({{ $guestVehicles->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-3" data-bs-toggle="tab" href="#kendaraan-log" role="tab">
                                <i class="ri-history-line me-1 align-bottom"></i> Log Gate ({{ $vehicleLogs->count() }})
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body pt-0">
                    <div class="tab-content">
                        <!-- Kendaraan Terdaftar -->
                        <div class="tab-pane active" id="kendaraan-terdaftar" role="tabpanel">
                            @if($guestVehicles->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plat</th>
                                            <th>Tipe</th>
                                            <th>Merek/Tipe</th>
                                            <th>Pemilik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($guestVehicles as $gv)
                                        <tr>
                                            <td class="fw-medium">{{ $gv->plate_number }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($gv->vehicle_type) }}</span></td>
                                            <td>{{ $gv->vehicle_brand ?: '-' }}</td>
                                            <td>{{ $gv->owner_name ?: '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-2 fs-13">
                                <i class="ri-car-line d-block fs-4 mb-1"></i>
                                Belum ada kendaraan terdaftar untuk tamu ini.
                            </div>
                            @endif
                        </div>

                        <!-- Log Gate -->
                        <div class="tab-pane" id="kendaraan-log" role="tabpanel">
                            @if($vehicleLogs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plat</th>
                                            <th>Tipe</th>
                                            <th>Pengemudi</th>
                                            <th>Masuk</th>
                                            <th>Status</th>
                                            <th>Foto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vehicleLogs as $vl)
                                        <tr>
                                            <td class="fw-medium">{{ $vl->plate_number }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($vl->vehicle_type) }}</span></td>
                                            <td>{{ $vl->driver_name }}</td>
                                            <td class="text-nowrap">{{ $vl->time_in->format('d/m H:i') }}</td>
                                            <td>
                                                @if($vl->status === 'in')
                                                    <span class="badge bg-success">Di Dalam</span>
                                                @else
                                                    <span class="badge bg-secondary">Keluar {{ $vl->time_out?->format('H:i') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($vl->photo_in)
                                                    @php
                                                        $pIn = is_array($vl->photo_in) ? $vl->photo_in : [$vl->photo_in];
                                                        $pOut = $vl->photo_out ? (is_array($vl->photo_out) ? $vl->photo_out : [$vl->photo_out]) : [];
                                                        $pInUrls = array_map(fn($p) => asset('storage/'.$p), $pIn);
                                                        $pOutUrls = array_map(fn($p) => asset('storage/'.$p), $pOut);
                                                    @endphp
                                                    <a href="javascript:void(0);" class="text-primary" title="Lihat foto"
                                                       data-photo-in="{{ json_encode($pInUrls) }}"
                                                       data-photo-out="{{ json_encode($pOutUrls) }}"
                                                       data-plate="{{ $vl->plate_number }}"
                                                       onclick="showVehiclePhoto(this)">
                                                        <i class="ri-image-line fs-16"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-2 fs-13">
                                <i class="ri-history-line d-block fs-4 mb-1"></i>
                                Belum ada riwayat kendaraan masuk/keluar.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vehicle Photo Modal --}}
            <div class="modal fade" id="vehiclePhotoModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="vehiclePhotoTitle">Foto Kendaraan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <p class="text-muted small mb-1 fw-bold">Foto Masuk</p>
                                <div id="vehiclePhotoInContainer"></div>
                            </div>
                            <div id="vehiclePhotoOutSection" style="display:none;">
                                <p class="text-muted small mb-1 fw-bold">Foto Keluar</p>
                                <div id="vehiclePhotoOutContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Guest Info</h5>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-xl mx-auto mb-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-3">
                            {{ substr($booking->guest->name ?? 'G', 0, 1) }}
                        </span>
                    </div>
                    <h5 class="mb-1">{{ $booking->guest->name ?? 'N/A' }}</h5>
                    <p class="text-muted mb-3">{{ $booking->guest->phone ?? '' }}</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('guests.show', $booking->guest_id ?? '#') }}" class="btn btn-soft-primary btn-sm w-100">View Profile</a>
                        <button type="button" class="btn btn-soft-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addGuestVehicleModal">
                            <i class="ri-car-line align-middle me-1"></i> Input Kendaraan Tamu
                        </button>
                    </div>
                </div>

                @if($booking->guest && $booking->guest->emergencyContacts->count() > 0)
                <div class="card-footer border-top-dashed px-3 py-2">
                    <p class="text-muted text-uppercase fw-semibold fs-11 mb-2"><i class="ri-phone-fill me-1 text-warning"></i>Kontak Darurat</p>
                    @foreach($booking->guest->emergencyContacts as $contact)
                    <div class="d-flex align-items-center py-1 {{ !$loop->last ? 'border-bottom border-bottom-dashed' : '' }}">
                        <div class="flex-grow-1">
                            <p class="mb-0 fs-12 fw-medium">{{ $contact->contact_name }}</p>
                            <p class="mb-0 fs-11 text-muted">{{ $contact->phone_number }}{!! $contact->relationship ? ' <span class="text-primary">(' . $contact->relationship . ')</span>' : '' !!}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Manual Notifications</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#notifyBookingModal">
                            <i class="ri-notification-3-line align-middle me-1"></i> Booking Confirmation
                        </button>

                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#notifyPaymentModal" {{ $booking->transactions->where('type', 'payment')->count() == 0 ? 'disabled' : '' }}>
                            <i class="ri-money-dollar-box-line align-middle me-1"></i> Payment Receipt
                        </button>

                        @if($booking->status === 'cancelled')
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#notifyCancelModal">
                            <i class="ri-close-circle-line align-middle me-1"></i> Cancellation Notice
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('bookings.checkin')
                        @if($booking->status === 'confirmed' || $booking->status === 'pending')
                            @php
                                $hotelDate = get_hotel_date();
                                $checkInDate = $booking->check_in->format('Y-m-d');
                                // Custom booking can always check-in for flexibility, or follow rule
                                $isTodayOrLater = $booking->is_custom ? true : ($hotelDate >= $checkInDate);
                                $canForceCheckIn = auth()->user()->can('bookings.checkin.force');
                                $isForceSituation = !$isTodayOrLater && $canForceCheckIn;
                            @endphp
                            <form action="{{ route('bookings.check-in', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="{{ $isForceSituation ? 'PERHATIAN: Check-in PAKSA sebelum jadwal (' . $booking->check_in->format('d M Y') . '). Tindakan ini tercatat di audit log. Lanjutkan?' : 'Are you sure?' }}">
                                @csrf
                                @if(($booking->deposit_amount ?? 0) <= 0 && !in_array($booking->stay_type, ['monthly', 'yearly']))
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="with_deposit" value="1" id="withDepositCheck">
                                    <label class="form-check-label fw-bold" for="withDepositCheck">
                                        Deposit Kamar Rp {{ number_format($booking->hotel->room_deposit_amount ?? 0, 0, ',', '.') }}
                                    </label>
                                </div>
                                @endif
                                <button type="submit" data-submit-protect="true" class="btn {{ $isForceSituation ? 'btn-warning' : 'btn-success' }} w-100" id="checkInBtn" {{ (!$isTodayOrLater && !$canForceCheckIn) || auth()->user()->hasRole('Front Page Only') ? 'disabled' : '' }}>
                                    Check In
                                    @if($isForceSituation)
                                        <small class="d-block">⚠ Check-in paksa (jadwal {{ $booking->check_in->format('d/m') }})</small>
                                    @elseif(!$isTodayOrLater)
                                        <small class="d-block" id="checkInCountdown"
                                               data-unlock="{{ $booking->check_in->copy()->startOfDay()->addHours(12)->timestamp }}"
                                               data-server-now="{{ now()->timestamp }}">(Tersedia tgl {{ $booking->check_in->format('d/m') }} jam 12:00)</small>
                                    @endif
                                </button>
                            </form>
                            @if(!$isTodayOrLater && !$canForceCheckIn && !auth()->user()->hasRole('Front Page Only'))
                            <script>
                            (function () {
                                var el = document.getElementById('checkInCountdown');
                                var btn = document.getElementById('checkInBtn');
                                if (!el || !btn) return;
                                var unlock = parseInt(el.dataset.unlock, 10) * 1000;
                                // Selisih jam server vs jam PC user, supaya countdown ikut jam server
                                var offset = parseInt(el.dataset.serverNow, 10) * 1000 - Date.now();
                                var timer;
                                function pad(n) { return n < 10 ? '0' + n : n; }
                                function tick() {
                                    var remain = unlock - (Date.now() + offset);
                                    if (remain <= 0) {
                                        btn.removeAttribute('disabled');
                                        el.textContent = '';
                                        if (timer) clearInterval(timer);
                                        return;
                                    }
                                    var s = Math.floor(remain / 1000);
                                    var d = Math.floor(s / 86400);
                                    var h = Math.floor((s % 86400) / 3600);
                                    var m = Math.floor((s % 3600) / 60);
                                    var dtl = (d > 0 ? d + ' hari ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s % 60);
                                    el.textContent = '(Check-in dibuka dalam ' + dtl + ')';
                                }
                                tick();
                                timer = setInterval(tick, 1000);
                            })();
                            </script>
                            @endif
                        @endif
                        @endcan
                        @can('bookings.checkout')
                        @if($booking->status === 'checked_in')
                            @php
                                $now = \Carbon\Carbon::now();
                                $checkInTime = $booking->actual_check_in ?? $booking->check_in;
                                $actualNights = max(1, $checkInTime->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));
                                $plannedNights = $booking->check_in->copy()->startOfDay()->diffInDays($booking->check_out->copy()->startOfDay());
                                $isEarlyCheckout = $actualNights < $plannedNights;

                                $previewTotal = $grandTotal;
                                // Kos (monthly) does not get room refund for early checkout
                                if ($isEarlyCheckout && !in_array($booking->stay_type, ['monthly', 'yearly'])) {
                                    $newBasePrice = 0;
                                    $breakdown = $booking->pricing_breakdown;
                                    if (is_array($breakdown)) {
                                        for ($i = 0; $i < $actualNights && $i < count($breakdown); $i++) {
                                            $newBasePrice += $breakdown[$i]['price'];
                                        }
                                    }
                                    $newDiscount = $booking->discount_amount > 0 ? ($booking->discount_amount / $plannedNights) * $actualNights : 0;
                                    $previewTotal = $newBasePrice - $newDiscount + $booking->deposit_amount + $extraCharges->sum('amount') + $posOrders->sum('total_amount');
                                }

                                $totalPaid = $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                                $refundPreview = round($totalPaid - $previewTotal, 2);
                            @endphp

                            <form action="{{ route('bookings.check-out', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" onsubmit="return confirmCheckout()">
                                @csrf

                                @if(in_array($booking->stay_type, ['monthly', 'yearly']) && $booking->deposit_amount > 0)
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="refund_deposit" value="1" id="refundDepositCheck" onchange="toggleRefundNotice()">
                                        <label class="form-check-label fw-bold" for="refundDepositCheck">
                                            Kembalikan Uang Jaminan (Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }})?
                                        </label>
                                    </div>
                                @endif

                                <div id="refund_notice_area" style="display: {{ $refundPreview > 100 ? 'block' : 'none' }};">
                                    <div class="alert alert-info fs-12 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="ri-information-line fs-16 me-2"></i>
                                            <strong>Proses Refund Terdeteksi</strong>
                                        </div>
                                        <p class="mb-2" id="refund_text">
                                            @if($isEarlyCheckout) Tamu check-out lebih awal. @endif
                                            Estimasi refund sistem: <span class="fw-bold">Rp <span id="refund_amount_display">{{ number_format($refundPreview, 0, ',', '.') }}</span></span>
                                        </p>

                                        <label class="form-label fs-11 text-uppercase fw-semibold">Nominal Refund:</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="manual_refund_amount" id="manual_refund_amount" value="{{ max(0, round($refundPreview)) }}" min="0" step="1" oninput="checkRefundDiff()">
                                        </div>
                                        <small class="text-muted" id="refund_diff_notice" style="display: none;">
                                            <i class="ri-error-warning-line me-1 text-warning"></i> Nominal berbeda dari estimasi sistem.
                                        </small>

                                        <label class="form-label fs-11 text-uppercase fw-semibold mt-2">Sumber Dana Refund:</label>
                                        <select name="bank_account_id" id="refund_bank_select" class="form-select form-select-sm">
                                            <option value="">-- Pilih Kas/Bank --</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }} (Saldo: Rp {{ number_format($account->balance, 0, ',', '.') }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <script>
                                    function checkRefundDiff() {
                                        const input = document.getElementById('manual_refund_amount');
                                        const notice = document.getElementById('refund_diff_notice');
                                        const estimasi = {{ round($refundPreview) }};
                                        const val = parseFloat(input.value) || 0;
                                        notice.style.display = (val !== estimasi) ? 'block' : 'none';
                                    }

                                    function toggleRefundNotice() {
                                        const check = document.getElementById('refundDepositCheck');
                                        const area = document.getElementById('refund_notice_area');
                                        const select = document.getElementById('refund_bank_select');
                                        const amountDisplay = document.getElementById('refund_amount_display');
                                        const manualInput = document.getElementById('manual_refund_amount');

                                        let baseRefund = {{ $refundPreview }};
                                        let deposit = {{ $booking->deposit_amount ?? 0 }};
                                        let totalRefund = baseRefund + (check && check.checked ? deposit : 0);

                                        if (totalRefund > 100) {
                                            area.style.display = 'block';
                                            select.setAttribute('required', 'required');
                                            amountDisplay.textContent = new Intl.NumberFormat('id-ID').format(totalRefund);
                                            manualInput.value = Math.round(totalRefund);
                                            checkRefundDiff();
                                        } else {
                                            area.style.display = 'none';
                                            select.removeAttribute('required');
                                        }
                                    }

                                    function confirmCheckout() {
                                        const refundInput = document.getElementById('manual_refund_amount');
                                        const depositOutstanding = {{ $depositOutstanding }};
                                        let msg = 'Apakah Anda yakin ingin melakukan Check Out?';

                                        if (depositOutstanding > 0) {
                                            msg = 'Deposit sebesar Rp ' + new Intl.NumberFormat('id-ID').format(depositOutstanding) + ' belum dikembalikan.\n\n';
                                            msg += 'Tamu akan tetap Check Out. Deposit BELUM dikembalikan dan masih bisa di-refund kapan saja lewat panel Deposit Items di halaman ini.\n\n';
                                            msg += 'Lanjutkan Check Out?';
                                            return confirm(msg);
                                        }

                                        if (refundInput && parseFloat(refundInput.value) > 0) {
                                            const amount = new Intl.NumberFormat('id-ID').format(parseFloat(refundInput.value));
                                            msg += ' Tamu akan menerima refund sebesar Rp ' + amount;
                                        }
                                        return confirm(msg);
                                    }

                                    // Run once on load
                                    document.addEventListener('DOMContentLoaded', function() {
                                        toggleRefundNotice();
                                    });
                                </script>

                                @if($depositOutstanding > 0)
                                    <div class="alert alert-warning fs-12 mb-2">
                                        <i class="ri-secure-payment-line me-1"></i>
                                        <strong>Deposit Outstanding!</strong><br>
                                        Ada deposit yang belum dikembalikan: <strong>Rp {{ number_format($depositOutstanding, 0, ',', '.') }}</strong>.
                                        <br><small>Klik "Check Out" untuk Skip (tidak refund). Atau refund deposit terlebih dahulu.</small>
                                    </div>
                                @endif
                                @if($remainingBalance > 0 && $refundPreview <= 0)
                                    <div class="alert alert-warning fs-12 mb-2">
                                        <i class="ri-error-warning-line me-1"></i> Pembayaran belum lunas (Sisa: Rp {{ number_format($remainingBalance, 0, ',', '.') }}). Harap lunasi tagihan sebelum Check Out.
                                    </div>
                                    <button type="button" class="btn btn-warning w-100 opacity-50" disabled>Check Out (Belum Lunas)</button>
                                @else
                                    <button type="submit" data-submit-protect="true" class="btn btn-warning w-100" {{ auth()->user()->hasRole('Front Page Only') ? 'disabled' : '' }}>Check Out</button>
                                @endif
                            </form>
                        @endif
                        @endcan

                        @if(!auth()->user()->hasRole('Front Page Only'))
                            @can('bookings.payment')
                            @if($remainingBalance > 0 && !in_array($booking->status, ['cancelled', 'no_show', 'checked_out']))
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Add Payment</button>
                            @endif
                            @endcan

                            <div class="row g-2">
                                @can('bookings.invoice')
                                <div class="col-{{ $booking->status === 'checked_out' ? '12' : '6' }}">
                                    <button type="button" class="btn btn-soft-info w-100" data-bs-toggle="modal" data-bs-target="#printInvoiceModal">
                                        <i class="ri-printer-line align-bottom me-1"></i> Invoice
                                    </button>
                                </div>
                                @endcan
                                @can('bookings.send-wa')
                                @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                <div class="col-6">
                                    <form action="{{ route('bookings.send-wa', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                                        @csrf
                                        <button type="submit" data-submit-protect="true" class="btn btn-soft-success w-100">WA</button>
                                    </form>
                                </div>
                                @endif
                                <div class="col-{{ $booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show' ? '6' : '12' }}">
                                    <button type="button" class="btn btn-soft-warning w-100" onclick="copyWaTemplate()">
                                        <i class="ri-clipboard-line me-1"></i> Copy WA
                                    </button>
                                </div>
                                @endcan
                            </div>

                            @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
                                @can('bookings.edit.unlimited')
                                    <a href="{{ route('bookings.edit', $booking->id) }}" class="btn btn-soft-secondary">
                                        <i class="ri-edit-line me-1"></i> Edit Booking
                                    </a>
                                @elsecan('bookings.edit')
                                    @php
                                        $editDeadline = $booking->created_at->addHours(24);
                                        $editRemaining = now()->diffInSeconds($editDeadline, false);
                                        $editExpired = $editRemaining <= 0;
                                    @endphp
                                    <a href="{{ $editExpired ? 'javascript:void(0)' : route('bookings.edit', $booking->id) }}"
                                       class="btn btn-soft-secondary btn-timer {{ $editExpired ? 'disabled text-muted' : ($editRemaining < 3600 ? 'text-warning' : '') }}"
                                       data-deadline="{{ $editDeadline->toISOString() }}"
                                       data-action="edit"
                                       {{ $editExpired ? 'aria-disabled=true' : '' }}
                                       title="{{ $editExpired ? 'Waktu edit sudah habis (24 jam). Hubungi admin.' : '' }}">
                                        <i class="ri-edit-line me-1"></i> Edit
                                        <span class="timer ms-1">
                                            @if($editExpired)
                                                ❌ Expired
                                            @else
                                                ⏱️ {{ gmdate('H:i:s', $editRemaining) }}
                                            @endif
                                        </span>
                                    </a>
                                    @if($editExpired)
                                        @can('bookings.edit.request')
                                            @php $pendingEditReq = $booking->editRequests()->where('status', 'pending')->first(); @endphp
                                            @if($pendingEditReq)
                                            <button type="button" class="btn btn-soft-info" disabled title="Request sudah dikirim, menunggu persetujuan admin">
                                                <i class="ri-time-line me-1"></i> Request Dikirim
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#requestEditModal">
                                                <i class="ri-calendar-schedule-line me-1"></i> Request Edit
                                            </button>
                                            @endif
                                        @endcan
                                    @endif
                                @endcan

                                @can('bookings.transfer')
                                @if($booking->status === 'checked_in')
                                <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#roomTransferModal">
                                    <i class="ri-swap-box-line align-middle me-1"></i> Pindah Kamar
                                </button>
                                @endif
                                @endcan

                                @can('bookings.extend')
                                <a href="{{ route('bookings.extend', $booking->id) }}" class="btn btn-soft-primary">
                                    <i class="ri-calendar-check-line align-middle me-1"></i> Perpanjang Booking
                                </a>
                                @endcan
                            @endif

                            @if(!in_array($booking->status, ['checked_in', 'checked_out', 'cancelled']))
                                @can('bookings.cancel')
                                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                    <i class="ri-close-circle-line me-1"></i> Cancel Booking
                                </button>
                                @endcan
                            @endif

                            @if(!in_array($booking->status, ['checked_in', 'checked_out', 'cancelled', 'no_show']) || auth()->user()->hasRole('Admin'))
                                @can('bookings.delete.unlimited')
                                    @php $showUnlimitedDelete = true; @endphp
                                @elsecan('bookings.delete')
                                    @php $showTimeDelete = true; @endphp
                                @endcan
                                @if(auth()->user()->hasRole('Admin')) @php $showUnlimitedDelete = true; @endphp @endif

                                @if($showUnlimitedDelete ?? false)
                                <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" onsubmit="return confirm('Are you sure you want to delete this booking?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger w-100"><i class="ri-delete-bin-line me-1"></i> Delete Booking</button>
                                </form>
                                @elseif($showTimeDelete ?? false)
                                    @php
                                        $deleteDeadline = $booking->created_at->addHours(24);
                                        $deleteRemaining = now()->diffInSeconds($deleteDeadline, false);
                                        $deleteExpired = $deleteRemaining <= 0;
                                    @endphp
                                    <button type="button"
                                            class="btn btn-link text-danger w-100 btn-timer {{ $deleteExpired ? 'disabled text-muted' : '' }}"
                                            data-deadline="{{ $deleteDeadline->toISOString() }}"
                                            data-action="delete"
                                            onclick="{{ $deleteExpired ? '' : "confirmDelete($booking->id)" }}"
                                            {{ $deleteExpired ? 'disabled' : '' }}
                                            title="{{ $deleteExpired ? 'Waktu hapus sudah habis (24 jam). Hubungi admin.' : '' }}">
                                        <i class="ri-delete-bin-line me-1"></i> Hapus
                                        <span class="timer ms-1">
                                            @if($deleteExpired)
                                                ❌ Expired
                                            @else
                                                ⏱️ {{ gmdate('H:i:s', $deleteRemaining) }}
                                            @endif
                                        </span>
                                    </button>
                                @endcan
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            </div>

            {{-- Recent Activity --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @php
                            $recentLogs = \App\Models\AuditLog::where('model_type', \App\Models\Booking::class)
                                ->where('model_id', $booking->id)
                                ->with('user')
                                ->latest()
                                ->take(5)
                                ->get();
                        @endphp
                        @forelse($recentLogs as $log)
                            <li class="mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-14">
                                                <i class="ri-history-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fs-13 mb-1">{{ $log->description }}</h6>
                                        <p class="text-muted fs-12 mb-0"><i class="ri-time-line align-middle me-1"></i>{{ $log->created_at->diffForHumans() }} - {{ $log->user->name ?? 'System' }}</p>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted text-center fs-13">Belum ada riwayat aktivitas.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    <!-- Add Guest Vehicle Modal -->
    <div class="modal fade" id="addGuestVehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary-subtle">
                    <h5 class="modal-title">Input Kendaraan Tamu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('guest-vehicles.store') }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true">
                    @csrf
                    <input type="hidden" name="guest_id" value="{{ $booking->guest_id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipe Kendaraan <span class="text-danger">*</span></label>
                            <select class="form-select" name="vehicle_type" required>
                                <option value="motor">Motor</option>
                                <option value="mobil">Mobil</option>
                                <option value="truck">Truck</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Plat / Nopol <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="plate_number" required placeholder="N 1234 ABC">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Merek/Tipe Kendaraan</label>
                            <input type="text" class="form-control" name="vehicle_brand" placeholder="Honda Vario, Toyota Avanza, dll">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nama Pemilik</label>
                            <input type="text" class="form-control" name="owner_name" value="{{ $booking->guest->name ?? '' }}">
                            <small class="text-muted">Biarkan jika milik tamu langsung.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-secondary">Simpan Kendaraan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title">Add Rental Item (Sewa) — No Stock Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <div class="row g-2">
                            <div class="col">
                                <input type="text" id="itemSearch" class="form-control" placeholder="Search item...">
                            </div>
                            <div class="col-auto">
                                <select id="categoryFilter" class="form-select" style="width: 150px;">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Models\InventoryCategory::all() as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="item-list-container" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <tbody id="inventoryList">
                                @foreach($inventoryItems as $item)
                                <tr class="item-row" data-name="{{ strtolower($item->name) }}" data-category="{{ $item->inventoryCategory?->name ?? 'Uncategorized' }}">
                                    <td class="ps-3">
                                        <div class="fw-medium">{{ $item->name }}</div>
                                        <small class="text-muted">Stock: {{ $item->stock }}</small>
                                        @if($item->is_refundable)
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-warning fs-10"><i class="ri-secure-payment-line me-1"></i>Deposit: Rp {{ number_format((float)$item->deposit_amount > 0 ? $item->deposit_amount : $item->price_per_unit, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-center" style="width: 100px;">
                                        <input type="number" class="form-control form-control-sm qty-input" value="1" min="1" id="qty_{{ $item->id }}">
                                    </td>
                                    <td class="text-center" style="width: 200px;">
                                        <form action="{{ route('bookings.add-item', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                                            @csrf
                                            <input type="hidden" name="inventory_id" value="{{ $item->id }}">
                                            <input type="hidden" name="quantity" class="final-qty" value="1">
                                            <button type="submit" data-submit-protect="true" class="btn btn-sm btn-success w-100">Add</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Custom Charge Modal -->
    <div class="modal fade" id="addCustomChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title">Add Custom Charge / Tax</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bookings.add-custom-charge', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="description" placeholder="e.g. Extra Bed, Tax, Service Charge" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Amount (Net)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="amount" value="0" id="custom_base_amount">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax Amount (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="tax_amount" value="0" id="custom_tax_amount">
                                </div>
                            </div>
                        </div>
                        <div class="bg-light p-2 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-medium">Total Charge:</span>
                                <span id="custom_total_display" class="fw-bold text-success fs-15">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-info">Add Charge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Apply Discount Modal -->
    <div class="modal fade" id="applyDiscountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title">Apply Discount / Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bookings.apply-discount', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Voucher Code</label>
                            <input type="text" class="form-control" name="voucher_code" value="{{ $booking->voucher_code }}" placeholder="CODE">
                            <small class="text-muted">Enter a code to apply voucher-based discount.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Manual Discount (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="discount_amount" value="{{ $booking->discount_amount }}">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Reason</label>
                            <textarea class="form-control" name="reason" rows="2" placeholder="Reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-danger">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Audit Pricing Modal -->
    <div class="modal fade" id="auditPricingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title"><i class="ri-search-eye-line me-2"></i>Pricing Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="auditResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="btnApplyAuditFix" style="display:none">
                        <i class="ri-check-double-line me-1"></i> Apply Fix
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title">Add New Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bookings.add-payment', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Tanggal Pembayaran</label>
                            <input type="datetime-local" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Payment Account</label>
                            <select class="form-select" name="bank_account_id" id="payment_account" required>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" data-type="{{ str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank' }}">
                                        {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Amount to Record (Nominal Masuk Kas)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" id="amount_to_pay" value="{{ (int)$remainingBalance }}" required>
                            </div>
                            <small class="text-muted">Sisa Tagihan: Rp {{ number_format($remainingBalance, 0, ',', '.') }}</small>
                        </div>
                        <div id="cash_calculator_section" class="p-3 bg-light rounded mb-3 border border-dashed">
                            <div class="d-flex align-items-center mb-2">
                                <label class="form-label fs-12 text-muted text-uppercase mb-0 flex-grow-1">Customer Paid (Uang Diterima)</label>
                                <button type="button" class="btn btn-link btn-sm p-0 fs-11 text-decoration-none" onclick="document.getElementById('amount_to_pay').value = document.getElementById('customer_paid').value; calculateChange();">
                                    Use as Record Amount
                                </button>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="customer_paid" placeholder="Masukkan uang tamu...">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-13">Kembalian:</span>
                                <span id="change_amount" class="fw-bold text-primary fs-15">Rp 0</span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Notes</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Payment notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Deposit Modal -->
    @if(($booking->deposit_amount ?? 0) > 0 && $booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'no_show')
    <div class="modal fade" id="editDepositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Security Deposit (Jaminan)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bookings.edit-deposit', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Deposit Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="deposit_amount" value="{{ (int)$booking->deposit_amount }}" min="0" required>
                            </div>
                        </div>
                        <div class="alert alert-info fs-12 mb-0">
                            <i class="ri-information-line me-1"></i> Mengubah deposit akan mempengaruhi total harga booking.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-warning">Update Deposit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Notify Booking Modal -->
    <div class="modal fade" id="notifyBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">Send Booking Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted">Pilih saluran pengiriman untuk konfirmasi booking:</p>
                    <div class="d-flex justify-content-center gap-3">
                        @php
                            $waPhone = $booking->guest->phone ?? '';
                            $waPhone = preg_replace('/[^0-9]/', '', $waPhone);
                            if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
                            $waConfirmText = "*KONFIRMASI BOOKING*\n" . ($booking->hotel->name ?? 'Our Homestay') . "\n------------------------------------------\nBooking ID: #" . $booking->id . "\nTamu: " . ($booking->guest->name ?? 'N/A') . "\nKamar: " . ($booking->room->room_number ?? ($booking->custom_room_name ?? 'N/A')) . "\nCheck-in: " . $booking->check_in->format('d M Y') . "\nCheck-out: " . $booking->check_out->format('d M Y') . "\nSarapan: " . ($booking->include_breakfast ? 'Termasuk ✓' : 'Tidak termasuk') . "\nTotal: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n------------------------------------------\nTerima kasih atas reservasinya! 🙏";
                        @endphp
                        <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode($waConfirmText) }}" target="_blank" class="btn btn-success">
                            <i class="ri-whatsapp-line fs-24 d-block"></i> WhatsApp
                        </a>
                        <button type="button" class="btn btn-warning" onclick="copyBookingConfirmationWa()">
                            <i class="ri-clipboard-line fs-24 d-block"></i> Copy WA
                        </button>
                        <form action="{{ route('notifications.booking', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                            @csrf
                            <input type="hidden" name="channel" value="email">
                            <button type="submit" data-submit-protect="true" class="btn btn-info">
                                <i class="ri-mail-line fs-24 d-block"></i> Email
                            </button>
                        </form>
                        <a href="{{ route('notifications.print.booking', $booking->id) }}" target="_blank" class="btn btn-secondary">
                            <i class="ri-printer-line fs-24 d-block"></i> Print
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notify Payment Modal -->
    <div class="modal fade" id="notifyPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title">Send Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('notifications.payment', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" id="paymentNotifyForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Transaksi Pembayaran:</label>
                            <select class="form-select" name="transaction_id" id="notify_transaction_id" required>
                                @foreach($booking->transactions->where('type', 'payment')->where('status', 'success') as $tx)
                                    <option value="{{ $tx->id }}">Rp {{ number_format($tx->amount, 0, ',', '.') }} - {{ $tx->created_at->format('d M Y, H:i') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="text-center py-2">
                            <p class="text-muted">Pilih saluran pengiriman:</p>
                            <div class="d-flex justify-content-center gap-3">
                                <button type="submit" data-submit-protect="true" name="channel" value="wa" class="btn btn-success">
                                    <i class="ri-whatsapp-line fs-24 d-block"></i> WhatsApp
                                </button>
                                <button type="submit" data-submit-protect="true" name="channel" value="email" class="btn btn-info">
                                    <i class="ri-mail-line fs-24 d-block"></i> Email
                                </button>
                                <button type="button" onclick="printPaymentReceipt()" class="btn btn-secondary">
                                    <i class="ri-printer-line fs-24 d-block"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notify Cancel Modal -->
    <div class="modal fade" id="notifyCancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title"><i class="ri-close-circle-line me-2"></i>Cancellation Notice #{{ $booking->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="ri-close-circle-fill text-danger" style="font-size: 48px;"></i>
                        <h5 class="mt-2">Booking Dibatalkan</h5>
                    </div>

                    <table class="table table-sm table-borderless mb-3">
                        <tr><td class="text-muted">Tamu</td><td class="fw-medium">{{ $booking->guest->name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Kamar</td><td class="fw-medium">{{ $booking->room->room_number ?? $booking->custom_room_name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Check-in</td><td class="fw-medium">{{ $booking->check_in->format('d M Y') }}</td></tr>
                        <tr><td class="text-muted">Dibatalkan</td><td class="fw-medium">{{ $booking->cancelled_at ? $booking->cancelled_at->format('d M Y, H:i') : '-' }}</td></tr>
                        @if($booking->cancelled_reason)
                        <tr><td class="text-muted">Alasan</td><td class="fw-medium text-danger">{{ $booking->cancelled_reason }}</td></tr>
                        @endif
                    </table>

                    @if($booking->cancelled_photo)
                    <div class="mb-3">
                        <label class="form-label fw-medium">Bukti/Foto:</label>
                        <img src="{{ asset('storage/' . $booking->cancelled_photo) }}" class="img-fluid rounded border" style="max-height: 300px;" alt="Cancellation Photo">
                    </div>
                    @endif

                    @php
                        $totalRefund = $booking->transactions->where('type', 'refund')->sum('amount');
                    @endphp
                    @if($totalRefund > 0)
                        <div class="alert alert-warning mb-0">
                            <i class="ri-refund-line me-2"></i>
                            Total refund: <strong>Rp {{ number_format($totalRefund, 0, ',', '.') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <a href="{{ route('notifications.print.cancel', $booking->id) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="ri-printer-line me-1"></i> Cetak
                    </a>
                    <form action="{{ route('notifications.cancel', $booking->id) }}" method="POST" data-ajax="true" class="d-inline">
                        @csrf
                        <button type="submit" data-submit-protect="true" class="btn btn-danger">
                            <i class="ri-whatsapp-line me-1"></i> Kirim via WhatsApp
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Booking Modal --}}
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-danger-subtle">
                        <h5 class="modal-title"><i class="ri-close-circle-line me-2"></i>Cancel Booking #{{ $booking->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if(($booking->deposit_amount ?? 0) > 0)
                            <div class="alert alert-warning">
                                <i class="ri-alert-line me-2"></i>
                                <strong>Perhatian:</strong> Booking ini memiliki uang jaminan sebesar <strong>Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }}</strong> yang akan ikut di-refund.
                            </div>
                        @endif

                        @php
                            $totalPaidSoFar = $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                        @endphp
                        @if($totalPaidSoFar > 0)
                            <div class="alert alert-info">
                                <i class="ri-information-line me-2"></i>
                                Total pembayaran yang akan di-refund: <strong>Rp {{ number_format($totalPaidSoFar, 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="cancelled_reason" rows="3" placeholder="Tulis alasan pembatalan booking..." required maxlength="500"></textarea>
                            <div class="form-text">Maksimal 500 karakter.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bukti/Foto (opsional)</label>
                            <input type="file" class="form-control" name="cancelled_photo" accept="image/*">
                            <div class="form-text">Format: JPG, PNG, GIF. Maks 5MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-danger" onclick="return confirm('Yakin ingin membatalkan booking ini? Tindakan ini tidak dapat dibatalkan.')">
                            <i class="ri-close-circle-line me-1"></i> Konfirmasi Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Invoice Options Modal -->
    <div class="modal fade" id="printInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title"><i class="ri-printer-line me-2"></i>Opsi Cetak Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Pilih komponen yang ingin ditampilkan dalam invoice:</p>

                    <div class="form-check form-check-info mb-3">
                        <input class="form-check-input" type="checkbox" id="print_room" checked>
                        <label class="form-check-label fw-medium" for="print_room">
                            Biaya Kamar & Jaminan (Room Charges)
                        </label>
                    </div>

                    <div class="form-check form-check-info mb-3">
                        <input class="form-check-input" type="checkbox" id="print_extra" checked>
                        <label class="form-check-label fw-medium" for="print_extra">
                            Biaya Tambahan Manual (Laundry, Extra Bed, dll)
                        </label>
                    </div>

                    <div class="form-check form-check-info mb-3">
                        <input class="form-check-input" type="checkbox" id="print_pos" checked>
                        <label class="form-check-label fw-medium" for="print_pos">
                            Pesanan POS (Makanan, Minuman, Barang)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info" onclick="generateSelectedInvoice()">
                        <i class="ri-printer-line me-1"></i> Buka Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Request Edit Modal --}}
    <div class="modal fade" id="requestEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title"><i class="ri-calendar-schedule-line me-2"></i>Request Edit Booking #{{ $booking->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bookings.edit-request.store', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Yang ingin diubah:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="change_type[]" value="date" id="chg_date">
                                    <label class="form-check-label" for="chg_date">Tanggal Check-in/Check-out</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="change_type[]" value="guest" id="chg_guest">
                                    <label class="form-check-label" for="chg_guest">Data Tamu</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="change_type[]" value="room" id="chg_room">
                                    <label class="form-check-label" for="chg_room">Room / Tipe Kamar</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="change_type[]" value="price" id="chg_price">
                                    <label class="form-check-label" for="chg_price">Harga / Pricing</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="change_type[]" value="other" id="chg_other">
                                    <label class="form-check-label" for="chg_other">Lainnya</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Jelaskan perubahan yang diinginkan..." required></textarea>
                        </div>
                        <div class="row" id="proposedDates" style="display:none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Check-in Baru</label>
                                <input type="date" class="form-control" name="proposed_check_in">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Check-out Baru</label>
                                <input type="date" class="form-control" name="proposed_check_out">
                            </div>
                        </div>
                        <div class="alert alert-info fs-12 mb-0">
                            <i class="ri-information-line me-1"></i> Request akan dikirim ke Admin/Manager untuk disetujui.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-info"><i class="ri-send-plane-line me-1"></i> Kirim Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('chg_date')?.addEventListener('change', function() {
            document.getElementById('proposedDates').style.display = this.checked ? 'flex' : 'none';
        });
    </script>

    {{-- Room Transfer Modal --}}
    @if($booking->status === 'checked_in')
    <div class="modal fade" id="roomTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="ri-swap-box-line me-2"></i>Pindah Kamar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('bookings.transfer.store', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" id="transferForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Alasan Pindah <span class="text-danger">*</span></label>
                                <select class="form-select" name="reason" id="transfer_reason" required>
                                    <option value="">-- Pilih Alasan --</option>
                                    <option value="Request Tamu">Request Tamu</option>
                                    <option value="Kerusakan Kamar">Kerusakan Kamar</option>
                                    <option value="Upgrade">Upgrade</option>
                                    <option value="Downgrade">Downgrade</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter Tipe Kamar</label>
                                <select class="form-select" id="transfer_filter_type" onchange="loadTransferRooms()">
                                    <option value="">All Types</option>
                                    @foreach(\App\Models\RoomType::where('is_active', true)->get() as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Catatan (opsional)</label>
                                <input type="text" class="form-control" name="notes" placeholder="Catatan tambahan...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Perpanjang Check-out (opsional)</label>
                                <input type="date" class="form-control" name="new_check_out" id="transfer_new_checkout" min="{{ date('Y-m-d', strtotime('+1 day')) }}" placeholder="Biarkan kosong jika tidak berubah">
                            </div>
                        </div>

                        <input type="hidden" name="room_id" id="transfer_room_id">
                        <input type="hidden" name="tier" id="transfer_tier">

                        {{-- Room List --}}
                        <div class="row g-3 mb-3" id="transfer_room_list" style="max-height: 300px; overflow-y: auto;"></div>

                        {{-- Price Preview --}}
                        <div id="transfer_preview" class="border rounded p-3 bg-light" style="display: none;">
                            <h6 class="mb-3"><i class="ri-calculator-line me-1"></i> Preview Harga</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr><td class="text-muted">Kamar Lama:</td><td class="fw-medium" id="tp_old_room">-</td></tr>
                                        <tr><td class="text-muted">Harga/malam:</td><td id="tp_old_price">-</td></tr>
                                        <tr><td class="text-muted">Malam terpakai:</td><td id="tp_nights_used">-</td></tr>
                                        <tr><td class="text-muted">Subtotal lama:</td><td id="tp_cost_old">-</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr><td class="text-muted">Kamar Baru:</td><td class="fw-medium" id="tp_new_room">-</td></tr>
                                        <tr><td class="text-muted">Harga/malam:</td><td id="tp_new_price">-</td></tr>
                                        <tr><td class="text-muted">Sisa malam:</td><td id="tp_remaining">-</td></tr>
                                        <tr><td class="text-muted">Subtotal baru:</td><td id="tp_cost_new">-</td></tr>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Total Lama:</span><span id="tp_old_total" class="fw-medium">-</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Baru:</span><span id="tp_new_total" class="fw-medium">-</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2 fs-16">
                                <span class="fw-bold">Selisih:</span><span id="tp_difference" class="fw-bold">-</span>
                            </div>

                            {{-- Charge Selisih Option --}}
                            <div id="transfer_charge_option" class="mt-3 p-3 border rounded bg-warning-subtle border-warning" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-information-line fs-16 text-warning me-2"></i>
                                    <span class="fw-semibold fs-13">Opsi Selisih Harga</span>
                                </div>
                                <p class="fs-12 text-muted mb-2" id="transfer_charge_desc">Kamar baru lebih mahal. Pilih tindakan untuk selisih harga:</p>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="charge_difference" id="charge_diff_yes" value="1">
                                    <label class="form-check-label fw-medium" for="charge_diff_yes">
                                        Charge selisih harga ke tamu <span class="text-danger" id="charge_diff_amount"></span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="charge_difference" id="charge_diff_no" value="0" checked>
                                    <label class="form-check-label fw-medium" for="charge_diff_no">
                                        Gratis (tidak charge selisih)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary" id="btnConfirmTransfer" disabled>
                            <i class="ri-swap-box-line me-1"></i> Konfirmasi Pindah Kamar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Room Transfer History --}}
    @if($booking->roomTransfers && $booking->roomTransfers->count() > 0)
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-swap-box-line me-2 text-info"></i>Riwayat Pindah Kamar</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-borderless align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Alasan</th>
                            <th>Selisih Harga</th>
                            <th>Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->roomTransfers->sortByDesc('transferred_at') as $transfer)
                        <tr>
                            <td>{{ $transfer->transferred_at->format('d M Y, H:i') }}</td>
                            <td><span class="badge bg-danger-subtle text-danger">{{ $transfer->fromRoom->room_number ?? '-' }}</span></td>
                            <td><span class="badge bg-success-subtle text-success">{{ $transfer->toRoom->room_number ?? '-' }}</span></td>
                            <td>{{ $transfer->reason }}</td>
                            <td>
                                @if($transfer->price_difference > 0)
                                    <span class="text-danger">+Rp {{ number_format($transfer->price_difference, 0, ',', '.') }}</span>
                                @elseif($transfer->price_difference < 0)
                                    <span class="text-success">-Rp {{ number_format(abs($transfer->price_difference), 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">Rp 0</span>
                                @endif
                            </td>
                            <td>{{ $transfer->transferredBy->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function copyBookingConfirmationWa() {
            var text = "*KONFIRMASI BOOKING*\n";
            text += "{!! addslashes($booking->hotel->name ?? 'Our Homestay') !!}\n";
            text += "------------------------------------------\n";
            text += "Booking ID: #{{ $booking->id }}\n";
            text += "Tamu: {!! addslashes($booking->guest->name ?? 'N/A') !!}\n";
            text += "Kamar: {{ $booking->room->room_number ?? ($booking->custom_room_name ?? 'N/A') }}{{ $booking->room && $booking->room->roomType ? ' (' . $booking->room->roomType->name . ')' : '' }}\n";
            text += "Check-in: {{ $booking->check_in->format('d M Y') }}\n";
            text += "Check-out: {{ $booking->check_out->format('d M Y') }}\n";
            text += "Sarapan: {{ $booking->include_breakfast ? 'Termasuk ✓' : 'Tidak termasuk' }}\n";
            text += "Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}\n";
            text += "------------------------------------------\n";
            text += "Terima kasih atas reservasinya! 🙏\n";
            @if($booking->guest && $booking->guest->phone)
            text += "\nKirim ke: {{ $booking->guest->phone }}";
            @endif

            navigator.clipboard.writeText(text).then(function() {
                Toastify({ text: "✅ Konfirmasi booking di-copy! Paste ke WhatsApp.", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            }).catch(function() {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                Toastify({ text: "✅ Konfirmasi booking di-copy!", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            });
        }

        function copyWaTemplate() {
            @php
                $booking->load(['guest', 'room.roomType', 'posOrders.items', 'hotel']);
                $extraCharges = $booking->transactions->where('type', 'charge')->where('status', 'success')->where('reference_id', null)->where('is_deposit', false);
                $posOrders = $booking->posOrders;
                $totalChargesWa = $booking->total_price + $extraCharges->sum('amount') + $posOrders->sum('total_amount');
                $totalPaidWa = $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                $balanceWa = $totalChargesWa - $totalPaidWa;
                $hotelNameWa = $booking->hotel->name ?? 'Our Homestay';
                $nightsWa = $booking->check_in->diffInDays($booking->check_out);
            @endphp
            var text = "*INVOICE - {!! addslashes($hotelNameWa) !!}*\n";
            text += "------------------------------------------\n";
            text += "No: #INV-{{ date('Ymd') }}-{{ $booking->id }}\n";
            text += "Date: {{ date('d M Y') }}\n\n";
            text += "*Guest Info:*\n";
            text += "Name: {!! addslashes($booking->guest->name ?? 'N/A') !!}\n";
            text += "Room: {{ $booking->room->room_number ?? ($booking->custom_room_name ?? 'N/A') }} ({{ $booking->room->roomType->name ?? '-' }})\n";
            text += "Check-in: {{ $booking->check_in->format('d M Y') }}\n";
            text += "Check-out: {{ $booking->check_out->format('d M Y') }}\n\n";
            text += "*Billing Details:*\n";
            text += "- Room ({{ $nightsWa }} malam): Rp {{ number_format($booking->base_price, 0, ',', '.') }}\n";
            @if($booking->include_breakfast && ($booking->pricing_breakdown['breakfast_total'] ?? 0) > 0)
            text += "- Breakfast ({{ $nightsWa }} malam): Rp {{ number_format($booking->pricing_breakdown['breakfast_total'], 0, ',', '.') }}\n";
            @endif
            @foreach($extraCharges as $charge)
            text += "- {!! addslashes($charge->description) !!}: Rp {{ number_format($charge->amount, 0, ',', '.') }}\n";
            @endforeach
            @foreach($posOrders as $order)
                @foreach($order->items as $item)
            text += "- {!! addslashes($item->item_name) !!} (x{{ $item->quantity }}): Rp {{ number_format($item->subtotal, 0, ',', '.') }}\n";
                @endforeach
            @endforeach
            @if($booking->tax_amount > 0)
            text += "- Tax: Rp {{ number_format($booking->tax_amount, 0, ',', '.') }}\n";
            @endif
            @if($booking->discount_amount > 0)
            text += "- Discount: -Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}\n";
            @endif
            text += "------------------------------------------\n";
            text += "*TOTAL: Rp {{ number_format($totalChargesWa, 0, ',', '.') }}*\n";
            text += "Paid: Rp {{ number_format($totalPaidWa, 0, ',', '.') }}\n";
            @if($balanceWa > 0)
            text += "*Remaining: Rp {{ number_format($balanceWa, 0, ',', '.') }}*\n";
            @else
            text += "*STATUS: FULLY PAID*\n";
            @endif
            text += "------------------------------------------\n";
            text += "Thank you for staying with us! 🙏";

            navigator.clipboard.writeText(text).then(function() {
                Toastify({ text: "✅ Template WA berhasil di-copy! Tinggal paste ke WhatsApp.", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            }).catch(function() {
                // Fallback for older browsers
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                Toastify({ text: "✅ Template WA berhasil di-copy!", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Toast Notifications
            @if(session('success'))
                Toastify({ text: "{{ session('success') }}", duration: 3000, gravity: "top", position: "right", style: { background: "#0ab39c" } }).showToast();
            @endif
            @if(session('error'))
                Toastify({ text: "{{ session('error') }}", duration: 3000, gravity: "top", position: "right", style: { background: "#f06548" } }).showToast();
            @endif

            const amountInput = document.getElementById('amount_to_pay');
            const paidInput = document.getElementById('customer_paid');
            const changeDisplay = document.getElementById('change_amount');
            const accountSelect = document.getElementById('payment_account');
            const cashSection = document.getElementById('cash_calculator_section');

            function calculateChange() {
                const toPayInput = document.getElementById('amount_to_pay');
                const paidInput = document.getElementById('customer_paid');
                const changeDisplay = document.getElementById('change_amount');
                const remaining = {{ (int)$remainingBalance }};

                const paid = parseFloat(paidInput.value) || 0;

                // Jika bayar kurang dari sisa (cicilan), update nominal kas
                if (paid > 0 && paid <= remaining) {
                    toPayInput.value = paid;
                }
                // Jika bayar lebih (lunas + kembalian), nominal kas = sisa tagihan
                else if (paid > remaining) {
                    toPayInput.value = remaining;
                }

                const toPay = parseFloat(toPayInput.value) || 0;
                const change = Math.max(0, paid - toPay);
                changeDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(change);
            }

            function toggleCashCalculator() {
                if(!accountSelect) return;
                const isCash = accountSelect.options[accountSelect.selectedIndex].dataset.type === 'cash';
                cashSection.style.display = isCash ? 'block' : 'none';
            }

            if(amountInput) amountInput.addEventListener('input', calculateChange);
            if(paidInput) paidInput.addEventListener('input', calculateChange);
            if(accountSelect) {
                accountSelect.addEventListener('change', toggleCashCalculator);
                toggleCashCalculator();
            }

            // Custom Charge Calculation
            const customBase = document.getElementById('custom_base_amount');
            const customTax = document.getElementById('custom_tax_amount');
            const customTotalDisplay = document.getElementById('custom_total_display');

            function calculateCustomTotal() {
                const b = parseFloat(customBase.value) || 0;
                const t = parseFloat(customTax.value) || 0;
                const total = b + t;
                customTotalDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
            }

            if(customBase) customBase.addEventListener('input', calculateCustomTotal);
            if(customTax) customTax.addEventListener('input', calculateCustomTotal);

            // Filter Items
            const itemSearch = document.getElementById('itemSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            function filter() {
                const s = itemSearch.value.toLowerCase();
                const c = categoryFilter.value;
                document.querySelectorAll('.item-row').forEach(row => {
                    const match = row.dataset.name.includes(s) && (!c || row.dataset.category === c);
                    row.style.display = match ? '' : 'none';
                });
            }
            if(itemSearch) itemSearch.addEventListener('input', filter);
            if(categoryFilter) categoryFilter.addEventListener('change', filter);

            // Re-init filter when modal opens (handles modal rendering quirks)
            const addItemModal = document.getElementById('addItemModal');
            if (addItemModal) {
                addItemModal.addEventListener('shown.bs.modal', function() {
                    if (itemSearch) itemSearch.value = '';
                    if (categoryFilter) categoryFilter.value = '';
                    filter();
                });
            }

            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('input', function() {
                    this.closest('tr').querySelector('.final-qty').value = this.value;
                });
            });
        });

        function printPaymentReceipt() {
            const txId = document.getElementById('notify_transaction_id').value;
            if (txId) {
                const url = "{{ route('notifications.print.payment', [$booking->id, ':txId']) }}".replace(':txId', txId);
                window.open(url, '_blank');
            }
        }

        function generateSelectedInvoice() {
            const room = document.getElementById('print_room').checked ? '1' : '0';
            const extra = document.getElementById('print_extra').checked ? '1' : '0';
            const pos = document.getElementById('print_pos').checked ? '1' : '0';

            const url = "{{ route('bookings.invoice', $booking->id) }}?room=" + room + "&extra=" + extra + "&pos=" + pos;
            window.open(url, '_blank');
            bootstrap.Modal.getInstance(document.getElementById('printInvoiceModal')).hide();
        }
    </script>

    {{-- Room Transfer JS --}}
    @if($booking->status === 'checked_in')
    <script>
        function formatRp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n)); }

        function loadTransferRooms() {
            const typeId = document.getElementById('transfer_filter_type').value;
            const params = new URLSearchParams({
                check_in: '{{ $booking->check_in->format("Y-m-d") }}',
                check_out: '{{ $booking->check_out->format("Y-m-d") }}',
                room_type_id: typeId,
                guest_id: '{{ $booking->guest_id }}',
                stay_type: 'daily'
            });
            fetch(`{{ route('bookings.available-rooms') }}?${params.toString()}`).then(r => r.json()).then(rooms => {
                const container = document.getElementById('transfer_room_list');
                container.innerHTML = '';
                // Only show rooms that are clean/available for immediate transfer (not dirty/checkout)
                const transferableRooms = rooms.filter(room => room.id !== {{ $booking->room_id ?? 0 }} && room.available === true);
                transferableRooms.forEach(room => {
                    const statusBadge = room.status ? `<span class="badge bg-success-subtle text-success mb-1">${room.status}</span>` : '';
                    const col = document.createElement('div');
                    col.className = 'col-md-3';
                    col.innerHTML = `
                        <div class="card border shadow-none h-100">
                            <div class="card-body p-3">
                                <h6 class="fs-14 mb-1">Room ${room.room_number}</h6>
                                <span class="badge bg-primary-subtle text-primary mb-1">${room.room_type}</span>
                                ${statusBadge}
                                <div class="d-grid gap-1 mt-1">
                                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="selectTransferRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_public}, 'public')">
                                        Umum: ${formatRp(room.price_public)}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-info" onclick="selectTransferRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_sales}, 'sales')">
                                        Sales: ${formatRp(room.price_sales)}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" onclick="selectTransferRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_high_season}, 'high_season')">
                                        High Season: ${formatRp(room.price_high_season)}
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    container.appendChild(col);
                });
                if (transferableRooms.length === 0) {
                    container.innerHTML = '<div class="col-12 text-center text-muted py-3"><i class="ri-information-line fs-20 d-block mb-1"></i>Tidak ada kamar bersih yang tersedia untuk pindah kamar. Pastikan kamar tujuan sudah di-clean oleh Housekeeping.</div>';
                }
            });
        }

        let currentSelectedTransferRoom = null;
        function selectTransferRoom(roomId, roomNum, roomType, price, tier) {
            currentSelectedTransferRoom = { roomId, roomNum, roomType, price, tier };
            document.getElementById('transfer_room_id').value = roomId;
            document.getElementById('transfer_tier').value = tier;

            // Fetch preview
            const params = new URLSearchParams({ room_id: roomId, tier: tier });
            const newCheckOut = document.getElementById('transfer_new_checkout').value;
            if (newCheckOut) {
                params.append('new_check_out', newCheckOut);
            }

            fetch(`{{ route('bookings.transfer.preview', $booking->id) }}?${params.toString()}`).then(r => r.json()).then(res => {
                if (!res.success) {
                    alert(res.message);
                    return;
                }
                const d = res.data;
                document.getElementById('tp_old_room').textContent = '{{ $booking->room->room_number ?? "-" }}';
                document.getElementById('tp_old_price').textContent = formatRp(d.old_price_per_night);
                document.getElementById('tp_nights_used').textContent = d.nights_used + ' malam';
                document.getElementById('tp_cost_old').textContent = formatRp(d.cost_old_room);
                document.getElementById('tp_new_room').textContent = roomNum;
                document.getElementById('tp_new_price').textContent = formatRp(d.new_price_per_night);
                document.getElementById('tp_remaining').textContent = d.remaining_nights + ' malam';
                document.getElementById('tp_cost_new').textContent = formatRp(d.cost_new_room);
                document.getElementById('tp_old_total').textContent = formatRp(d.old_total);
                document.getElementById('tp_new_total').textContent = formatRp(d.new_total);

                const diffEl = document.getElementById('tp_difference');
                const chargeOption = document.getElementById('transfer_charge_option');
                const chargeDesc = document.getElementById('transfer_charge_desc');
                const chargeAmount = document.getElementById('charge_diff_amount');

                if (d.price_difference > 0) {
                    diffEl.textContent = '+' + formatRp(d.price_difference);
                    diffEl.className = 'fw-bold text-danger';
                    chargeOption.style.display = 'block';
                    chargeDesc.textContent = 'Kamar baru / durasi perpanjangan memiliki total lebih tinggi. Pilih tindakan:';
                    chargeAmount.textContent = '(' + formatRp(d.price_difference) + ')';
                } else if (d.price_difference < 0) {
                    diffEl.textContent = '-' + formatRp(Math.abs(d.price_difference));
                    diffEl.className = 'fw-bold text-success';
                    chargeOption.style.display = 'none';
                } else {
                    diffEl.textContent = 'Rp 0 (sama)';
                    diffEl.className = 'fw-bold text-muted';
                    chargeOption.style.display = 'none';
                }

                document.getElementById('transfer_preview').style.display = 'block';
                document.getElementById('btnConfirmTransfer').disabled = false;
            });
        }

        document.getElementById('transfer_new_checkout').addEventListener('change', function() {
            if (currentSelectedTransferRoom) {
                selectTransferRoom(
                    currentSelectedTransferRoom.roomId,
                    currentSelectedTransferRoom.roomNum,
                    currentSelectedTransferRoom.roomType,
                    currentSelectedTransferRoom.price,
                    currentSelectedTransferRoom.tier
                );
            }
        });

        // Load rooms when modal opens
        document.getElementById('roomTransferModal').addEventListener('show.bs.modal', function() {
            loadTransferRooms();
        });
    </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-timer').forEach(function(btn) {
                const deadline = new Date(btn.dataset.deadline).getTime();
                if (!deadline) return;

                function updateTimer() {
                    const now = new Date().getTime();
                    const remaining = deadline - now;
                    const timerSpan = btn.querySelector('.timer');

                    if (remaining <= 0) {
                        btn.disabled = true;
                        btn.classList.add('disabled', 'text-muted');
                        btn.classList.remove('btn-warning');
                        if (timerSpan) timerSpan.textContent = '❌ Expired';
                        if (btn.tagName === 'A') {
                            btn.removeAttribute('href');
                            btn.style.pointerEvents = 'none';
                        }
                        return;
                    }

                    const hours = Math.floor(remaining / 3600000);
                    const minutes = Math.floor((remaining % 3600000) / 60000);
                    const seconds = Math.floor((remaining % 60000) / 1000);

                    if (timerSpan) {
                        timerSpan.textContent = '⏱️ ' +
                            hours.toString().padStart(2, '0') + ':' +
                            minutes.toString().padStart(2, '0') + ':' +
                            seconds.toString().padStart(2, '0');
                    }

                    if (remaining < 3600000) {
                        btn.classList.add('text-warning');
                    }
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            });
        });
    </script>
    <script>
        function showVehiclePhoto(el) {
            const photoInArr = JSON.parse(el.dataset.photoIn || '[]');
            const photoOutArr = JSON.parse(el.dataset.photoOut || '[]');
            const plate = el.dataset.plate;

            document.getElementById('vehiclePhotoTitle').textContent = 'Foto Kendaraan - ' + plate;

            let htmlIn = '';
            photoInArr.forEach(p => {
                htmlIn += '<img loading="lazy" src="' + p + '" class="img-fluid rounded mb-2 border" style="max-height: 250px; display:inline-block; margin-right:5px;">';
            });
            document.getElementById('vehiclePhotoInContainer').innerHTML = htmlIn;

            if (photoOutArr && photoOutArr.length > 0) {
                let htmlOut = '';
                photoOutArr.forEach(p => {
                    htmlOut += '<img loading="lazy" src="' + p + '" class="img-fluid rounded mb-2 border" style="max-height: 250px; display:inline-block; margin-right:5px;">';
                });
                document.getElementById('vehiclePhotoOutContainer').innerHTML = htmlOut;
                document.getElementById('vehiclePhotoOutSection').style.display = 'block';
            } else {
                document.getElementById('vehiclePhotoOutSection').style.display = 'none';
            }
            var modal = new bootstrap.Modal(document.getElementById('vehiclePhotoModal'));
            modal.show();
        }
    // Pricing Audit
    document.getElementById('btnAuditPricing')?.addEventListener('click', function() {
        const bookingId = this.dataset.bookingId;
        const resultDiv = document.getElementById('auditResult');
        const applyBtn = document.getElementById('btnApplyAuditFix');
        resultDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning" role="status"></div><p class="mt-2 text-muted">Scanning pricing...</p></div>';
        applyBtn.style.display = 'none';
        new bootstrap.Modal(document.getElementById('auditPricingModal')).show();

        fetch(`/admin/bookings/${bookingId}/audit-pricing`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                return;
            }
            const a = data.data;
            const severityBadge = { critical: 'danger', high: 'warning', medium: 'info', info: 'secondary' };

            if (a.anomalies.length === 0) {
                let html = '<div class="text-center text-success py-3">';
                html += '<i class="ri-checkbox-circle-fill fs-3 d-block mb-1"></i>';
                html += '<span class="fw-semibold">No anomaly detected. Pricing is correct.</span></div>';
                html += '<div class="mt-2 fs-12 text-muted">Room: ' + (a.room_number || 'N/A') + ' | Rate: Rp ' + new Intl.NumberFormat('id-ID').format(a.room_rate) + '/bulan | ' + a.nights + ' malam (' + a.stay_type + ')</div>';
                resultDiv.innerHTML = html;
                return;
            }

            let html = '<div class="alert alert-warning d-flex align-items-center mb-3"><i class="ri-alert-fill me-2 fs-4"></i>';
            html += '<div><strong>' + a.anomaly_count + ' anomal' + (a.anomaly_count > 1 ? 'ies' : 'y') + ' found</strong>';
            if (a.has_critical) html += ' <span class="badge bg-danger ms-1">CRITICAL</span>';
            html += '</div></div>';

            html += '<div class="table-responsive"><table class="table table-sm mb-0">';
            html += '<thead class="table-light"><tr><th>Issue</th><th>Field</th><th class="text-end">Expected</th><th class="text-end">Actual</th><th class="text-end">Diff</th></tr></thead><tbody>';

            a.anomalies.forEach(function(an) {
                const diffClass = an.diff > 0 ? 'text-danger' : (an.diff < 0 ? 'text-success' : 'text-muted');
                const diffSign = an.diff > 0 ? '+' : '';
                const sev = severityBadge[an.severity] || 'secondary';
                html += '<tr>';
                html += '<td><span class="badge bg-' + sev + '-subtle text-' + sev + ' me-1">' + (an.severity || 'medium').toUpperCase() + '</span>';
                html += '<small class="text-muted">' + (an.code || '').replace(/_/g, ' ') + '</small>';
                if (an.description) html += '<br><small class="text-muted">' + an.description + '</small>';
                html += '</td>';
                html += '<td class="fw-medium">' + an.label + '</td>';
                html += '<td class="text-end text-nowrap">Rp ' + new Intl.NumberFormat('id-ID').format(an.expected) + '</td>';
                html += '<td class="text-end text-nowrap">Rp ' + new Intl.NumberFormat('id-ID').format(an.actual) + '</td>';
                html += '<td class="text-end text-nowrap ' + diffClass + '">' + diffSign + 'Rp ' + new Intl.NumberFormat('id-ID').format(an.diff) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            html += '<div class="mt-3 fs-12 text-muted"><i class="ri-information-line me-1"></i>Room: ' + (a.room_number || 'N/A') + ' | Rate: Rp ' + new Intl.NumberFormat('id-ID').format(a.room_rate) + '/bulan | ' + a.nights + ' malam (' + a.stay_type + ')</div>';
            resultDiv.innerHTML = html;
            applyBtn.style.display = 'inline-block';
        })
        .catch(err => {
            resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
        });
    });

    document.getElementById('btnApplyAuditFix')?.addEventListener('click', function() {
        if (!confirm('Apply pricing fix? This will recalculate base_price, discount, and total_price.')) return;
        const bookingId = document.getElementById('btnAuditPricing').dataset.bookingId;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Applying...';

        fetch(`/admin/bookings/${bookingId}/apply-audit-fix`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to apply fix');
                this.disabled = false;
                this.innerHTML = '<i class="ri-check-double-line me-1"></i> Apply Fix';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            this.disabled = false;
            this.innerHTML = '<i class="ri-check-double-line me-1"></i> Apply Fix';
        });
    });
</script>
@endsection
