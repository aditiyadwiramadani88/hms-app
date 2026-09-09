<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $leaveTypes = LeaveType::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.leaves.types', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_paid' => 'nullable',
            'max_days_per_year' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_paid'] = $request->boolean('is_paid');
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['sort_order'] = $request->input('sort_order', 0);
            $validated['hotel_id'] = active_hotel_id();

            LeaveType::create($validated);

            return $this->ajaxOrRedirect('Jenis cuti berhasil ditambahkan.', route('admin.leave-types.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_paid' => 'nullable',
            'max_days_per_year' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_paid'] = $request->boolean('is_paid');
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['sort_order'] = $request->input('sort_order', 0);

            $leaveType->update($validated);

            return $this->ajaxOrRedirect('Jenis cuti berhasil diupdate.', route('admin.leave-types.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(LeaveType $leaveType)
    {
        try {
            if ($leaveType->leaveRequests()->exists()) {
                return $this->ajaxError('Jenis cuti tidak bisa dihapus karena sudah digunakan dalam pengajuan cuti.');
            }

            $leaveType->delete();
            return $this->ajaxOrRedirect('Jenis cuti berhasil dihapus.', route('admin.leave-types.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
