<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantTransactionService;
use App\Services\TenantBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TenantMonitoringController extends Controller
{
    public function __construct(
        private TenantTransactionService $transactionService,
        private TenantBillingService $billingService
    ) {}

    public function index()
    {
        $tenants = Tenant::with(['users', 'billings' => fn($q) => $q->latest()->take(1)])
            ->withCount(['transactions', 'products'])
            ->when(request('search'), fn($q) => $q->where('name', 'like', '%' . request('search') . '%'))
            ->when(request('status'), fn($q) => $q->where('is_active', request('status') === 'active'))
            ->paginate(25);

        return view('tenant-admin.monitoring.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['products', 'billings', 'users']);

        $monthlyRevenue = $this->transactionService->getMonthlyRevenue($tenant);
        $weeklyRevenue = $this->transactionService->getTransactionSummary($tenant, 'weekly')['total_revenue'];
        $dailyRevenue = $this->transactionService->getDailyRevenue($tenant);

        $billingSummary = $this->billingService->getBillingSummary($tenant);

        $transactionQuery = $tenant->transactions()->with('items')->latest();

        if (request('q')) {
            $transactionQuery->where('transaction_number', 'like', '%' . request('q') . '%');
        }

        $recentTransactions = $transactionQuery->paginate(10)->withQueryString();

        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = $this->transactionService->getMonthlyRevenue($tenant, $month);
            $revenueChart[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
            ];
        }

        return view('tenant-admin.monitoring.show', compact(
            'tenant',
            'monthlyRevenue',
            'weeklyRevenue',
            'dailyRevenue',
            'billingSummary',
            'recentTransactions',
            'revenueChart'
        ));
    }

    public function transactions(Tenant $tenant)
    {
        $tenant->load('hotel');

        $query = $tenant->transactions()->with('items');

        // Period filter
        $period = request('period', 'month');
        if ($period === 'custom') {
            if (request('start_date')) {
                $query->whereDate('transaction_date', '>=', request('start_date'));
            }
            if (request('end_date')) {
                $query->whereDate('transaction_date', '<=', request('end_date'));
            }
        } else {
            $this->filterByPeriod($query, $period);
        }

        if (request('payment_method')) {
            $query->where('payment_method', request('payment_method'));
        }

        $transactions = $query->latest('transaction_date')->paginate(50);
        $summary = $this->getTransactionSummary($tenant);

        return view('tenant-admin.monitoring.transactions', compact(
            'tenant',
            'transactions',
            'summary'
        ));
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
            case 'last_month':
                return $query->whereMonth('transaction_date', now()->subMonth()->month)
                    ->whereYear('transaction_date', now()->subMonth()->year);
            default:
                return $query;
        }
    }

    private function getTransactionSummary(Tenant $tenant)
    {
        $query = $tenant->transactions();

        if (request('date_from')) {
            $query->whereDate('transaction_date', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('transaction_date', '<=', request('date_to'));
        }
        if (request('period')) {
            $this->filterByPeriod($query, request('period'));
        }

        $transactions = $query->get();

        $totalItemsSold = 0;
        foreach ($transactions as $tx) {
            $totalItemsSold += $tx->items->sum('quantity');
        }

        return [
            'total_revenue' => $transactions->sum('total_amount'),
            'transaction_count' => $transactions->count(),
            'average_transaction' => $transactions->count() > 0
                ? $transactions->sum('total_amount') / $transactions->count()
                : 0,
            'total_items_sold' => $totalItemsSold,
            'cash_total' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
            'qris_total' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
            'transfer_total' => $transactions->where('payment_method', 'transfer')->sum('total_amount'),
        ];
    }

    public function comparison()
    {
        $startMonth = request('start_month')
            ? Carbon::createFromFormat('Y-m', request('start_month'))->startOfMonth()
            : now()->startOfMonth();
        $endMonth = request('end_month')
            ? Carbon::createFromFormat('Y-m', request('end_month'))->endOfMonth()
            : now()->endOfMonth();

        $tenants = Tenant::active()->with(['products', 'transactions' => function ($q) use ($startMonth, $endMonth) {
            $q->whereBetween('transaction_date', [$startMonth, $endMonth]);
        }])->get();

        $comparisonData = $tenants->map(function ($tenant) {
            $txs = $tenant->transactions;
            $topProduct = null;
            if ($txs->isNotEmpty()) {
                $productSales = [];
                foreach ($txs as $tx) {
                    foreach ($tx->items as $item) {
                        $productSales[$item->product_name] = ($productSales[$item->product_name] ?? 0) + $item->quantity;
                    }
                }
                if (!empty($productSales)) {
                    arsort($productSales);
                    $topProduct = array_key_first($productSales);
                }
            }

            return [
                'tenant' => $tenant,
                'total_revenue' => $txs->sum('total_amount'),
                'transaction_count' => $txs->count(),
                'average_transaction' => $txs->count() > 0 ? $txs->sum('total_amount') / $txs->count() : 0,
                'products_count' => $tenant->products->count(),
                'top_product' => $topProduct,
            ];
        })->sortByDesc('total_revenue')->values();

        return view('tenant-admin.monitoring.comparison', compact('comparisonData'));
    }
}
