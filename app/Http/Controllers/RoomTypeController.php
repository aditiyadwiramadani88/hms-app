<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $query = RoomType::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $roomTypes = $query->latest()->paginate(15);

        return view('room-types.index', compact('roomTypes'));
    }

    public function create()
    {
        return view('room-types.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'base_price' => 'required|numeric|min:0',
                'monthly_price' => 'nullable|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'max_guests' => 'required|integer|min:1',
                'size_sqm' => 'nullable|integer|min:1',
                'amenities' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('room-types', 'public');
            }

            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['amenities'] = $request->amenities ?? [];

            if (active_hotel_id()) {
                $validated['hotel_id'] = active_hotel_id();
            }

            $roomType = RoomType::create($validated);

            return $this->ajaxOrRedirect('Room type created successfully.', route('room-types.index'), $roomType);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', 'Failed to create room type: ' . $e->getMessage());
        }
    }

    public function show(RoomType $roomType)
    {
        $roomType->load('rooms');

        return view('room-types.show', compact('roomType'));
    }

    public function edit(RoomType $roomType)
    {
        return view('room-types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'base_price' => 'required|numeric|min:0',
                'monthly_price' => 'nullable|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'max_guests' => 'required|integer|min:1',
                'size_sqm' => 'nullable|integer|min:1',
                'amenities' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            if ($request->hasFile('image')) {
                if ($roomType->image) {
                    Storage::disk('public')->delete($roomType->image);
                }
                $validated['image'] = $request->file('image')->store('room-types', 'public');
            }

            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['amenities'] = $request->amenities ?? [];

            $roomType->update($validated);

            return $this->ajaxOrRedirect('Room type updated.', route('room-types.index'), $roomType);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function destroy(RoomType $roomType)
    {
        try {
            $roomType->delete();

            return $this->ajaxOrRedirect('Room type deleted.', route('room-types.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }
}
