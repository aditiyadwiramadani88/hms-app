<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\VehicleRental;
use App\Models\Inventory;
use App\Models\Transaction;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleRentalController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'renter_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'nik' => 'nullable|string|max:50',
            'sim_number' => 'nullable|string|max:50',
            'vehicle_plate_number' => 'required|string|max:50',
            'start_km' => 'nullable|integer',
            'rental_days' => 'required|integer|min:1',
        ]);

        $inventory = Inventory::findOrFail($request->inventory_id);

        try {
            DB::beginTransaction();

            $dailyPrice = $inventory->price_per_unit;
            $depositAmount = $inventory->deposit_amount > 0 ? $inventory->deposit_amount : 0;
            $totalPrice = $dailyPrice * $request->rental_days;

            // Create Vehicle Rental Record
            $vehicleRental = VehicleRental::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $booking->id,
                'renter_name' => $request->renter_name,
                'company_name' => $request->company_name,
                'nik' => $request->nik,
                'sim_number' => $request->sim_number,
                'vehicle_plate_number' => $request->vehicle_plate_number,
                'start_km' => $request->start_km,
                'rental_days' => $request->rental_days,
                'daily_price' => $dailyPrice,
                'deposit_amount' => $depositAmount,
                'status' => 'rented'
            ]);

            // Add POS Order for the rental charge
            $posOrder = PosOrder::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $booking->id,
                'cashier_id' => auth()->id(),
                'status' => 'completed',
                'payment_status' => 'unpaid',
                'subtotal' => $totalPrice,
                'discount' => 0,
                'tax' => 0,
                'total_amount' => $totalPrice,
                'order_date' => now()
            ]);

            PosOrderItem::create([
                'pos_order_id' => $posOrder->id,
                'inventory_id' => $inventory->id,
                'quantity' => $request->rental_days,
                'unit_price' => $dailyPrice,
                'subtotal' => $totalPrice
            ]);

            // Reduce stock
            if ($inventory->stock !== null) {
                $inventory->decrement('stock', 1);
            }

            // Update Booking Deposit if necessary
            if ($depositAmount > 0) {
                $booking->increment('deposit_amount', $depositAmount);
                // Deposit is not automatically added as transaction unless paid, 
                // but in this system it's added to total deposit expected.
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle Rental added successfully',
                'data' => $vehicleRental
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function print(VehicleRental $vehicleRental)
    {
        // Must belong to current hotel
        if ($vehicleRental->hotel_id !== active_hotel_id()) {
            abort(403);
        }
        
        $vehicleRental->load('booking.guest');
        
        return view('vehicle_rentals.print', compact('vehicleRental'));
    }
}
