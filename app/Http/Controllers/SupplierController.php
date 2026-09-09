<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:suppliers,code|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        try {
            $supplier = Supplier::create($validated);
            return $this->ajaxOrRedirect('Supplier created successfully.', route('suppliers.index'), $supplier, 201);
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
    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:suppliers,code,' . $supplier->id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        try {
            $supplier->update($validated);
            return $this->ajaxOrRedirect('Supplier updated successfully.', route('suppliers.index'), $supplier);
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
    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
            return $this->ajaxOrRedirect('Supplier deleted successfully.', route('suppliers.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
