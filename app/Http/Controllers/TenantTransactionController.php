<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Tenant;
use App\Models\TenantProduct;
use App\Models\TenantTransaction;
use App\Services\TenantTransactionService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantTransactionController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function __construct(
        private TenantTransactionService $transactionService
    ) {}

    public function dashboard()
    {
        $tenant = auth()->user()->tenant;
        $today = now()->toDateString();

        $todayRevenue = $tenant->transactions()->whereDate('transaction_date', $today)->sum('total_amount');
        $monthlyRevenue = $tenant->transactions()->whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year)->sum('total_amount');
        $todayTransactions = $tenant->transactions()->whereDate('transaction_date', $today)->count();
        $unpaidBillings = $tenant->billings()->whereIn('status', ['unpaid', 'overdue'])->count();
        $recentTransactions = $tenant->transactions()->latest('transaction_date')->take(5)->get();

        return view('tenant.dashboard', compact('tenant', 'todayRevenue', 'monthlyRevenue', 'todayTransactions', 'unpaidBillings', 'recentTransactions'));
    }

    public function billings()
    {
        $tenant = auth()->user()->tenant;
        $billings = $tenant->billings()->latest('billing_period')->paginate(20);

        return view('tenant.billings.index', compact('billings'));
    }

    public function index()
    {
        $tenant = auth()->user()->tenant;

        $transactions = $tenant->transactions()
            ->with('items')
            ->when(request('date'), fn($q) => $q->whereDate('transaction_date', request('date')))
            ->when(request('period'), fn($q) => $this->filterByPeriod($q, request('period')))
            ->when(request('payment_method'), fn($q) => $q->where('payment_method', request('payment_method')))
            ->latest('transaction_date')
            ->paginate(25);

        $summary = $this->transactionService->getTransactionSummary($tenant, request('period', 'daily'));

        return view('tenant.transactions.index', compact('transactions', 'tenant', 'summary'));
    }

    private function filterByPeriod($query, string $period)
    {
        switch ($period) {
            case 'today':
                return $query->whereDate('transaction_date', now()->toDateString());
            case 'week':
                return $query->whereBetween('transaction_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString(),
                ]);
            case 'month':
                return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
            default:
                return $query;
        }
    }

    public function create()
    {
        $tenant = auth()->user()->tenant;

        $products = TenantProduct::where('tenant_id', $tenant->id)
            ->active()
            ->inStock()
            ->with(['addonGroups.activeItems'])
            ->orderBy('name')
            ->get();

        // Also load global addon groups (product_id = null)
        $globalAddonGroups = \App\Models\TenantProductAddonGroup::with(['activeItems'])
            ->where('tenant_id', $tenant->id)
            ->whereNull('tenant_product_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $categories = TenantProduct::where('tenant_id', $tenant->id)
            ->active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('tenant.transactions.create', compact('tenant', 'products', 'categories', 'globalAddonGroups'));
    }

    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.tenant_product_id' => 'required|exists:tenant_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.addons' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Parse addons JSON string into array
        $items = collect($request->items)->map(function ($item) {
            $item['addons'] = !empty($item['addons']) ? json_decode($item['addons'], true) : [];
            return $item;
        })->toArray();

        foreach ($items as $item) {
            $product = TenantProduct::where('id', $item['tenant_product_id'])
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$product) {
                $errorMessage = 'Invalid product selected.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return redirect()->back()
                    ->withErrors(['items' => $errorMessage])
                    ->withInput();
            }

            // Validate addon items belong to this tenant
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    if (empty($addon['addon_item_id'])) continue;
                    $addonItem = \App\Models\TenantProductAddonItem::whereHas('group', function ($q) use ($tenant) {
                        $q->where('tenant_id', $tenant->id);
                    })->where('id', $addon['addon_item_id'])->first();

                    if (!$addonItem) {
                        $errorMessage = 'Invalid add-on selected.';
                        if ($this->isAjaxRequest()) {
                            return $this->ajaxError($errorMessage);
                        }
                        return redirect()->back()->withErrors(['items' => $errorMessage])->withInput();
                    }
                }
            }
        }

        try {
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('tenant-payment-proofs', 'public');
            }

            $transaction = $this->transactionService->createTransaction($tenant, [
                'payment_method' => $request->payment_method,
                'payment_proof' => $proofPath,
                'notes' => $request->notes,
                'items' => $items,
            ]);

            return $this->ajaxOrRedirect('Transaction created successfully.', route('tenant.transactions.show', $transaction), $transaction, 201);
        } catch (InsufficientStockException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Transaction failed: ' . $e->getMessage());
            }
            return redirect()->back()
                ->with('error', 'Transaction failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(TenantTransaction $transaction)
    {
        $tenant = auth()->user()->tenant;

        if ($transaction->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $transaction->load('items');

        $btItems = $transaction->items->map(function($i) {
            $addons = [];
            if ($i->addons_total > 0 && $i->addons_json) {
                $parsed = json_decode($i->addons_json, true) ?? [];
                foreach ($parsed as $a) {
                    $addons[] = ['name' => $a['name'], 'price' => number_format($a['price'] * $i->quantity, 0, ',', '.')];
                }
            }
            return [
                'name' => $i->product_name,
                'qty' => $i->quantity,
                'price' => number_format($i->price, 0, ',', '.'),
                'subtotal' => number_format($i->subtotal, 0, ',', '.'),
                'addons' => $addons,
            ];
        })->toArray();

        return view('tenant.transactions.show', compact('transaction', 'tenant', 'btItems'));
    }

    public function print(TenantTransaction $transaction)
    {
        $tenant = auth()->user()->tenant;

        if ($transaction->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $transaction->load('items');

        $btItems = $transaction->items->map(function($i) {
            $addons = [];
            if ($i->addons_total > 0 && $i->addons_json) {
                $parsed = json_decode($i->addons_json, true) ?? [];
                foreach ($parsed as $a) {
                    $addons[] = ['name' => $a['name'], 'price' => number_format($a['price'] * $i->quantity, 0, ',', '.')];
                }
            }
            return [
                'name' => $i->product_name,
                'qty' => $i->quantity,
                'price' => number_format($i->price, 0, ',', '.'),
                'subtotal' => number_format($i->subtotal, 0, ',', '.'),
                'addons' => $addons,
            ];
        })->toArray();

        return view('tenant.transactions.print', compact('transaction', 'tenant', 'btItems'));
    }

    public function destroy(TenantTransaction $transaction)
    {
        $tenant = auth()->user()->tenant;

        if ($transaction->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        // Restore stock for each item
        foreach ($transaction->items as $item) {
            if ($item->product) {
                $item->product->incrementStock($item->quantity);
            }
        }

        $transaction->items()->delete();
        $transaction->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
        }

        return redirect()->route('tenant.transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function dailySummary()
    {
        $tenant = auth()->user()->tenant;

        $date = request('date', now()->toDateString());

        $transactions = $tenant->transactions()
            ->whereDate('transaction_date', $date)
            ->with('items')
            ->get();

        $summary = [
            'total_revenue' => $transactions->sum('total_amount'),
            'transaction_count' => $transactions->count(),
            'cash_total' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
            'qris_total' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
            'transfer_total' => $transactions->where('payment_method', 'transfer')->sum('total_amount'),
        ];

        return view('tenant.transactions.daily-summary', compact('tenant', 'transactions', 'summary', 'date'));
    }
}
