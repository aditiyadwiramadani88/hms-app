<?php

namespace App\Http\Controllers;

use App\Models\TenantBilling;
use App\Models\TenantProduct;
use App\Models\TenantTransaction;
use App\Services\TenantTransactionService;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    public function __construct(
        private TenantTransactionService $transactionService
    ) {}

    public function index()
    {
        $tenant = auth()->user()->tenant;

        // Today's stats
        $todayRevenue = $this->transactionService->getDailyRevenue($tenant);
        $monthlyRevenue = $this->transactionService->getMonthlyRevenue($tenant);
        $todayTransactions = TenantTransaction::where('tenant_id', $tenant->id)
            ->whereDate('transaction_date', now()->toDateString())
            ->count();

        // Outstanding billings
        $outstandingBillings = TenantBilling::where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->count();

        // Recent transactions
        $recentTransactions = TenantTransaction::where('tenant_id', $tenant->id)
            ->with('items')
            ->latest('transaction_date')
            ->take(5)
            ->get();

        // Low stock products
        $lowStockProducts = TenantProduct::where('tenant_id', $tenant->id)
            ->whereNotNull('stock')
            ->where('stock', '<=', 5)
            ->where('is_active', true)
            ->count();

        return view('tenant.dashboard', compact(
            'tenant',
            'todayRevenue',
            'monthlyRevenue',
            'todayTransactions',
            'outstandingBillings',
            'recentTransactions',
            'lowStockProducts'
        ));
    }
}
