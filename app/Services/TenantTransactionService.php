<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Tenant;
use App\Models\TenantProduct;
use App\Models\TenantTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantTransactionService
{
    /**
     * Create a new tenant transaction with items.
     *
     * @param Tenant $tenant
     * @param array $data [items, payment_method, notes]
     * @return TenantTransaction
     * @throws InsufficientStockException
     * @throws \Exception
     */
    public function createTransaction(Tenant $tenant, array $data): TenantTransaction
    {
        return DB::transaction(function () use ($tenant, $data) {
            if (empty($data['items'])) {
                throw new \Exception('Transaction must contain at least one item.');
            }

            $totalAmount = 0;
            $itemsData = [];

            // Validate & prepare items
            foreach ($data['items'] as $item) {
                $product = TenantProduct::where('tenant_id', $tenant->id)
                    ->where('id', $item['tenant_product_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Check stock if tracked
                if ($product->stock !== null && $product->stock < $item['quantity']) {
                    throw new InsufficientStockException(
                        $product,
                        $item['quantity'],
                        $product->stock
                    );
                }

                $subtotal = $product->price * $item['quantity'];

                // Process addons
                $addons = $item['addons'] ?? [];
                $addonsTotal = 0;
                $addonsJson = null;

                if (!empty($addons)) {
                    $addonRecords = [];
                    foreach ($addons as $addon) {
                        $addonPrice = (float) ($addon['price'] ?? 0);
                        $addonsTotal += $addonPrice;
                        $addonRecords[] = [
                            'id' => $addon['addon_item_id'] ?? null,
                            'name' => $addon['name'] ?? '',
                            'price' => $addonPrice,
                        ];
                    }
                    $addonsTotal *= $item['quantity'];
                    $addonsJson = json_encode($addonRecords);
                }

                $totalAmount += $subtotal + $addonsTotal;

                $itemsData[] = [
                    'tenant_product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                    'addons_json' => $addonsJson,
                    'addons_total' => $addonsTotal,
                ];

                // Decrement stock
                if ($product->stock !== null) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            // Create transaction
            $transaction = TenantTransaction::create([
                'tenant_id' => $tenant->id,
                'transaction_number' => $this->generateTransactionNumber($tenant),
                'transaction_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'items_count' => count($itemsData),
                'payment_method' => $data['payment_method'],
                'status' => 'paid',
                'payment_proof' => $data['payment_proof'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create transaction items
            $transaction->items()->createMany($itemsData);

            return $transaction->load('items');
        });
    }

    /**
     * Generate transaction number for a tenant.
     */
    private function generateTransactionNumber(Tenant $tenant): string
    {
        $date = now()->format('Ymd');
        $prefix = 'TNT';

        $latest = TenantTransaction::where('tenant_id', $tenant->id)
            ->whereDate('created_at', now()->toDateString())
            ->latest('id')
            ->lockForUpdate()
            ->first();

        $sequence = $latest ? ((int) substr($latest->transaction_number, -4)) + 1 : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get transaction summary for a tenant.
     *
     * @param Tenant $tenant
     * @param string $period daily|weekly|monthly
     * @return array
     */
    public function getTransactionSummary(Tenant $tenant, string $period): array
    {
        $query = TenantTransaction::where('tenant_id', $tenant->id);

        switch ($period) {
            case 'daily':
                $query->whereDate('transaction_date', now()->toDateString());
                break;
            case 'weekly':
                $query->whereBetween('transaction_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString(),
                ]);
                break;
            case 'monthly':
            default:
                $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
                break;
        }

        $transactions = $query->get();
        $totalRevenue = $transactions->sum('total_amount');
        $transactionCount = $transactions->count();
        $averageTransaction = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;

        // Top products
        $topProducts = DB::table('tenant_transaction_items')
            ->join('tenant_transactions', 'tenant_transaction_items.tenant_transaction_id', '=', 'tenant_transactions.id')
            ->where('tenant_transactions.tenant_id', $tenant->id)
            ->when($period === 'daily', fn($q) => $q->whereDate('tenant_transactions.transaction_date', now()->toDateString()))
            ->when($period === 'weekly', fn($q) => $q->whereBetween('tenant_transactions.transaction_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]))
            ->when($period === 'monthly', fn($q) => $q->whereMonth('tenant_transactions.transaction_date', now()->month)
                ->whereYear('tenant_transactions.transaction_date', now()->year))
            ->select('product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Revenue by payment method
        $revenueByMethod = $transactions->groupBy('payment_method')
            ->map(fn($group) => $group->sum('total_amount'));

        return [
            'total_revenue' => round($totalRevenue, 2),
            'transaction_count' => $transactionCount,
            'average_transaction' => round($averageTransaction, 2),
            'top_products' => $topProducts,
            'revenue_by_method' => $revenueByMethod,
        ];
    }

    /**
     * Get daily revenue for a tenant.
     */
    public function getDailyRevenue(Tenant $tenant, ?Carbon $date = null): float
    {
        $date = $date ?? now();

        return (float) TenantTransaction::where('tenant_id', $tenant->id)
            ->whereDate('transaction_date', $date->toDateString())
            ->sum('total_amount');
    }

    /**
     * Get monthly revenue for a tenant.
     */
    public function getMonthlyRevenue(Tenant $tenant, ?Carbon $month = null): float
    {
        $month = $month ?? now();

        return (float) TenantTransaction::where('tenant_id', $tenant->id)
            ->whereMonth('transaction_date', $month->month)
            ->whereYear('transaction_date', $month->year)
            ->sum('total_amount');
    }

    /**
     * Get transactions with date range.
     */
    public function getTransactionsByDateRange(
        Tenant $tenant,
        Carbon $startDate,
        Carbon $endDate,
        ?string $paymentMethod = null
    ): Collection {
        $query = TenantTransaction::where('tenant_id', $tenant->id)
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('items')
            ->orderBy('transaction_date', 'desc');

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        return $query->get();
    }
}
