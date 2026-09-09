<?php

namespace App\Http\Controllers;

use App\Models\ScheduleLocation;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class ScheduleLocationController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $locations = ScheduleLocation::where('is_active', true)->get();
        return view('schedule_locations.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $location = ScheduleLocation::create($request->all());

        return $this->ajaxOrRedirect('Location created successfully.', route('schedule-locations.index'), $location, 201);
    }

    public function update(Request $request, ScheduleLocation $scheduleLocation)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $scheduleLocation->update($request->all());

        return $this->ajaxOrRedirect('Location updated successfully.', route('schedule-locations.index'), $scheduleLocation);
    }

    public function destroy(ScheduleLocation $scheduleLocation)
    {
        $scheduleLocation->delete();
        return $this->ajaxOrRedirect('Location deleted successfully.', route('schedule-locations.index'));
    }
}
