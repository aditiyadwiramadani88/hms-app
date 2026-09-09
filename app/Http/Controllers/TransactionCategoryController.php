<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionCategoryController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of transaction categories.
     */
    public function index()
    {
        $categories = TransactionCategory::where('hotel_id', active_hotel_id())
            ->select('id', 'name', 'type', 'is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.finance.categories', compact('categories'));
    }

    /**
     * Show a single category (redirects to index).
     */
    public function show(TransactionCategory $transactionCategory)
    {
        return redirect()->route('finance.categories.index');
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $hotelId = active_hotel_id();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_categories')->where(function ($query) use ($hotelId, $request) {
                    return $query->where('hotel_id', $hotelId)
                                 ->where('type', $request->type);
                }),
            ],
            'type' => 'required|in:income,expense',
        ], [
            'name.unique' => 'Kategori dengan nama dan tipe ini sudah ada.'
        ]);

        try {
            $category = TransactionCategory::create([
                'hotel_id' => $hotelId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'is_active' => true,
            ]);

            return $this->ajaxOrRedirect('Finance category created successfully.', back()->getTargetUrl(), $category, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Update a category.
     */
    public function update(Request $request, TransactionCategory $transactionCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_categories')->where(function ($query) use ($transactionCategory) {
                    return $query->where('hotel_id', $transactionCategory->hotel_id)
                                 ->where('type', $transactionCategory->type);
                })->ignore($transactionCategory->id),
            ],
            'is_active' => 'required|boolean',
        ], [
            'name.unique' => 'Kategori dengan nama dan tipe ini sudah ada.'
        ]);

        try {
            $transactionCategory->update($validated);
            return $this->ajaxOrRedirect('Category updated successfully.', back()->getTargetUrl(), $transactionCategory);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a category.
     */
    public function destroy(TransactionCategory $transactionCategory)
    {
        try {
            // Prevent deletion if used in transactions
            if ($transactionCategory->transactions()->exists()) {
                $errorMsg = 'Cannot delete category that is already used in transactions. Deactivate it instead.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMsg);
                }
                return back()->with('error', $errorMsg);
            }

            $transactionCategory->delete();
            return $this->ajaxOrRedirect('Category deleted successfully.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
