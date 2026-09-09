@extends('public.layouts.app')

@section('title', 'Payment')
@section('page_title', 'Payment')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div style="background: #fff; border-radius: 10px; padding: 35px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div class="text-center" style="margin-bottom: 25px;">
                        <h3 style="font-family: 'Playfair Display', serif; color: #222;">Booking Summary</h3>
                        <p style="color: #666; font-size: 14px;">Review your booking before payment</p>
                    </div>

                    {{-- Booking Details --}}
                    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Room Type</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->room?->roomType?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Room Number</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->room?->room_number ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Guest</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->guest?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Email</small>
                                <p style="color: #333; font-weight: 600; margin: 0; font-size: 13px;">{{ $booking->guest?->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-in</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_in->format('d M Y') }}</p>
                            </div>
                            <div class="col-6">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-out</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_out->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div style="border-top: 2px solid #eee; padding-top: 20px; margin-bottom: 25px;">
                        <div class="d-flex justify-content-between" style="margin-bottom: 8px;">
                            <span style="color: #666;">Room ({{ $booking->check_in->diffInDays($booking->check_out) }} malam)</span>
                            <span style="color: #333;">Rp {{ number_format($booking->base_price, 0, ',', '.') }}</span>
                        </div>
                        @if($booking->tax_amount > 0)
                        <div class="d-flex justify-content-between" style="margin-bottom: 8px;">
                            <span style="color: #666;">Tax</span>
                            <span style="color: #333;">Rp {{ number_format($booking->tax_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between" style="padding-top: 10px; border-top: 1px solid #eee;">
                            <strong style="color: #222; font-size: 18px;">Total</strong>
                            <strong style="color: #8b7355; font-size: 18px;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    {{-- Pay Button --}}
                    <button id="pay-button" style="display: block; width: 100%; padding: 16px; background: #8b7355; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer;">
                        💳 Pay Now — Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                    </button>

                    <p style="text-align: center; margin-top: 15px; color: #999; font-size: 12px;">
                        Secure payment powered by Midtrans
                    </p>

                    <div class="text-center" style="margin-top: 10px;">
                        <a href="{{ route('public.index') }}" style="font-size: 13px; color: #999;">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $midtransClientKey }}"></script>
<script>
    document.getElementById('pay-button').addEventListener('click', function () {
        snap.pay('{{ $snapToken }}', {
            onSuccess: function (result) {
                window.location.href = '{{ route("public.payment.success", $booking->id) }}';
            },
            onPending: function (result) {
                alert('Payment pending. Please complete your payment.');
            },
            onError: function (result) {
                alert('Payment failed. Please try again.');
            },
            onClose: function () {
                alert('You closed the payment popup without completing payment.');
            }
        });
    });
</script>
@endsection
