<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
    {
        $hotelId = active_hotel_id();

        // Auto-sync: ensure any inventory in this hotel that lacks warehouse stock records
        // is initialized in the display warehouse (or first warehouse)
        $displayWarehouse = Warehouse::where('hotel_id', $hotelId)->where('type', 'display')->first()
            ?? Warehouse::where('hotel_id', $hotelId)->first();

        if ($displayWarehouse) {
            $existingInventoryIdsWithStock = \App\Models\InventoryWarehouseStock::whereHas('warehouse', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })->pluck('inventory_id')->unique();

            $missingInventories = \App\Models\Inventory::where('hotel_id', $hotelId)
                ->whereNotIn('id', $existingInventoryIdsWithStock)
                ->get();

            foreach ($missingInventories as $missingInv) {
                \App\Models\InventoryWarehouseStock::firstOrCreate([
                    'inventory_id' => $missingInv->id,
                    'warehouse_id' => $displayWarehouse->id,
                ], [
                    'stock' => max(0, (int)$missingInv->stock),
                ]);
            }
        }

        $warehouses = Warehouse::where('hotel_id', $hotelId)
            ->with([
                'stocks' => function ($q) {
                    $q->with('inventory')->whereHas('inventory');
                }
            ])
            ->get();

        return view('pos.warehouse.index', compact('warehouses'));
    }

    public function show(Warehouse $warehouse)
    {
        if ($warehouse->hotel_id !== active_hotel_id()) {
            abort(403);
        }

        $warehouse->load([
            'stocks' => function ($q) {
                $q->with('inventory')->whereHas('inventory');
            }
        ]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'warehouse' => $warehouse,
                'stocks' => $warehouse->stocks,
            ]);
        }

        return redirect()->route('warehouse.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:storage,display',
        ]);

        Warehouse::create([
            'hotel_id' => active_hotel_id(),
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Gudang berhasil ditambahkan');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:storage,display',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($request->only('name', 'type', 'is_active'));

        return redirect()->back()->with('success', 'Gudang berhasil diupdate');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->stocks()->where('stock', '>', 0)->exists()) {
            return redirect()->back()->with('error', 'Gudang tidak dapat dihapus karena masih memiliki stok');
        }

        $warehouse->delete();

        return redirect()->back()->with('success', 'Gudang berhasil dihapus');
    }
}
