<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\RoomType;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vouchers = Voucher::latest()->paginate(10);
        return view('vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roomTypes = RoomType::all();
        return view('vouchers.create', compact('roomTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|unique:vouchers,code|max:20',
                'name' => 'required|string|max:100',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'min_booking_amount' => 'nullable|numeric|min:0',
                'max_discount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'valid_from' => 'nullable|date',
                'valid_until' => 'required|date|after_or_equal:valid_from',
                'applicable_room_types' => 'nullable|array',
            ]);

            $data = $request->all();
            $data['is_active'] = $request->has('is_active');
            $data['code'] = strtoupper($data['code']);

            $voucher = Voucher::create($data);

            return $this->ajaxOrRedirect('Voucher created successfully.', route('vouchers.index'), $voucher, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Display the specified resource with usage history.
     */
    public function show(Voucher $voucher)
    {
        $usageHistory = \App\Models\Booking::where('voucher_code', $voucher->code)
            ->with(['guest', 'room'])
            ->latest('created_at')
            ->get();

        return view('vouchers.show', compact('voucher', 'usageHistory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        $roomTypes = RoomType::all();
        return view('vouchers.edit', compact('voucher', 'roomTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:20|unique:vouchers,code,' . $voucher->id,
                'name' => 'required|string|max:100',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'min_booking_amount' => 'nullable|numeric|min:0',
                'max_discount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'valid_from' => 'nullable|date',
                'valid_until' => 'required|date|after_or_equal:valid_from',
                'applicable_room_types' => 'nullable|array',
            ]);

            $data = $request->all();
            $data['is_active'] = $request->has('is_active');
            $data['code'] = strtoupper($data['code']);

            $voucher->update($data);

            return $this->ajaxOrRedirect('Voucher updated successfully.', route('vouchers.index'), $voucher);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Check voucher validity via AJAX.
     */
    public function check(Request $request)
    {
        $code = strtoupper($request->query('code'));
        $subtotal = $request->query('subtotal', 0);
        $roomId = $request->query('room_id');
        $roomTypeId = null;

        if ($roomId) {
            $room = \App\Models\Room::find($roomId);
            $roomTypeId = $room ? $room->room_type_id : null;
        }

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher code not found.']);
        }

        if (!$voucher->isValid()) {
            return response()->json(['success' => false, 'message' => 'Voucher is inactive or has expired.']);
        }

        if ($subtotal < $voucher->min_booking_amount) {
            return response()->json([
                'success' => false, 
                'message' => 'Minimum booking amount for this voucher is Rp ' . number_format($voucher->min_booking_amount, 0, ',', '.')
            ]);
        }

        // Check if applies to specific room type
        if (!empty($voucher->applicable_room_types)) {
            if (!$roomTypeId || !in_array($roomTypeId, $voucher->applicable_room_types)) {
                return response()->json(['success' => false, 'message' => 'This voucher is not applicable for the selected room type.']);
            }
        }

        // Calculate discount
        $discount = 0;
        if ($voucher->type === 'percentage') {
            $discount = ($subtotal * $voucher->value) / 100;
            if ($voucher->max_discount > 0 && $discount > $voucher->max_discount) {
                $discount = $voucher->max_discount;
            }
        } else {
            $discount = $voucher->value;
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher applied! Discount: Rp ' . number_format($discount, 0, ',', '.'),
            'discount_amount' => $discount,
            'voucher_name' => $voucher->name
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        try {
            $voucher->delete();
            return $this->ajaxOrRedirect('Voucher deleted successfully.', route('vouchers.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }
}
