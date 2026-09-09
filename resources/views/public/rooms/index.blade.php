@extends('public.layouts.app')

@section('title', 'Rooms & Suites')
@section('page_title', 'Rooms & Suites')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 40px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ ROOMS & SUITES</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 36px;">{{ $typeName ?? 'Choose Your Perfect Room' }}</h2>
            @if($typeName)
                <p style="color: #666;">{{ $rooms->count() }} kamar tersedia</p>
            @endif
        </div>

        <div class="row">
            @forelse($rooms as $room)
            <div class="col-lg-4 col-md-6" style="margin-bottom: 30px;">
                    <div style="background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        @if($room->roomType->image ?? false)
                        <div style="height: 200px; overflow: hidden;">
                            <img src="{{ Storage::url($room->roomType->image) }}" alt="{{ $room->roomType->name ?? 'Room' }}" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        @endif
                        <div style="background: #2c2c2c; padding: 30px 20px; text-align: center;">
                            <h3 style="color: #fff; font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 5px;">Room {{ $room->room_number }}</h3>
                            <p style="color: #8b7355; font-size: 18px; font-weight: 700; margin: 0;">Rp {{ number_format($room->price_public ?? $room->roomType->base_price ?? 0, 0, ',', '.') }} <small style="font-size: 12px; color: #aaa;">/ malam</small></p>
                        </div>

                        <div style="padding: 25px 20px;">
                            <p style="color: #666; font-size: 13px; margin-bottom: 15px;">{{ $room->roomType->name ?? 'Room' }} — Tersedia</p>

                            <a href="{{ route('public.rooms.show', $room->id) }}" style="display: block; text-align: center; padding: 12px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-size: 14px; font-weight: 600;">VIEW DETAILS & BOOK →</a>
                        </div>
                </div>
            </div>
            @empty
            <div class="col-lg-12 text-center" style="padding: 60px;">
                <h3 style="color: #222;">No rooms available</h3>
                <p style="color: #666;">All rooms are currently occupied. Please check back later.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
