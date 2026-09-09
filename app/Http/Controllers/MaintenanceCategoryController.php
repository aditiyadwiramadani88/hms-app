<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceCategory;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class MaintenanceCategoryController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $categories = MaintenanceCategory::where('hotel_id', active_hotel_id())
            ->orderBy('sort_order')
            ->get();
        return view('maintenance.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        try {
            MaintenanceCategory::create([
                'hotel_id' => active_hotel_id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Kategori maintenance berhasil ditambahkan.', route('maintenance.categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, MaintenanceCategory $maintenanceCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        try {
            $maintenanceCategory->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Kategori maintenance berhasil diupdate.', route('maintenance.categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(MaintenanceCategory $maintenanceCategory)
    {
        try {
            $maintenanceCategory->delete();
            return $this->ajaxOrRedirect('Kategori maintenance berhasil dihapus.', route('maintenance.categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
