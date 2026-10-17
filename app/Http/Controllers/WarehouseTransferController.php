<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Models\InventoryWarehouseStock;
use App\Models\Inventory;
use App\Models\InventoryMutation;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseTransferController extends Controller
{
    use AjaxResponse;

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
            $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
            $targetWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
            $isCrossBranch = $targetWarehouse->hotel_id !== active_hotel_id();

            $transfer = WarehouseTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'status' => 'completed',
                'transfer_date' => now(),
                'created_by' => Auth::id() ?? auth()->id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $inventory = Inventory::findOrFail($item['inventory_id']);

                $sourceStock = InventoryWarehouseStock::where('inventory_id', $inventory->id)
                    ->where('warehouse_id', $request->from_warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$sourceStock) {
                    $sourceStock = InventoryWarehouseStock::create([
                        'inventory_id' => $inventory->id,
                        'warehouse_id' => $request->from_warehouse_id,
                        'stock' => max(0, (int) $inventory->stock),
                    ]);
                }

                if ($sourceStock->stock < $item['quantity']) {
                    throw new \Exception("Stok {$inventory->name} tidak mencukupi di {$fromWarehouse->name}. Tersedia: {$sourceStock->stock}");
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

                // Log into InventoryMutation so mutation history tracks this transfer
                InventoryMutation::create([
                    'hotel_id' => active_hotel_id() ?: ($inventory->hotel_id ?? 1),
                    'inventory_id' => $inventory->id,
                    'user_id' => Auth::id() ?? auth()->id(),
                    'type' => 'transfer',
                    'quantity' => $item['quantity'],
                    'notes' => "Mutasi dari {$fromWarehouse->name} ke {$targetWarehouse->name}" . ($request->notes ? " - {$request->notes}" : ''),
                    'reference_type' => WarehouseTransfer::class,
                    'reference_id' => $transfer->id,
                ]);
            }

            DB::commit();

            return $this->ajaxOrRedirect('Mutasi stok berhasil disimpan.', redirect()->back()->getTargetUrl());

        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Gagal mutasi stok: ' . $e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
