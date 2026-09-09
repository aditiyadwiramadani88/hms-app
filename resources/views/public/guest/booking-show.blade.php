@extends('public.layouts.app')

@section('title', 'Booking Details')
@section('page_title', 'Booking Details')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                {{-- Booking Info --}}
                <div style="background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
                    <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 20px;">
                        <h4 style="color: #222; margin: 0; font-family: 'Playfair Display', serif;">Booking #{{ $booking->id }}</h4>
                        @php
                            $statusColor = match($booking->status) {
                                'confirmed' => '#10b981',
                                'checked_in' => '#3b82f6',
                                'checked_out' => '#6b7280',
                                'cancelled' => '#ef4444',
                                default => '#f59e0b',
                            };
                        @endphp
                        <span style="padding: 5px 12px; background: {{ $statusColor }}; color: #fff; border-radius: 12px; font-size: 12px; font-weight: 600;">{{ ucfirst($booking->status) }}</span>
                    </div>

                    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px;">
                        <div class="row">
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Room Type</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->room?->roomType?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Room Number</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->room?->room_number ?? 'Not assigned' }}</p>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-in</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_in->format('d M Y') }} — 14:00</p>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Check-out</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->check_out->format('d M Y') }} — 12:00</p>
                            </div>
                            <div class="col-md-6">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Guest</small>
                                <p style="color: #333; font-weight: 600; margin: 0;">{{ $booking->guest?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <small style="color: #999; font-size: 11px; text-transform: uppercase;">Total Price</small>
                                <p style="color: #8b7355; font-weight: 700; font-size: 18px; margin: 0;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment History --}}
                @if($booking->transactions->count() > 0)
                <div style="background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h5 style="color: #222; margin-bottom: 15px;">Payment History</h5>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="padding: 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">Date</th>
                                <th style="padding: 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">Type</th>
                                <th style="padding: 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">Amount</th>
                                <th style="padding: 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->transactions as $transaction)
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 0; color: #333; font-size: 14px;">{{ $transaction->created_at->format('d M Y') }}</td>
                                <td style="padding: 12px 0; color: #333; font-size: 14px;">{{ ucfirst($transaction->type) }}</td>
                                <td style="padding: 12px 0; color: #333; font-size: 14px; font-weight: 600;">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                <td style="padding: 12px 0;">
                                    @php $trxColor = $transaction->status === 'success' ? '#10b981' : '#f59e0b'; @endphp
                                    <span style="padding: 3px 8px; background: {{ $trxColor }}; color: #fff; border-radius: 10px; font-size: 11px;">{{ ucfirst($transaction->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    @if($booking->status === 'pending' && $booking->source === 'online')
                        <a href="{{ route('public.payment.pay', $booking->id) }}" style="display: block; width: 100%; padding: 12px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center; margin-bottom: 10px;">💳 Pay Now</a>
                    @endif
                    <a href="{{ route('guest.dashboard') }}" style="display: block; width: 100%; padding: 12px; border: 1px solid #ddd; color: #666; text-decoration: none; border-radius: 5px; text-align: center; margin-bottom: 10px;">← Back to Dashboard</a>
                    <a href="{{ route('public.rooms.index') }}" style="display: block; width: 100%; padding: 12px; background: #222; color: #fff; text-decoration: none; border-radius: 5px; text-align: center;">Book Another Room</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
