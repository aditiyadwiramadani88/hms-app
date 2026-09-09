<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected PaymentService $paymentService;

    public function __construct(BookingService $bookingService, PaymentService $paymentService)
    {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Get the authenticated user's bookings.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['room.roomType', 'guest', 'transactions'])
            ->where('user_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $bookings = $query->latest()->paginate($request->query('per_page', 15));

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    /**
     * Create a new booking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:255'],
            'voucher_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
        ]);

        // Find or create guest
        $guest = null;
        if (!empty($validated['guest_email'])) {
            $guest = Guest::where('email', $validated['guest_email'])->first();
        }

        if (!$guest) {
            $guest = Guest::create([
                'name' => $validated['guest_name'],
                'email' => $validated['guest_email'] ?? null,
                'phone' => $validated['guest_phone'] ?? null,
            ]);
        }

        try {
            $bookingData = [
                'guest_id' => $guest->id,
                'room_id' => $validated['room_id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $validated['adults'],
                'children' => $validated['children'] ?? 0,
            ];

            if (isset($validated['voucher_code'])) {
                $bookingData['voucher_code'] = $validated['voucher_code'];
            }
            if (isset($validated['notes'])) {
                $bookingData['notes'] = $validated['notes'];
            }
            if (isset($validated['source'])) {
                $bookingData['source'] = $validated['source'];
            }

            $booking = $this->bookingService->createBooking($bookingData);

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking->load(['room.roomType', 'guest']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show a specific booking.
     */
    public function show($id)
    {
        $booking = Booking::with(['room.roomType', 'guest', 'user', 'transactions'])
            ->where('user_id', Auth::id())
            ->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'booking' => $booking,
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel($id)
    {
        $booking = Booking::where('user_id', Auth::id())->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        try {
            $booking = $this->bookingService->cancelBooking($booking);

            return response()->json([
                'message' => 'Booking cancelled successfully',
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Initiate Midtrans payment for a booking.
     */
    public function initiatePayment(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        try {
            $snapToken = $this->paymentService->createMidtransTransaction($booking);

            return response()->json([
                'message' => 'Payment initiated successfully',
                'snap_token' => $snapToken,
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
