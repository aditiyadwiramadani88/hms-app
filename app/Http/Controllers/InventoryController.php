<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryCategory;
use App\Models\InventoryMutation;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $query = Inventory::with(['inventoryCategory:id,name', 'warehouseStocks.warehouse:id,name,type'])
            ->select('id', 'name', 'category_id', 'stock', 'min_stock', 'unit', 'price_per_unit', 'purchase_price', 'is_active');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $inventories = $query->latest()->paginate(15);
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::where('hotel_id', active_hotel_id())->get();

        $totals = (object) [
            'stock' => Inventory::where('hotel_id', active_hotel_id())->sum('stock'),
            'min_stock' => Inventory::where('hotel_id', active_hotel_id())->sum('min_stock'),
            'count' => Inventory::where('hotel_id', active_hotel_id())->count(),
        ];

        return view('pos.inventory.index', compact('inventories', 'categories', 'warehouses', 'totals'));
    }

    public function create()
    {
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        return view('pos.inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:inventory_categories,id',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'unit' => 'required|string|max:50',
                'price_per_unit' => 'required|numeric|min:0',
                'is_active' => 'nullable',
                'is_refundable' => 'nullable',
                'deposit_amount' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
                $validated['is_active'] = $request->has('is_active');
                $validated['is_refundable'] = $request->has('is_refundable');
                $validated['deposit_amount'] = $validated['deposit_amount'] ?: 0;
                
                // Add hotel_id explicitly just in case, although trait should handle it
                $validated['hotel_id'] = active_hotel_id();

                // Handle image upload
                if ($request->hasFile('image')) {
                    $validated['image'] = $request->file('image')->store('inventory', 'public');
                } else {
                    unset($validated['image']);
                }

                $inventory = Inventory::create($validated);

                if ($validated['stock'] > 0) {
                    InventoryMutation::create([
                        'hotel_id' => active_hotel_id(),
                        'inventory_id' => $inventory->id,
                        'user_id' => Auth::id(),
                        'type' => 'in',
                        'quantity' => $validated['stock'],
                        'notes' => 'Initial stock',
                    ]);

                    $defaultWarehouse = \App\Models\Warehouse::where('hotel_id', active_hotel_id())->first();
                    if ($defaultWarehouse) {
                        \App\Models\InventoryWarehouseStock::create([
                            'inventory_id' => $inventory->id,
                            'warehouse_id' => $defaultWarehouse->id,
                            'stock' => $validated['stock'],
                        ]);
                    }
                }

                return $this->ajaxOrRedirect('Inventory created successfully.', route('inventory.index'), $inventory, 201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Validation failed', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to create inventory: ' . $e->getMessage());
            return back()->with('error', 'Failed to create inventory: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Inventory $inventory)
    {
        $mutations = InventoryMutation::where('inventory_id', $inventory->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('pos.inventory.show', compact('inventory', 'mutations'));
    }

    public function edit(Inventory $inventory)
    {
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        return view('pos.inventory.edit', compact('inventory', 'categories'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:inventory_categories,id',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'unit' => 'required|string|max:50',
                'price_per_unit' => 'required|numeric|min:0',
                'purchase_price' => 'nullable|numeric|min:0',
                'is_active' => 'nullable',
                'is_refundable' => 'nullable',
                'deposit_amount' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $oldStock = $inventory->stock;
            $newStock = (int)$validated['stock'];

            $validated['is_active'] = $request->has('is_active');
            $validated['is_refundable'] = $request->has('is_refundable');
            $validated['deposit_amount'] = $validated['deposit_amount'] ?: 0;
            $validated['purchase_price'] = $validated['purchase_price'] ?? 0;

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($inventory->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($inventory->image)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($inventory->image);
                }
                $validated['image'] = $request->file('image')->store('inventory', 'public');
            } else {
                unset($validated['image']);
            }
            
            $inventory->update($validated);

            if ($newStock != $oldStock) {
                $diff = $newStock - $oldStock;
                InventoryMutation::create([
                    'hotel_id' => $inventory->hotel_id,
                    'inventory_id' => $inventory->id,
                    'user_id' => Auth::id(),
                    'type' => $diff > 0 ? 'in' : 'out',
                    'quantity' => abs($diff),
                    'notes' => 'Stock adjustment via edit',
                ]);

                $defaultWarehouse = \App\Models\Warehouse::where('hotel_id', $inventory->hotel_id)->first();
                if ($defaultWarehouse) {
                    $stockObj = \App\Models\InventoryWarehouseStock::firstOrCreate([
                        'inventory_id' => $inventory->id,
                        'warehouse_id' => $defaultWarehouse->id,
                    ], ['stock' => 0]);
                    
                    $stockObj->increment('stock', $diff);
                }
            }

            return $this->ajaxOrRedirect('Inventory updated successfully.', route('inventory.index'), $inventory);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Validation failed', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Error: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Inventory $inventory)
    {
        try {
            $inventory->delete();
            return $this->ajaxOrRedirect('Inventory deleted successfully.', route('inventory.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function mutation(Request $request, Inventory $inventory)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:in,out,adjustment',
                'quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:500',
            ]);

            return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $inventory) {
                $oldStock = $inventory->stock;

                if ($validated['type'] === 'in') {
                    $inventory->stock += $validated['quantity'];
                } elseif ($validated['type'] === 'out') {
                    if ($inventory->stock < $validated['quantity']) {
                        $errorMessage = 'Insufficient stock.';
                        if ($this->isAjaxRequest()) {
                            return $this->ajaxError($errorMessage);
                        }
                        return back()->with('error', $errorMessage);
                    }
                    $inventory->stock -= $validated['quantity'];
                } else {
                    $inventory->stock = $validated['quantity'];
                }

                $inventory->save();

                $mutation = InventoryMutation::create([
                    'hotel_id' => active_hotel_id(),
                    'inventory_id' => $inventory->id,
                    'user_id' => Auth::id(),
                    'type' => $validated['type'],
                    'quantity' => $validated['type'] === 'adjustment' ? $validated['quantity'] : abs($inventory->stock - $oldStock),
                    'notes' => $validated['notes'],
                ]);

                $defaultWarehouse = \App\Models\Warehouse::where('hotel_id', active_hotel_id())->first();
                if ($defaultWarehouse) {
                    $stockObj = \App\Models\InventoryWarehouseStock::firstOrCreate([
                        'inventory_id' => $inventory->id,
                        'warehouse_id' => $defaultWarehouse->id,
                    ], ['stock' => 0]);
                    
                    if ($validated['type'] === 'in') {
                        $stockObj->increment('stock', $validated['quantity']);
                    } elseif ($validated['type'] === 'out') {
                        $stockObj->decrement('stock', $validated['quantity']);
                    } else {
                        $diff = $inventory->stock - $oldStock;
                        $stockObj->increment('stock', $diff);
                    }
                }

                return $this->ajaxOrRedirect('Mutation recorded successfully.', route('inventory.show', $inventory), $mutation);
            });
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Failed to record mutation: ' . $e->getMessage());
            }
            return back()->with('error', 'Failed to record mutation: ' . $e->getMessage());
        }
    }

    // Category CRUD
    public function categoriesIndex(Request $request)
    {
        $query = InventoryCategory::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $categories = $query->latest()->paginate(15);

        return view('pos.inventory.categories', compact('categories'));
    }

    public function categoriesStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $category = InventoryCategory::create([
                'name' => $validated['name'],
                'hotel_id' => active_hotel_id(),
                'is_active' => true
            ]);

            return $this->ajaxOrRedirect('Category created successfully.', route('inventory.categories.index'), $category, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to create category: ' . $e->getMessage());
            return back()->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    public function categoriesUpdate(Request $request, InventoryCategory $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:inventory_categories,name,'.$category->id,
            ]);

            $category->update($validated);

            return $this->ajaxOrRedirect('Category updated.', route('inventory.categories.index'), $category);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to update category: ' . $e->getMessage());
            return back()->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    public function categoriesDestroy(InventoryCategory $category)
    {
        try {
            $category->delete();
            return $this->ajaxOrRedirect('Category deleted.', route('inventory.categories.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
