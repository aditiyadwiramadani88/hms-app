@extends('public.layouts.app')

@section('title', 'Payment Success')
@section('page_title', 'Payment Success')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div style="background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center;">
                    <div style="width: 70px; height: 70px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <span style="color: #fff; font-size: 32px;">✓</span>
                    </div>

                    <h3 style="font-family: 'Playfair Display', serif; color: #222; margin-bottom: 10px;">Thank You!</h3>
                    <p style="color: #666; font-size: 14px; margin-bottom: 30px;">Your payment was successful. Your booking has been confirmed.</p>

                    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 25px;">
                        <div style="margin-bottom: 12px;">
                            <small style="color: #999; font-size: 11px; text-transform: uppercase;">Room</small>
                            <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->room?->roomType?->name ?? 'N/A' }} — {{ $booking->room?->room_number ?? '' }}</p>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-in</small>
                            <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_in->format('d M Y') }} — 14:00</p>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-out</small>
                            <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_out->format('d M Y') }} — 12:00</p>
                        </div>
                        <div style="border-top: 1px solid #eee; padding-top: 12px;">
                            <small style="color: #999; font-size: 11px; text-transform: uppercase;">Total Paid</small>
                            <p style="color: #8b7355; font-weight: 700; font-size: 18px; margin: 0;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        @auth('guest')
                        <a href="{{ route('guest.dashboard') }}" style="padding: 12px 20px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: 600;">View My Bookings →</a>
                        @endauth
                        <a href="{{ route('public.index') }}" style="padding: 12px 20px; border: 1px solid #ddd; color: #666; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: 600;">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
