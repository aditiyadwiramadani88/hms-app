@extends('public.layouts.app')

@section('title', 'Contact')
@section('page_title', 'Contact')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; background: #f5f5f5;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ CONTACT US</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 36px;">Get in Touch</h2>
        </div>

        <div class="row">
            <div class="col-lg-4" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%;">
                    <div style="font-size: 36px; margin-bottom: 15px;">📍</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Address</h5>
                    <p style="color: #666; font-size: 14px;">{{ $hotel->address ?? 'Hotel Address' }}</p>
                </div>
            </div>
            <div class="col-lg-4" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%;">
                    <div style="font-size: 36px; margin-bottom: 15px;">📞</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Phone</h5>
                    <p style="color: #666; font-size: 14px;">{{ $hotel->phone ?? '+62 xxx xxxx xxxx' }}</p>
                </div>
            </div>
            <div class="col-lg-4" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%;">
                    <div style="font-size: 36px; margin-bottom: 15px;">✉️</div>
                    <h5 style="color: #222; margin-bottom: 10px;">Email</h5>
                    <p style="color: #666; font-size: 14px;">{{ $hotel->email ?? 'info@hotel.com' }}</p>
                </div>
            </div>
        </div>

        <div style="background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-top: 20px;">
            <h4 style="color: #222; margin-bottom: 20px; font-family: 'Playfair Display', serif;">Send us a Message</h4>
            <form>
                <div class="row">
                    <div class="col-md-6" style="margin-bottom: 15px;">
                        <input type="text" placeholder="Your Name" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                    </div>
                    <div class="col-md-6" style="margin-bottom: 15px;">
                        <input type="email" placeholder="Your Email" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                    </div>
                    <div class="col-12" style="margin-bottom: 15px;">
                        <input type="text" placeholder="Subject" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                    </div>
                    <div class="col-12" style="margin-bottom: 15px;">
                        <textarea rows="5" placeholder="Your Message" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; resize: vertical;"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" data-submit-protect="true" style="padding: 14px 30px; background: #8b7355; color: #fff; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer;">Send Message →</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
