<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Transaction;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicBookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function availableRooms(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $rooms = $this->bookingService->getAvailableRooms(
            $request->check_in,
            $request->check_out,
            $request->room_type_id
        );

        return response()->json(
            $rooms->map(fn($r) => [
                'id' => $r->id,
                'room_number' => $r->room_number,
                'room_name' => ($r->roomType->name ?? 'Room') . ' ' . $r->room_number,
                'room_type_name' => $r->roomType->name ?? '',
                'status' => $r->status,
                'price_public' => (int) ($r->price_public ?? 0),
            ])
        );
    }

    public function showForm(Request $request)
    {
        $roomType = null;
        if ($request->has('room_type_id')) {
            $roomType = RoomType::find($request->room_type_id);
        }

        // With dates picked, decide "available" per date-range overlap (a room
        // that's merely In-House/Occupied right now can still be free for the
        // selected dates) instead of the raw current room status snapshot,
        // which hid whole room types (e.g. a single-room type currently
        // occupied) even when they were free for the dates being browsed.
        $checkIn = $request->get('check_in');
        $checkOut = $request->get('check_out');
        $hasDateRange = $checkIn && $checkOut && $checkOut > $checkIn;

        $roomTypes = \App\Models\RoomType::with('rooms')
            ->where('is_active', true)
            ->get()
            ->map(function($rt) use ($checkIn, $checkOut, $hasDateRange) {
                if ($hasDateRange) {
                    $available = $this->bookingService->getAvailableRooms($checkIn, $checkOut, $rt->id);
                } else {
                    $available = $rt->rooms->whereIn('status', ['Available', 'available', 'Clean', 'clean']);
                }
                $rt->min_price = $available->min('price_public') ?? $rt->base_price;
                $rt->available_count = $available->count();
                return $rt;
            })
            ->filter(fn($rt) => $rt->available_count > 0)
            ->values();

        // Breakfast prices by room type (from any room in that type)
        $hasBreakfast = false;
        $breakfastPrices = \App\Models\Room::whereIn('room_type_id', $roomTypes->pluck('id'))
            ->get()
            ->groupBy('room_type_id')
            ->map(function($rooms) use (&$hasBreakfast) {
                $public = (int) ($rooms->max('price_breakfast_public') ?? 0);
                if ($public > 0) $hasBreakfast = true;
                return [
                    'public' => $public,
                    'sales' => (int) ($rooms->max('price_breakfast_sales') ?? 0),
                    'high_season' => (int) ($rooms->max('price_breakfast_high_season') ?? 0),
                ];
            });

        return view('public.booking.form', compact('roomType', 'roomTypes', 'breakfastPrices', 'hasBreakfast'));
    }

    public function store(Request $request)
    {
        $rules = [
            'room_type_id' => 'required|exists:room_types,id',
            'room_id' => 'nullable|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'breakfast' => 'nullable|boolean',
            'voucher_code' => 'nullable|string|max:50',
        ];

        // Only require guest info if not logged in
        if (!Auth::guard('guest')->check()) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'nullable|string|max:20';
            $rules['guest_password'] = 'nullable|string|min:8';
        }

        $validated = $request->validate($rules);

        // Find available room — prefer selected room_id, otherwise cheapest
        if ($request->filled('room_id')) {
            $room = Room::find($request->room_id);
            if (!$room || !in_array($room->status, ['Available', 'available'])) {
                return back()->withErrors(['room_id' => 'Selected room is no longer available.'])->withInput();
            }
            // Also check it's not booked for these dates
            $isBooked = Booking::where('room_id', $room->id)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->where('check_in', '<', $validated['check_out'])
                ->where('check_out', '>', $validated['check_in'])
                ->exists();
            if ($isBooked) {
                return back()->withErrors(['room_id' => 'Selected room is already booked for these dates.'])->withInput();
            }
        } else {
            $availableRooms = $this->bookingService->getAvailableRooms(
                $validated['check_in'],
                $validated['check_out'],
                $validated['room_type_id']
            );

            if ($availableRooms->isEmpty()) {
                return back()->withErrors(['room_type_id' => 'No rooms available for the selected dates.'])->withInput();
            }

            $room = $availableRooms->first();
        }

        // Get or create guest
        $guest = Auth::guard('guest')->user();
        if (!$guest) {
            $guest = Guest::where('email', $validated['guest_email'])->first();
            if (!$guest) {
                $guest = Guest::create([
                    'name' => $validated['guest_name'],
                    'email' => $validated['guest_email'],
                    'phone' => $validated['guest_phone'] ?? null,
                    'password' => $validated['guest_password'] ? Hash::make($validated['guest_password']) : null,
                ]);
            }
            Auth::guard('guest')->login($guest);
        }

        // Create booking via service (handles breakfast + voucher)
        $systemUserId = Auth::guard('web')->id() ?? \App\Models\User::first()->id;

        $bookingData = [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'adults' => $validated['adults'],
            'children' => $validated['children'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'voucher_code' => $validated['voucher_code'] ?? null,
            'include_breakfast' => $validated['breakfast'] ?? false,
            'stay_type' => 'daily',
            'source' => 'online',
            'user_id' => Auth::guard('web')->id() ?? \App\Models\User::first()->id,
        ];

        $booking = $this->bookingService->createBooking($bookingData);

        return redirect()->route('public.payment.pay', $booking->id);
    }

    public function showPayment(Booking $booking)
    {
        if ($booking->status !== 'pending' || $booking->source !== 'online') {
            abort(404);
        }

        $hotel = \App\Models\Hotel::find(active_hotel_id());
        $midtransClientKey = config('services.midtrans.client_key');
        $midtransServerKey = config('services.midtrans.server_key');
        $isProduction = config('services.midtrans.is_production', false);

        // Generate Midtrans Snap Token
        \Midtrans\Config::$serverKey = $midtransServerKey;
        \Midtrans\Config::$isProduction = $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $orderId = 'BOOK-' . $booking->id . '-' . time();
        $booking->update(['payment_order_id' => $orderId]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->guest->name,
                'email' => $booking->guest->email,
                'phone' => $booking->guest->phone ?? '',
            ],
            'callbacks' => [
                'finish' => route('public.payment.success', $booking->id),
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return view('public.payment.pay', compact('booking', 'snapToken', 'midtransClientKey'));
    }

    public function paymentCallback(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderId = $request->order_id;
        $bookingId = explode('-', $orderId)[1] ?? null;

        if (!$bookingId) {
            return response()->json(['message' => 'Invalid order ID'], 400);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($request->transaction_status === 'settlement') {
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            Transaction::create([
                'hotel_id' => $booking->hotel_id,
                'booking_id' => $booking->id,
                'guest_id' => $booking->guest_id,
                'user_id' => null,
                'type' => 'payment',
                'amount' => $request->gross_amount,
                'payment_method' => $request->payment_type,
                'reference_id' => $request->transaction_id,
                'description' => 'Midtrans payment - ' . $request->payment_type,
                'status' => 'success',
            ]);
        } elseif (in_array($request->transaction_status, ['expire', 'cancel'])) {
            $booking->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'success']);
    }

    public function paymentSuccess(Booking $booking)
    {
        // Auto-confirm if still pending (webhook might not reach localhost in dev)
        if ($booking->status === 'pending' || $booking->payment_status !== 'paid') {
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        }

        // Always ensure payment transaction exists (webhook might have confirmed but failed to create record)
        $existingPayment = Transaction::where('booking_id', $booking->id)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->first();

        if (!$existingPayment) {
            Transaction::create([
                'hotel_id' => $booking->hotel_id,
                'booking_id' => $booking->id,
                'guest_id' => $booking->guest_id,
                'user_id' => $booking->user_id,
                'type' => 'payment',
                'amount' => $booking->total_price,
                'payment_method' => 'midtrans',
                'reference_id' => $booking->payment_order_id ?? 'ONLINE-PAY-' . $booking->id,
                'description' => 'Online payment via Midtrans',
                'status' => 'success',
            ]);

            // Also update the initial charge transaction to success
            Transaction::where('booking_id', $booking->id)
                ->where('type', 'charge')
                ->where('status', 'pending')
                ->update(['status' => 'success']);
        }

        // Refresh booking model with latest data + transactions
        $booking->refresh();
        $booking->load('transactions');

        return view('public.payment.success', compact('booking'));
    }
}
