<?php

namespace App\Http\Controllers;

use App\Models\GuestCategory;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class RoomRateController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RoomRate::with(['roomType', 'guestCategory']);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by Room Type
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter by Guest Category
        if ($request->filled('guest_category_id')) {
            $query->where('guest_category_id', $request->guest_category_id);
        }

        $roomRates = $query->latest()->paginate(10)->withQueryString();
        
        $roomTypes = RoomType::all();
        $guestCategories = GuestCategory::all();

        return view('room-rates.index', compact('roomRates', 'roomTypes', 'guestCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roomTypes = RoomType::all();
        $guestCategories = GuestCategory::all();
        return view('room-rates.create', compact('roomTypes', 'guestCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_type_id' => 'required|exists:room_types,id',
            'guest_category_id' => 'nullable|exists:guest_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
            'is_locked' => 'nullable|boolean',
        ]);

        try {
            $data = $validated;
            $data['is_locked'] = $request->has('is_locked');

            $roomRate = RoomRate::create($data);

            return $this->ajaxOrRedirect('Room Rate created successfully.', route('room-rates.index'), $roomRate, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RoomRate $roomRate)
    {
        return view('room-rates.show', compact('roomRate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoomRate $roomRate)
    {
        $roomTypes = RoomType::all();
        $guestCategories = GuestCategory::all();
        return view('room-rates.edit', compact('roomRate', 'roomTypes', 'guestCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RoomRate $roomRate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_type_id' => 'required|exists:room_types,id',
            'guest_category_id' => 'nullable|exists:guest_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
            'is_locked' => 'nullable|boolean',
        ]);
        
        try {
            $data = $validated;
            $data['is_locked'] = $request->has('is_locked');

            $roomRate->update($data);

            return $this->ajaxOrRedirect('Room Rate updated successfully.', route('room-rates.index'), $roomRate);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomRate $roomRate)
    {
        try {
            $roomRate->delete();
            return $this->ajaxOrRedirect('Room Rate deleted successfully.', route('room-rates.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
