@extends('public.layouts.app')

@section('title', $hotel?->name ?? 'Hotel')
@section('page_title', 'Luxury Hotel')
@section('is_home', true)

@section('content')
<!--==================================================-->
<!-- Hero + Booking Section -->
<!--==================================================-->
<div style="background: #2c2c2c; padding: 80px 0 0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div style="padding: 40px 0;">
                    <h4 style="color: #8b7355; font-size: 14px; letter-spacing: 2px; margin-bottom: 15px;">✦ {{ $hotel?->name ?? 'LUXURY HOTEL' }}</h4>
                    <h1 style="font-family: 'Playfair Display', serif; color: #fff; font-size: 44px; line-height: 1.2; margin-bottom: 20px;">Discover Your Next<br>Luxurious <span style="color: #8b7355;">Escapes</span></h1>
                    <p style="color: #aaa; font-size: 15px; margin-bottom: 30px;">Experience world-class hospitality, comfort, and luxury at {{ $hotel?->name ?? 'our hotel' }}. Your perfect getaway awaits.</p>
                    <a href="{{ route('public.rooms.index') }}" style="display: inline-block; padding: 14px 30px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 14px;">EXPLORE ROOMS →</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div style="background: #222; border-radius: 10px; padding: 30px; margin-bottom: -60px; position: relative; z-index: 10;">
                    <h4 style="color: #fff; text-align: center; margin-bottom: 20px; font-family: 'Playfair Display', serif;">Booking Online</h4>
                    <form action="{{ route('public.booking.form') }}" method="GET">
                        <div class="row">
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label style="color: #aaa; font-size: 12px; display: block; margin-bottom: 5px;">Check-in Date</label>
                                <input type="date" name="check_in" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required style="width: 100%; padding: 10px 12px; border: 1px solid #444; border-radius: 5px; background: #333; color: #fff; font-size: 14px;">
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label style="color: #aaa; font-size: 12px; display: block; margin-bottom: 5px;">Jumlah Malam</label>
                                <select name="nights" style="width: 100%; padding: 10px 12px; border: 1px solid #444; border-radius: 5px; background: #333; color: #fff; font-size: 14px;">
                                    <option value="1">1 Malam</option>
                                    <option value="2">2 Malam</option>
                                    <option value="3">3 Malam</option>
                                    <option value="5">5 Malam</option>
                                    <option value="7">7 Malam</option>
                                    <option value="14">14 Malam</option>
                                    <option value="30">30 Malam</option>
                                </select>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label style="color: #aaa; font-size: 12px; display: block; margin-bottom: 5px;">Adults</label>
                                <select name="adults" style="width: 100%; padding: 10px 12px; border: 1px solid #444; border-radius: 5px; background: #333; color: #fff; font-size: 14px;">
                                    <option value="1">1 Adult</option>
                                    <option value="2" selected>2 Adults</option>
                                    <option value="3">3 Adults</option>
                                    <option value="4">4 Adults</option>
                                </select>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label style="color: #aaa; font-size: 12px; display: block; margin-bottom: 5px;">Children</label>
                                <select name="children" style="width: 100%; padding: 10px 12px; border: 1px solid #444; border-radius: 5px; background: #333; color: #fff; font-size: 14px;">
                                    <option value="0" selected>0 Children</option>
                                    <option value="1">1 Child</option>
                                    <option value="2">2 Children</option>
                                    <option value="3">3 Children</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" data-submit-protect="true" style="width: 100%; padding: 14px; background: #8b7355; color: #fff; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer;">BOOK NOW →</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!--==================================================-->
<!-- Rooms Section (No Images) -->
<!--==================================================-->
<div style="padding: 120px 0 80px; background: #1a1a1a;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ ROOMS & SUITES</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #fff; font-size: 36px;">Sleep in Comfort, Choose From<br>Our Rooms & Suites</h2>
        </div>
        <div class="row">
            @foreach($roomTypes as $roomType)
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #2c2c2c; border-radius: 10px; overflow: hidden; height: 100%; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    @if($roomType->image)
                    <div style="height: 160px; overflow: hidden;">
                        <img src="{{ Storage::url($roomType->image) }}" alt="{{ $roomType->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    @endif
                    <div style="background: #8b7355; padding: 30px 20px; text-align: center;">
                        <h1 style="color: #fff; font-family: 'Playfair Display', serif; font-size: 28px; margin: 0;">{{ $roomType->name }}</h1>
                    </div>
                    <div style="padding: 25px 20px;">
                        <p style="color: #8b7355; font-size: 13px; font-weight: 600; margin-bottom: 10px;">Mulai dari</p>
                        <h3 style="color: #fff; font-size: 22px; margin-bottom: 15px;">Rp {{ number_format($roomType->min_price ?? $roomType->base_price, 0, ',', '.') }} <small style="font-size: 13px; color: #aaa;">/ malam</small></h3>
                        <ul style="list-style: none; padding: 0; margin: 0 0 20px;">
                            <li style="color: #aaa; font-size: 13px; padding: 5px 0; border-bottom: 1px solid #3a3a3a;">✓ {{ $roomType->max_guests ?? 2 }} Tamu</li>
                            <li style="color: #aaa; font-size: 13px; padding: 5px 0; border-bottom: 1px solid #3a3a3a;">✓ {{ $roomType->available_count ?? $roomType->rooms->where('status', 'Available')->count() }} Kamar Tersedia</li>
                            <li style="color: #aaa; font-size: 13px; padding: 5px 0;">✓ AC, WiFi, TV</li>
                        </ul>
                        <a href="{{ route('public.rooms.index', ['type' => $roomType->id]) }}" style="display: block; text-align: center; padding: 10px; border: 1px solid #8b7355; color: #8b7355; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: 600;">VIEW DETAILS →</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!--==================================================-->
<!-- About Section -->
<!--==================================================-->
<div style="padding: 80px 0; background: #fff;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ OUR HOTEL</h4>
                <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 34px; line-height: 1.3; margin-bottom: 20px;">Your Gateway to Comfort, Luxury, and World-Class Hospitality</h2>
                <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 25px;">{{ $hotel?->name ?? 'Our Hotel' }} offers exceptional hospitality with world-class amenities, stunning views, and unforgettable experiences for every guest.</p>
                <div style="margin-bottom: 25px;">
                    <p style="color: #333; margin-bottom: 8px;">✓ Exclusive Deals & Discounts</p>
                    <p style="color: #333; margin-bottom: 8px;">✓ 24/7 Premium Room Service</p>
                    <p style="color: #333; margin-bottom: 8px;">✓ Strategic Location</p>
                    <p style="color: #333; margin-bottom: 8px;">✓ Clean & Comfortable Rooms</p>
                </div>
                <a href="{{ route('public.about') }}" style="display: inline-block; padding: 12px 25px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 13px;">MORE ABOUT →</a>
            </div>
            <div class="col-lg-6">
                <div style="background: #f5f5f5; border-radius: 10px; padding: 60px 40px; text-align: center;">
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 64px; color: #8b7355; margin-bottom: 10px;">6K+</h1>
                    <p style="color: #666; font-size: 16px; margin-bottom: 30px;">Happy Customers</p>
                    <div class="row">
                        <div class="col-4">
                            <h3 style="color: #222; font-size: 24px; margin-bottom: 5px;">{{ \App\Models\Room::count() }}</h3>
                            <p style="color: #999; font-size: 12px;">Rooms</p>
                        </div>
                        <div class="col-4">
                            <h3 style="color: #222; font-size: 24px; margin-bottom: 5px;">24/7</h3>
                            <p style="color: #999; font-size: 12px;">Service</p>
                        </div>
                        <div class="col-4">
                            <h3 style="color: #222; font-size: 24px; margin-bottom: 5px;">5★</h3>
                            <p style="color: #999; font-size: 12px;">Rating</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--==================================================-->
<!-- Services Section -->
<!--==================================================-->
<div style="padding: 80px 0; background: #f9f9f9;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ SERVICES</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 34px;">Enhancing Your Stay<br>With Exclusive Services</h2>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">🚗</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Transportation</h5>
                    <p style="color: #666; font-size: 13px;">Airport transfers and local transport</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">📶</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Free Wi-Fi</h5>
                    <p style="color: #666; font-size: 13px;">High-speed internet access</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">🍽️</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Room Service</h5>
                    <p style="color: #666; font-size: 13px;">24/7 dining options</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">🏊</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Facilities</h5>
                    <p style="color: #666; font-size: 13px;">Pool, gym, and more</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!--==================================================-->
<!-- CTA Section -->
<!--==================================================-->
<div style="padding: 80px 0; background: #2c2c2c; text-align: center;">
    <div class="container">
        <h2 style="font-family: 'Playfair Display', serif; color: #fff; font-size: 36px; margin-bottom: 15px;">Ready to Book Your Stay?</h2>
        <p style="color: #aaa; font-size: 16px; margin-bottom: 30px;">Contact us or book online for the best rates</p>
        <a href="{{ route('public.booking.form') }}" style="display: inline-block; padding: 14px 35px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 15px; margin-right: 15px;">BOOK NOW →</a>
        <a href="{{ route('public.contact') }}" style="display: inline-block; padding: 14px 35px; border: 1px solid #8b7355; color: #8b7355; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 15px;">CONTACT US</a>
    </div>
</div>
@endsection
