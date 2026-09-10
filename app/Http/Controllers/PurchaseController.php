<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Models\InventoryMutation;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest()->paginate(10);
        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::where('hotel_id', active_hotel_id())->where('is_active', true)->orderBy('name')->get();
        return view('purchases.create', compact('suppliers', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'required|in:draft,received',
            'warehouse_id' => 'required_if:status,received|nullable|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $purchase = Purchase::create([
                'hotel_id' => active_hotel_id() ?: 1,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => Auth::id(),
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'],
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'total_amount' => $totalAmount,
                'purchase_number' => 'PO-' . date('Ymd') . '-' . Str::upper(Str::random(4)),
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'inventory_id' => $itemData['inventory_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'subtotal' => $itemData['quantity'] * $itemData['price'],
                ]);

                if ($purchase->status == 'received') {
                    $inventory = Inventory::find($itemData['inventory_id']);
                    $inventory->stock += $itemData['quantity'];
                    $inventory->purchase_price = $itemData['price'];
                    $inventory->save();

                    $warehouseStock = InventoryWarehouseStock::firstOrCreate(
                        ['inventory_id' => $inventory->id, 'warehouse_id' => $purchase->warehouse_id],
                        ['stock' => 0]
                    );
                    $warehouseStock->increment('stock', $itemData['quantity']);

                    InventoryMutation::create([
                        'inventory_id' => $inventory->id,
                        'user_id' => Auth::id(),
                        'type' => 'in',
                        'quantity' => $itemData['quantity'],
                        'notes' => 'From Purchase Order #' . $purchase->purchase_number . ' -> ' . ($purchase->warehouse->name ?? 'Gudang'),
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                    ]);
                }
            }

            DB::commit();

            return $this->ajaxOrRedirect('Purchase order created successfully.', route('purchases.index'), [
                'purchase' => $purchase,
                'redirect' => route('purchases.index'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to create purchase order. Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create purchase order. Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['items.inventory', 'supplier', 'user']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load(['items.inventory', 'supplier', 'user', 'warehouse']);
        $warehouses = Warehouse::where('hotel_id', $purchase->hotel_id)->where('is_active', true)->orderBy('name')->get();
        return view('purchases.edit', compact('purchase', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'status' => 'required|in:draft,received',
            // Penempatan/gudang wajib diisi saat menerima barang, supaya stok
            // yang masuk juga tercermin di breakdown per-gudang (bukan cuma
            // total stok hotel), konsisten dgn WarehouseTransferController.
            'warehouse_id' => 'required_if:status,received|nullable|exists:warehouses,id',
        ]);

        $oldStatus = $purchase->status;
        $newStatus = $request->status;

        try {
            DB::beginTransaction();

            $purchase->update([
                'status' => $newStatus,
                'warehouse_id' => $newStatus === 'received' ? $request->warehouse_id : $purchase->warehouse_id,
            ]);

            // When changing to 'received', add to inventory + the chosen warehouse's stock
            if ($newStatus === 'received' && $oldStatus !== 'received') {
                $purchase->load('items');
                foreach ($purchase->items as $item) {
                    $inventory = Inventory::find($item->inventory_id);
                    if ($inventory) {
                        $inventory->stock += $item->quantity;
                        $inventory->purchase_price = $item->price;
                        $inventory->save();

                        $warehouseStock = InventoryWarehouseStock::firstOrCreate(
                            ['inventory_id' => $inventory->id, 'warehouse_id' => $purchase->warehouse_id],
                            ['stock' => 0]
                        );
                        $warehouseStock->increment('stock', $item->quantity);

                        InventoryMutation::create([
                            'inventory_id' => $inventory->id,
                            'user_id' => Auth::id(),
                            'type' => 'in',
                            'quantity' => $item->quantity,
                            'notes' => 'Received - PO #' . $purchase->purchase_number . ' -> ' . ($purchase->warehouse->name ?? 'Gudang'),
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                        ]);
                    }
                }
            }

            // When changing away from 'received', remove from inventory + the warehouse it was placed in
            if ($newStatus !== 'received' && $oldStatus === 'received') {
                $purchase->load('items');
                foreach ($purchase->items as $item) {
                    $inventory = Inventory::find($item->inventory_id);
                    if ($inventory) {
                        $inventory->stock = max(0, $inventory->stock - $item->quantity);
                        $inventory->save();

                        if ($purchase->warehouse_id) {
                            $warehouseStock = InventoryWarehouseStock::where('inventory_id', $inventory->id)
                                ->where('warehouse_id', $purchase->warehouse_id)
                                ->first();
                            $warehouseStock?->decrement('stock', min($item->quantity, $warehouseStock->stock));
                        }

                        InventoryMutation::create([
                            'inventory_id' => $inventory->id,
                            'user_id' => Auth::id(),
                            'type' => 'out',
                            'quantity' => $item->quantity,
                            'notes' => 'Reverted - PO #' . $purchase->purchase_number,
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                        ]);
                    }
                }
            }

            DB::commit();

            return $this->ajaxOrRedirect('Purchase order updated successfully.', route('purchases.index'), $purchase);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to update purchase order. Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update purchase order. Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $this->authorize('delete transactions');

        try {
            DB::beginTransaction();

            // Revert stock if purchase was 'received'
            if ($purchase->status === 'received') {
                $purchase->load('items');
                foreach ($purchase->items as $item) {
                    $inventory = Inventory::find($item->inventory_id);
                    if ($inventory) {
                        $revertQty = min($item->quantity, $inventory->stock);
                        $inventory->stock = max(0, $inventory->stock - $item->quantity);
                        $inventory->save();

                        if ($revertQty > 0) {
                            \App\Models\InventoryMutation::create([
                                'hotel_id' => active_hotel_id(),
                                'inventory_id' => $inventory->id,
                                'type' => 'purchase_revert',
                                'quantity' => -$revertQty,
                                'reference' => 'PO Deleted: ' . ($purchase->purchase_number ?? '#' . $purchase->id),
                                'user_id' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();

            return $this->ajaxOrRedirect('Purchase order deleted successfully.', route('purchases.index'));
        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->isAjaxRequest()) return $this->ajaxError('Failed to delete purchase order. Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete purchase order. Error: ' . $e->getMessage());
        }
    }

    /**
     * Search inventory items for purchase order (Select2 AJAX).
     */
    public function searchInventory(Request $request)
    {
        $term = $request->input('term', '');
        $hotelId = active_hotel_id() ?: 1;

        $items = Inventory::where('is_active', true)
            ->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            })
            ->when($term, function ($q, $term) {
                $q->where('name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                $price = (float) ($item->purchase_price > 0 ? $item->purchase_price : ($item->price_per_unit ?? 0));
                return [
                    'id' => $item->id,
                    'text' => $item->name . ' (' . ($item->unit ?? 'Unit') . ')',
                    'price' => $price,
                ];
            });

        return response()->json($items);
    }
}
