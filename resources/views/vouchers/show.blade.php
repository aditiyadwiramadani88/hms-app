@extends('layouts.master')
@section('title') Voucher: {{ $voucher->code }} @endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('vouchers.index') }}">Vouchers</a> @endslot
    @slot('title') {{ $voucher->code }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-ticket-line me-2 text-primary"></i>Voucher Info</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-medium">Code</td>
                        <td class="text-end"><span class="badge bg-primary fs-12 px-3">{{ $voucher->code }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Name</td>
                        <td class="text-end">{{ $voucher->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Type</td>
                        <td class="text-end">
                            @if($voucher->type === 'percentage')
                                {{ $voucher->value }}% OFF
                            @else
                                Rp {{ number_format($voucher->value, 0, ',', '.') }} OFF
                            @endif
                        </td>
                    </tr>
                    @if($voucher->max_discount)
                    <tr>
                        <td class="text-muted fw-medium">Max Discount</td>
                        <td class="text-end">Rp {{ number_format($voucher->max_discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($voucher->min_booking_amount)
                    <tr>
                        <td class="text-muted fw-medium">Min Booking</td>
                        <td class="text-end">Rp {{ number_format($voucher->min_booking_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted fw-medium">Usage</td>
                        <td class="text-end">{{ $voucher->usage_count }} / {{ $voucher->usage_limit ?? '∞' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Valid</td>
                        <td class="text-end">
                            {{ $voucher->valid_from ? $voucher->valid_from->format('d M Y') : '-' }} s/d {{ $voucher->valid_until ? $voucher->valid_until->format('d M Y') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Status</td>
                        <td class="text-end">
                            @if($voucher->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2">
                    <a href="{{ route('vouchers.edit', $voucher) }}" class="btn btn-soft-primary btn-sm flex-grow-1">
                        <i class="ri-edit-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-soft-secondary btn-sm flex-grow-1">
                        <i class="ri-arrow-left-line me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1"><i class="ri-history-line me-2 text-info"></i>Usage History ({{ $usageHistory->count() }} bookings)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking</th>
                                <th>Tamu</th>
                                <th>Kamar</th>
                                <th>Check-in</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Discount</th>
                                <th>Tanggal Pakai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageHistory as $booking)
                            <tr>
                                <td>
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="fw-medium text-primary">
                                        #{{ $booking->booking_number ?? $booking->id }}
                                    </a>
                                </td>
                                <td>{{ $booking->guest->name ?? '-' }}</td>
                                <td>{{ $booking->room->room_number ?? ($booking->custom_room_name ?? '-') }}</td>
                                <td>{{ $booking->check_in->format('d M Y') }}</td>
                                <td class="text-end">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                                <td class="text-end text-success fw-medium">- Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</td>
                                <td>{{ $booking->created_at->format('d M Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ri-ticket-line fs-1 d-block mb-2"></i>
                                    Voucher ini belum pernah digunakan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($usageHistory->count() > 0)
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total discount diberikan:</span>
                    <span class="fw-bold text-success">Rp {{ number_format($usageHistory->sum('discount_amount'), 0, ',', '.') }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
