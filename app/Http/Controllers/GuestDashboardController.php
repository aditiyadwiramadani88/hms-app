<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestDashboardController extends Controller
{
    public function index()
    {
        $guest = Auth::guard('guest')->user();
        $bookings = $guest->bookings()->with('room.roomType')->latest()->paginate(10);
        return view('public.guest.dashboard', compact('guest', 'bookings'));
    }

    public function showBooking($id)
    {
        $guest = Auth::guard('guest')->user();
        $booking = Booking::where('guest_id', $guest->id)->with(['room.roomType', 'transactions'])->findOrFail($id);
        return view('public.guest.booking-show', compact('booking'));
    }
}
