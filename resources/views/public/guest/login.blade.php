@extends('public.layouts.app')

@section('title', 'Guest Login')
@section('page_title', 'Guest Login')

@section('content')
<!--==================================================-->
<!-- Start Login Section  -->
<!--==================================================-->
<div class="contact-section inner_page" style="padding-top: 150px; padding-bottom: 100px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5" data-aos="fade-up">
                <div class="contact-form" style="background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div class="text-center mb-4">
                        <h3 style="font-family: 'Playfair Display', serif; color: #222;">Sign In</h3>
                        <p style="color: #666; font-size: 14px;">Login to manage your bookings</p>
                    </div>

                    @if(session('error'))
                        <div style="padding: 10px 15px; border-radius: 5px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-bottom: 20px;">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" data-ajax="true" action="{{ route('guest.login') }}">
                        @csrf
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Email Address</label>
                            <input type="email" name="email" placeholder="your@email.com" value="{{ old('email') }}" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                            @error('email')
                                <span style="color: #dc3545; font-size: 12px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Password</label>
                            <input type="password" name="password" placeholder="Enter your password" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                            @error('password')
                                <span style="color: #dc3545; font-size: 12px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; cursor: pointer; color: #555;">
                                <input type="checkbox" name="remember" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 5px;">
                                Remember Me
                            </label>
                        </div>

                        <button type="submit" data-submit-protect="true" style="display: block; width: 100%; padding: 14px; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer; background: #8b7355; color: #fff; transition: background 0.3s;">
                            Login →
                        </button>

                        <div class="text-center" style="margin-top: 20px;">
                            <p style="font-size: 14px; color: #666;">Don't have an account? <a href="{{ route('guest.register') }}" style="color: #8b7355; font-weight: 600;">Register here</a></p>
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
<!-- End Login Section  -->
<!--==================================================-->
@endsection
