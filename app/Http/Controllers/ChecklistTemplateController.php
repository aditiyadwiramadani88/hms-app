<?php

namespace App\Http\Controllers;

use App\Models\CleaningChecklistTemplate;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChecklistTemplateController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $hotelId = active_hotel_id();
        $templates = CleaningChecklistTemplate::where('hotel_id', $hotelId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        return view('housekeeping.checklist-templates', compact('templates'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                if ($this->isAjaxRequest()) return $this->ajaxError('Validation failed', $validator->errors());
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $hotelId = active_hotel_id();
            $maxOrder = CleaningChecklistTemplate::where('hotel_id', $hotelId)->max('sort_order') ?? 0;

            $template = CleaningChecklistTemplate::create([
                'hotel_id' => $hotelId,
                'name' => $request->name,
                'sort_order' => $maxOrder + 1,
                'is_active' => true,
            ]);

            return $this->ajaxOrRedirect('Checklist template created successfully.', route('housekeeping.checklist-templates'), $template, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, CleaningChecklistTemplate $template)
    {
        try {
            if ((int) $template->hotel_id !== (int) active_hotel_id()) {
                abort(403, 'Access denied.');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sort_order' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                if ($this->isAjaxRequest()) return $this->ajaxError('Validation failed', $validator->errors());
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $template->update([
                'name' => $request->name,
                'sort_order' => $request->sort_order,
            ]);

            return $this->ajaxOrRedirect('Checklist template updated successfully.', route('housekeeping.checklist-templates'), $template);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(CleaningChecklistTemplate $template)
    {
        try {
            if ((int) $template->hotel_id !== (int) active_hotel_id()) {
                abort(403, 'Access denied.');
            }

            $template->delete();

            return $this->ajaxOrRedirect('Checklist item removed.', route('housekeeping.checklist-templates'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
}
