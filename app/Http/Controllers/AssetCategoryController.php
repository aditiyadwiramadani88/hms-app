<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $categories = AssetCategory::withCount('assets')->get();
        return view('assets.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('asset_categories')->where('hotel_id', active_hotel_id())
                ],
                'description' => 'nullable|string'
            ]);

            $assetCategory = AssetCategory::create([
                'hotel_id' => active_hotel_id(),
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return $this->ajaxOrRedirect('Asset category created successfully.', route('asset-categories.index'), $assetCategory, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        try {
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('asset_categories')->where('hotel_id', active_hotel_id())->ignore($assetCategory->id)
                ],
                'description' => 'nullable|string'
            ]);

            $assetCategory->update($request->only('name', 'description'));

            return $this->ajaxOrRedirect('Asset category updated successfully.', route('asset-categories.index'), $assetCategory);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function destroy(AssetCategory $assetCategory)
    {
        try {
            if ($assetCategory->assets()->count() > 0) {
                $msg = 'Cannot delete category that is still being used by assets.';
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return redirect()->route('asset-categories.index')->with('error', $msg);
            }

            $assetCategory->delete();
            return $this->ajaxOrRedirect('Asset category deleted successfully.', route('asset-categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }
}
