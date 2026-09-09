<?php

namespace App\Http\Controllers;

use App\Models\TenantProduct;
use App\Models\TenantProductAddonGroup;
use App\Models\TenantProductAddonItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAddonController extends Controller
{
    /**
     * List addon groups for a product (including global addons).
     */
    public function index(TenantProduct $product): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($product->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        // Product-specific groups + global groups (product_id = null)
        $groups = TenantProductAddonGroup::with(['activeItems'])
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) use ($product) {
                $q->where('tenant_product_id', $product->id)
                  ->orWhereNull('tenant_product_id');
            })
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $groups]);
    }

    /**
     * Create addon group.
     */
    public function storeGroup(Request $request, TenantProduct $product): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($product->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:single,multi',
            'is_required' => 'boolean',
            'max_selections' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $group = TenantProductAddonGroup::create([
            'tenant_id' => $tenant->id,
            'tenant_product_id' => $product->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_required' => $validated['is_required'] ?? false,
            'max_selections' => $validated['max_selections'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Add-on group created.', 'data' => $group], 201);
    }

    /**
     * Update addon group.
     */
    public function updateGroup(Request $request, TenantProductAddonGroup $group): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($group->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:single,multi',
            'is_required' => 'boolean',
            'max_selections' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $group->update($validated);

        return response()->json(['success' => true, 'message' => 'Add-on group updated.', 'data' => $group]);
    }

    /**
     * Delete addon group.
     */
    public function destroyGroup(TenantProductAddonGroup $group): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($group->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $group->delete();

        return response()->json(['success' => true, 'message' => 'Add-on group deleted.']);
    }

    /**
     * Create addon item.
     */
    public function storeItem(Request $request, TenantProductAddonGroup $group): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($group->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $item = $group->items()->create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'is_default' => $validated['is_default'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Add-on item created.', 'data' => $item], 201);
    }

    /**
     * Update addon item.
     */
    public function updateItem(Request $request, TenantProductAddonItem $item): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($item->group->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'message' => 'Add-on item updated.', 'data' => $item]);
    }

    /**
     * Delete addon item.
     */
    public function destroyItem(TenantProductAddonItem $item): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($item->group->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => 'Add-on item deleted.']);
    }
}
