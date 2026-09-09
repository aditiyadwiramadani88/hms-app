<?php

namespace App\Http\Controllers;

use App\Models\RoomStatus;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class RoomStatusController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $query = RoomStatus::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        $statuses = $query->orderBy('display_order')->paginate(15);

        return view('room-statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:room_statuses,name,NULL,id,hotel_id,' . active_hotel_id(),
                'color' => 'required|string|max:20',
                'icon' => 'nullable|string|max:50',
                'is_available' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);

            $validated['is_available'] = $request->boolean('is_available', false);
            $validated['display_order'] = $request->display_order ?? 0;
            $status = RoomStatus::create($validated);

            return $this->ajaxOrRedirect('Status created.', back()->getTargetUrl(), $status);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Failed to create status: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, RoomStatus $roomStatus)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:room_statuses,name,'.$roomStatus->id.',id,hotel_id,' . $roomStatus->hotel_id,
                'color' => 'required|string|max:20',
                'icon' => 'nullable|string|max:50',
                'is_available' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);

            $validated['is_available'] = $request->boolean('is_available', false);
            $roomStatus->update($validated);

            return $this->ajaxOrRedirect('Status updated.', back()->getTargetUrl(), $roomStatus);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Failed to update status: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(RoomStatus $roomStatus)
    {
        try {
            $roomStatus->delete();

            return $this->ajaxOrRedirect('Status deleted.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }
}
