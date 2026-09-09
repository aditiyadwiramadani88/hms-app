<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function index()
    {
        $hotel = Hotel::find(session('public_branch_id', 1)) ?? Hotel::where('is_active', true)->first();
        
        if (!$hotel) {
            // Fallback for empty database (e.g. after migration)
            return view('public.index', ['hotel' => null, 'roomTypes' => collect()]);
        }

        $roomTypes = \App\Models\RoomType::with('rooms')
            ->where('is_active', true)
            ->where('hotel_id', $hotel->id)
            ->limit(4)->get()
            ->map(function($rt) {
                $available = $rt->rooms->whereIn('status', ['Available', 'available', 'Clean', 'clean']);
                $rt->min_price = $available->min('price_public') ?? $rt->base_price;
                $rt->available_count = $available->count();
                return $rt;
            })
            ->filter(fn($rt) => $rt->available_count > 0)
            ->values();
        return view('public.index', compact('hotel', 'roomTypes'));
    }

    public function about()
    {
        $hotel = Hotel::where('is_active', true)->first();
        
        if (!$hotel) return view('public.about', ['hotel' => null, 'page' => null]);

        $page = \App\Models\HotelPage::where('hotel_id', $hotel->id)->where('slug', 'about')->where('is_published', true)->first();
        return view('public.about', compact('hotel', 'page'));
    }

    public function contact()
    {
        $hotel = Hotel::where('is_active', true)->first();
        return view('public.contact', compact('hotel'));
    }

    public function gallery()
    {
        $hotel = Hotel::where('is_active', true)->first();
        
        if (!$hotel) return view('public.gallery', ['hotel' => null, 'photos' => collect()]);

        $photos = \App\Models\HotelGallery::where('hotel_id', $hotel->id)->where('is_active', true)->orderBy('sort_order')->get();
        return view('public.gallery', compact('hotel', 'photos'));
    }

    public function services()
    {
        $hotel = Hotel::where('is_active', true)->first();
        
        if (!$hotel) return view('public.services', ['hotel' => null, 'services' => collect()]);

        $services = \App\Models\HotelService::where('hotel_id', $hotel->id)->where('is_active', true)->orderBy('sort_order')->get();
        return view('public.services', compact('hotel', 'services'));
    }

    public function setBranch(Hotel $hotel)
    {
        session(['public_branch_id' => $hotel->id]);
        session(['active_hotel_id' => $hotel->id]);
        return redirect()->back();
    }
}
