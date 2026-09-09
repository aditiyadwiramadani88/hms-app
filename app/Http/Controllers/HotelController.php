<?php

namespace App\Http\Controllers;

use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use App\Models\Hotel;

class HotelController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $hotels = Hotel::withCount('users')->latest()->paginate(10);
        return view('admin.hotels.index', compact('hotels'));
    }

    public function create()
    {
        return view('admin.hotels.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:hotels,code',
                'name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'is_active' => 'boolean'
            ]);

            $hotel = Hotel::create($validated);

            return $this->ajaxOrRedirect('Branch created successfully.', route('hotels.index'), $hotel);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function edit(Hotel $hotel)
    {
        return view('admin.hotels.edit', compact('hotel'));
    }

    public function update(Request $request, Hotel $hotel)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:hotels,code,' . $hotel->id,
                'name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'is_active' => 'boolean'
            ]);

            $hotel->update($validated);

            return $this->ajaxOrRedirect('Branch updated successfully.', route('hotels.index'), $hotel);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    public function destroy(Hotel $hotel)
    {
        try {
            if ($hotel->code === 'DEFAULT') {
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError('Cannot delete default branch.');
                }
                return redirect()->back()->with('error', 'Cannot delete default branch.');
            }

            $hotel->delete();

            return $this->ajaxOrRedirect('Branch deleted successfully.', route('hotels.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }
}
