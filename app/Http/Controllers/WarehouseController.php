<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::where('hotel_id', active_hotel_id())->get();
        return view('pos.warehouse.index', compact('warehouses'));
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
