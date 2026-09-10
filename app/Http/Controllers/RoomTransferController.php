<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\RoomTransferService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomTransferController extends Controller
{
    use \App\Traits\AjaxResponse;
    protected RoomTransferService $transferService;

    public function __construct(RoomTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Preview transfer pricing (AJAX).
     */
    public function preview(Request $request, Booking $booking)
    {
        $request->validate([
            "room_id" => "required|exists:rooms,id",
            "tier" => "required|in:public,sales,high_season",
            "new_check_out" => "nullable|date",
        ]);

        $newRoom = Room::findOrFail($request->room_id);
        $newCheckOut = $request->filled('new_check_out') ? Carbon::parse($request->new_check_out) : null;

        try {
            $preview = $this->transferService->previewTransfer(
                $booking,
                $newRoom,
                $request->tier,
                $newCheckOut,
            );
            return response()->json(["success" => true, "data" => $preview]);
        } catch (\Exception $e) {
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                422,
            );
        }
    }

    /**
     * Execute room transfer.
     */
    public function store(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            "room_id" => "required|exists:rooms,id",
            "tier" => "required|in:public,sales,high_season",
            "reason" => "required|string|max:255",
            "notes" => "nullable|string|max:1000",
            "charge_difference" => "nullable|in:0,1",
            "new_check_out" => "nullable|date|after:today",
        ]);

        $newRoom = Room::findOrFail($validated["room_id"]);
        $chargeDifference = ($validated["charge_difference"] ?? "0") === "1";
        $newCheckOut = isset($validated["new_check_out"])
            ? Carbon::parse($validated["new_check_out"])
            : null;

        try {
            $this->transferService->transfer(
                $booking,
                $newRoom,
                $validated["tier"],
                $validated["reason"],
                $validated["notes"] ?? null,
                $chargeDifference,
                $newCheckOut,
            );

            return $this->ajaxOrRedirect(
                "Pindah kamar berhasil! Tamu dipindahkan ke kamar " .
                    $newRoom->room_number,
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }
}
