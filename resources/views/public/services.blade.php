@extends('public.layouts.app')

@section('title', 'Services')
@section('page_title', 'Services')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; background: #f5f5f5;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ OUR SERVICES</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 36px;">Enhancing Your Stay</h2>
            <p style="color: #666;">Premium services for your comfort</p>
        </div>

        <div class="row">
            @forelse($services as $service)
            <div class="col-lg-4 col-md-6" style="margin-bottom: 30px;">
                <div style="background: #fff; padding: 35px 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%;">
                    <div style="font-size: 36px; margin-bottom: 15px;">{{ $service->icon ?? '⭐' }}</div>
                    <h5 style="color: #222; margin-bottom: 10px;">{{ $service->name }}</h5>
                    <p style="color: #666; font-size: 14px; line-height: 1.7;">{{ $service->description }}</p>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted">
                <p>No services available at the moment.</p>
            </div>
            @endforelse
        </div>

        {{-- CTA --}}
        <div style="background: #2c2c2c; border-radius: 10px; padding: 40px; margin-top: 30px; text-align: center;">
            <h3 style="color: #fff; font-family: 'Playfair Display', serif; margin-bottom: 10px;">Need More Information?</h3>
            <p style="color: #aaa; margin-bottom: 20px;">Contact us for special requests or custom services</p>
            <a href="{{ route('public.contact') }}" style="display: inline-block; padding: 12px 25px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600;">Contact Us →</a>
        </div>
    </div>
</div>
@endsection
