@extends('public.layouts.app')

@section('title', $page->title ?? 'About Us')
@section('page_title', $page->title ?? 'About Us')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; background: #f5f5f5;">
    <div class="container">
        {{-- Header --}}
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ ABOUT US</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 36px;">Welcome to<br>{{ $hotel->name ?? 'Our Hotel' }}</h2>
        </div>

        {{-- About Content --}}
        <div class="row" style="margin-bottom: 60px;">
            <div class="col-lg-6">
                <div style="background: #2c2c2c; border-radius: 10px; padding: 50px 30px; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center">
                        <h1 style="font-family: 'Playfair Display', serif; font-size: 72px; color: #8b7355;">{{ $hotel->name ? substr($hotel->name, 0, 1) : 'H' }}</h1>
                        <p style="color: #aaa; font-size: 14px;">{{ $hotel->name ?? 'Hotel' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div style="padding: 20px 0;">
                    @if($page && $page->content)
                        <div style="color: #666; line-height: 1.8; margin-bottom: 20px;">{!! $page->content !!}</div>
                    @else
                        <h3 style="color: #222; font-family: 'Playfair Display', serif; margin-bottom: 20px;">Our Story</h3>
                        <p style="color: #666; line-height: 1.8; margin-bottom: 20px;">We are committed to providing exceptional hospitality and unforgettable experiences. Our hotel combines modern luxury with warm, personalized service to make every stay memorable.</p>
                        <p style="color: #666; line-height: 1.8; margin-bottom: 20px;">Located in the heart of the city, we offer easy access to major attractions, business districts, and cultural landmarks. Whether you're traveling for business or leisure, our dedicated team is here to ensure your comfort and satisfaction.</p>
                    @endif
                    
                    <div class="row" style="margin-top: 30px;">
                        <div class="col-6">
                            <h4 style="color: #8b7355; font-size: 28px; margin-bottom: 5px;">{{ \App\Models\Room::count() }}+</h4>
                            <p style="color: #666; font-size: 13px;">Rooms Available</p>
                        </div>
                        <div class="col-6">
                            <h4 style="color: #8b7355; font-size: 28px; margin-bottom: 5px;">24/7</h4>
                            <p style="color: #666; font-size: 13px;">Customer Service</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="row">
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">🏨</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Premium Rooms</h5>
                    <p style="color: #666; font-size: 13px;">Comfortable and well-equipped rooms for every need</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">📍</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Strategic Location</h5>
                    <p style="color: #666; font-size: 13px;">Easy access to attractions and business areas</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">👨‍💼</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Professional Staff</h5>
                    <p style="color: #666; font-size: 13px;">Dedicated team for your comfort</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="font-size: 36px; margin-bottom: 15px;">💰</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Best Value</h5>
                    <p style="color: #666; font-size: 13px;">Competitive pricing with premium quality</p>
                </div>
            </div>
        </div>

        {{-- Contact Info --}}
        <div style="background: #2c2c2c; border-radius: 10px; padding: 40px; margin-top: 30px;">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h3 style="color: #fff; font-family: 'Playfair Display', serif; margin-bottom: 10px;">Get in Touch</h3>
                    <p style="color: #aaa;">We'd love to hear from you</p>
                </div>
                <div class="col-lg-6">
                    <p style="color: #ddd; margin-bottom: 8px;">📍 {{ $hotel->address ?? 'Hotel Address' }}</p>
                    <p style="color: #ddd; margin-bottom: 8px;">📞 {{ $hotel->phone ?? '+62 xxx xxxx xxxx' }}</p>
                    <p style="color: #ddd; margin-bottom: 0;">✉️ {{ $hotel->email ?? 'info@hotel.com' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
