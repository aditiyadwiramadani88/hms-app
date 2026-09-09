<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryApiController extends Controller
{
    public function search(Request $request)
    {
        $term = $request->input('term');

        if (empty($term)) {
            return response()->json([]);
        }

        $results = Inventory::where('name', 'LIKE', "%{$term}%")
            ->orWhere('id', 'LIKE', "%{$term}%")
            ->select('id', 'name as text', 'price_per_unit as price') // Format for select2
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}
