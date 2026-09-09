@extends('public.layouts.app')

@section('title', 'Gallery')
@section('page_title', 'Gallery')

@section('css')
<style>
    .gallery-img { cursor: pointer; transition: transform 0.3s; }
    .gallery-img:hover { transform: scale(1.03); }
    .gallery-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; padding: 15px; opacity: 0; transition: opacity 0.3s; }
    .gallery-item:hover .gallery-overlay { opacity: 1; }
</style>
@endsection

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; background: #f5f5f5;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px;">
            <h4 style="color: #8b7355; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px;">→ GALLERY</h4>
            <h2 style="font-family: 'Playfair Display', serif; color: #222; font-size: 36px;">Our Hotel Gallery</h2>
            <p style="color: #666;">Explore our facilities and rooms</p>
        </div>

        @if($photos->count() > 0)
        <div class="row g-3">
            @foreach($photos as $photo)
            <div class="col-lg-4 col-md-6">
                <div class="gallery-item position-relative rounded overflow-hidden shadow-sm">
                    <img loading="lazy" src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->title }}" class="img-fluid gallery-img w-100" style="height: 250px; object-fit: cover;">
                    <div class="gallery-overlay">
                        <h6 class="mb-1">{{ $photo->title ?? 'Untitled' }}</h6>
                        @if($photo->description)<small>{{ Str::limit($photo->description, 60) }}</small>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="background: #fff; border-radius: 10px; padding: 60px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div style="font-size: 48px; margin-bottom: 20px;">📷</div>
            <h4 style="color: #222; margin-bottom: 10px;">Gallery Coming Soon</h4>
            <p style="color: #666;">We're preparing beautiful photos of our hotel for you. Check back soon!</p>
            <a href="{{ route('public.rooms.index') }}" style="display: inline-block; margin-top: 20px; padding: 12px 25px; background: #8b7355; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600;">Browse Rooms →</a>
        </div>
        @endif
    </div>
</div>
@endsection
