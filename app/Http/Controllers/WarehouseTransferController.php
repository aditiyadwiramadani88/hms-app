<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Models\InventoryWarehouseStock;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseTransferController extends Controller
{
    public function index()
    {
        $hotelId = active_hotel_id();

        $transfers = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'items.inventory'])
            ->whereHas('fromWarehouse', function($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->orWhereHas('toWarehouse', function($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->latest()
            ->get();

        $sourceWarehouses = Warehouse::where('hotel_id', $hotelId)->get();
        $allWarehouses = Warehouse::with('hotel')->get();
        $inventories = Inventory::where('hotel_id', $hotelId)->get();

        return view('pos.warehouse.transfer.index', compact(
            'transfers', 'sourceWarehouses', 'allWarehouses', 'inventories'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $transfer = WarehouseTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'status' => 'completed',
                'transfer_date' => now(),
                'created_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            $targetWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
            $isCrossBranch = $targetWarehouse->hotel_id !== active_hotel_id();

            foreach ($request->items as $item) {
                $inventory = Inventory::findOrFail($item['inventory_id']);

                $sourceStock = InventoryWarehouseStock::where('inventory_id', $inventory->id)
                    ->where('warehouse_id', $request->from_warehouse_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sourceStock->stock < $item['quantity']) {
                    throw new \Exception("Stok {$inventory->name} tidak mencukupi di gudang asal.");
                }

                $sourceStock->decrement('stock', $item['quantity']);

                if ($isCrossBranch) {
                    $inventory->decrement('stock', $item['quantity']);
                }

                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $item['quantity']
                ]);

                $targetInventoryId = $inventory->id;

                if ($isCrossBranch) {
                    $targetInventory = Inventory::withoutGlobalScopes()
                        ->where('hotel_id', $targetWarehouse->hotel_id)
                        ->where('name', $inventory->name)
                        ->first();

                    if (!$targetInventory) {
                        $targetInventory = $inventory->replicate();
                        $targetInventory->hotel_id = $targetWarehouse->hotel_id;
                        $targetInventory->stock = 0;
                        $targetInventory->save();
                    }
                    $targetInventoryId = $targetInventory->id;
                }

                $targetStock = InventoryWarehouseStock::firstOrCreate(
                    [
                        'inventory_id' => $targetInventoryId,
                        'warehouse_id' => $targetWarehouse->id
                    ],
                    ['stock' => 0]
                );

                $targetStock->increment('stock', $item['quantity']);

                if ($isCrossBranch) {
                    $targetInventory->increment('stock', $item['quantity']);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Transfer stok berhasil.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
