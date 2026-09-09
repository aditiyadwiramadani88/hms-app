<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $shifts = Shift::active()->get();
        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'color' => 'nullable|string|max:10',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'start_time_2' => 'nullable|date_format:H:i',
            'end_time_2' => 'nullable|date_format:H:i',
            'is_off' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $shift = Shift::create($request->all());

        return $this->ajaxOrRedirect('Shift created successfully.', route('shifts.index'), $shift, 201);
    }

    public function update(Request $request, Shift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'color' => 'nullable|string|max:10',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'start_time_2' => 'nullable|date_format:H:i',
            'end_time_2' => 'nullable|date_format:H:i',
            'is_off' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $shift->update($request->all());

        return $this->ajaxOrRedirect('Shift updated successfully.', route('shifts.index'), $shift);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return $this->ajaxOrRedirect('Shift deleted successfully.', route('shifts.index'));
    }
}
