<?php

namespace App\Http\Controllers;

use App\Models\TenantProduct;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TenantProductController extends Controller
{
    use AjaxResponse;

    public function index(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return redirect()->route('dashboard')->with('error', 'You are not assigned to any tenant.');
        }

        $query = TenantProduct::where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $products = $query->latest()->paginate(12);

        $categories = TenantProduct::where('tenant_id', $tenant->id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('tenant.products._table', compact('products'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                ]
            ]);
        }

        return view('tenant.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return redirect()->route('tenant.products.index');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return $this->ajaxError('Access denied.', null, 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->except(['_token', 'photo']);
        $data['tenant_id'] = $tenant->id;
        $data['stock'] = $request->filled('stock') ? $request->stock : null;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('tenant-products', 'public');
        }

        TenantProduct::create($data);

        return $this->ajaxSuccess('Product created successfully.');
    }

    public function edit(TenantProduct $product)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant || $product->tenant_id !== $tenant->id) {
            return $this->ajaxError('Access denied.', null, 403);
        }

        return $this->ajaxSuccess('Product loaded.', [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => $product->is_active,
            'photo_url' => $product->photo ? asset('storage/' . $product->photo) : null,
        ]);
    }

    public function update(Request $request, TenantProduct $product)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant || $product->tenant_id !== $tenant->id) {
            return $this->ajaxError('Access denied.', null, 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->except(['_token', '_method', 'photo']);
        $data['stock'] = $request->filled('stock') ? $request->stock : null;

        if ($request->hasFile('photo')) {
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            $data['photo'] = $request->file('photo')->store('tenant-products', 'public');
        }

        $product->update($data);

        return $this->ajaxSuccess('Product updated successfully.');
    }

    public function destroy(TenantProduct $product)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant || $product->tenant_id !== $tenant->id) {
            return $this->ajaxError('Access denied.', null, 403);
        }

        if ($product->photo && Storage::disk('public')->exists($product->photo)) {
            Storage::disk('public')->delete($product->photo);
        }

        $product->delete();

        return $this->ajaxSuccess('Product deleted successfully.');
    }
}
