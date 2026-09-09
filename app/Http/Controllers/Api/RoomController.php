<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\BookingService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * List all active room types with their rooms.
     */
    public function index()
    {
        $roomTypes = RoomType::where('is_active', true)
            ->with('rooms')
            ->get();

        return response()->json([
            'room_types' => $roomTypes,
        ]);
    }

    /**
     * Get available rooms filtered by date range and optionally room type.
     */
    public function available(Request $request)
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
        ]);

        $availableRooms = $this->bookingService->getAvailableRooms(
            $validated['check_in'],
            $validated['check_out'],
            $validated['room_type_id'] ?? null
        );

        return response()->json([
            'rooms' => $availableRooms,
        ]);
    }

    /**
     * Show details for a specific room.
     */
    public function show($id)
    {
        $room = Room::with(['roomType', 'bookings'])->find($id);

        if (!$room) {
            return response()->json([
                'message' => 'Room not found',
            ], 404);
        }

        return response()->json([
            'room' => $room,
        ]);
    }
}
