@extends('public.layouts.app')

@section('title', 'My Dashboard')
@section('page_title', 'My Dashboard')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 30px;">
            <div>
                <h3 style="font-family: 'Playfair Display', serif; color: #222; margin-bottom: 5px;">My Bookings</h3>
                <p style="color: #666; margin: 0;">Welcome, <strong>{{ $guest->name }}</strong></p>
            </div>
            <form method="POST" data-ajax="true" action="{{ route('guest.logout') }}">
                @csrf
                <button type="submit" data-submit-protect="true" style="padding: 8px 16px; border: 1px solid #dc3545; color: #dc3545; background: #fff; border-radius: 5px; font-size: 13px; cursor: pointer;">Logout</button>
            </form>
        </div>

        {{-- Bookings List --}}
        @forelse($bookings as $booking)
        <div style="background: #fff; border-radius: 10px; padding: 20px 25px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h5 style="color: #222; margin-bottom: 5px; font-size: 16px;">{{ $booking->room?->roomType?->name ?? 'N/A' }}</h5>
                    <p style="color: #999; font-size: 12px; margin: 0;">Room {{ $booking->room?->room_number ?? '' }} • Booking #{{ $booking->id }}</p>
                </div>
                <div class="col-md-3">
                    <small style="color: #999;">Check-in</small>
                    <p style="color: #333; font-weight: 600; margin: 0; font-size: 14px;">{{ $booking->check_in->format('d M Y') }}</p>
                    <small style="color: #999;">Check-out</small>
                    <p style="color: #333; font-weight: 600; margin: 0; font-size: 14px;">{{ $booking->check_out->format('d M Y') }}</p>
                </div>
                <div class="col-md-3">
                    <small style="color: #999;">Status</small>
                    @php
                        $statusColor = match($booking->status) {
                            'confirmed' => '#10b981',
                            'checked_in' => '#3b82f6',
                            'checked_out' => '#6b7280',
                            'cancelled' => '#ef4444',
                            default => '#f59e0b',
                        };
                    @endphp
                    <p style="margin: 0;"><span style="display: inline-block; padding: 3px 10px; background: {{ $statusColor }}; color: #fff; border-radius: 12px; font-size: 11px; font-weight: 600;">{{ ucfirst($booking->status) }}</span></p>
                    <small style="color: #999;">Total</small>
                    <p style="color: #8b7355; font-weight: 700; margin: 0;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                </div>
                <div class="col-md-3 text-end">
                    @if($booking->status === 'pending' && $booking->source === 'online')
                        <a href="{{ route('public.payment.pay', $booking->id) }}" style="display: inline-block; padding: 8px 16px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: 600;">Pay Now</a>
                    @endif
                    <a href="{{ route('guest.bookings.show', $booking->id) }}" style="display: inline-block; padding: 8px 16px; border: 1px solid #ddd; color: #666; text-decoration: none; border-radius: 5px; font-size: 13px; margin-top: 5px;">Details</a>
                </div>
            </div>
        </div>
        @empty
        <div style="background: #fff; border-radius: 10px; padding: 60px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h4 style="color: #222; margin-bottom: 10px;">No bookings yet</h4>
            <p style="color: #666; margin-bottom: 20px;">Start by browsing our rooms and making your first reservation.</p>
            <a href="{{ route('public.rooms.index') }}" style="display: inline-block; padding: 12px 25px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600;">Browse Rooms →</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
