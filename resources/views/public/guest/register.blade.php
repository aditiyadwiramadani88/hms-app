@extends('public.layouts.app')

@section('title', 'Guest Register')
@section('page_title', 'Guest Register')

@section('content')
<!--==================================================-->
<!-- Start Register Section  -->
<!--==================================================-->
<div class="contact-section inner_page" style="padding-top: 150px; padding-bottom: 100px; min-height: 80vh; background: #f5f5f5 !important;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5" data-aos="fade-up">
                <div style="background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div class="text-center mb-4">
                        <h3 style="font-family: 'Playfair Display', serif; color: #222;">Create Account</h3>
                        <p style="color: #666; font-size: 14px;">Register to book rooms online</p>
                    </div>

                    @if($errors->any())
                        <div style="padding: 10px 15px; border-radius: 5px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-bottom: 20px;">
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" data-ajax="true" action="{{ route('guest.register') }}">
                        @csrf
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Full Name *</label>
                            <input type="text" name="name" placeholder="Your full name" value="{{ old('name') }}" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Email Address *</label>
                            <input type="email" name="email" placeholder="your@email.com" value="{{ old('email') }}" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Phone Number</label>
                            <input type="text" name="phone" placeholder="+62 xxx xxxx xxxx" value="{{ old('phone') }}" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Password *</label>
                            <input type="password" name="password" placeholder="Min 8 characters" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Confirm Password *</label>
                            <input type="password" name="password_confirmation" placeholder="Repeat password" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>

                        <button type="submit" data-submit-protect="true" style="display: block; width: 100%; padding: 14px; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer; background: #8b7355; color: #fff;">
                            Register →
                        </button>

                        <div class="text-center" style="margin-top: 20px;">
                            <p style="font-size: 14px; color: #666;">Already have an account? <a href="{{ route('guest.login') }}" style="color: #8b7355; font-weight: 600;">Login here</a></p>
                        </div>

                        <div class="text-center" style="margin-top: 10px;">
                            <a href="{{ route('public.index') }}" style="font-size: 13px; color: #999;">← Back to Home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--==================================================-->
<!-- End Register Section  -->
<!--==================================================-->
@endsection
