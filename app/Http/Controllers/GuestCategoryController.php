<?php

namespace App\Http\Controllers;

use App\Models\GuestCategory;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class GuestCategoryController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $guestCategories = GuestCategory::latest()->paginate(10);
        return view('guest-categories.index', compact('guestCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('guest-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:guest_categories,code|max:255',
            'description' => 'nullable|string',
            'breakfast_price' => 'required|numeric|min:0',
        ]);

        try {
            $guestCategory = GuestCategory::create($validated);

            return $this->ajaxOrRedirect('Guest Category created successfully.', route('guest-categories.index'), $guestCategory, 201);
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
    public function show(GuestCategory $guestCategory)
    {
        return view('guest-categories.show', compact('guestCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GuestCategory $guestCategory)
    {
        return view('guest-categories.edit', compact('guestCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GuestCategory $guestCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:guest_categories,code,' . $guestCategory->id,
            'description' => 'nullable|string',
            'breakfast_price' => 'required|numeric|min:0',
        ]);

        try {
            $guestCategory->update($validated);

            return $this->ajaxOrRedirect('Guest Category updated successfully.', route('guest-categories.index'), $guestCategory);
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
    public function destroy(GuestCategory $guestCategory)
    {
        try {
            $guestCategory->delete();

            return $this->ajaxOrRedirect('Guest Category deleted successfully.', route('guest-categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
