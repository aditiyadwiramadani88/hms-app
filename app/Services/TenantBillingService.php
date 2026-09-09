<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantBilling;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TenantBillingService
{
    /**
     * Generate monthly billings for active tenants.
     * Billings already existing for the period will be skipped.
     */
    public function generateMonthlyBillings(Carbon $month, ?array $tenantIds = null): Collection
    {
        $billingPeriod = $month->format('Y-m');
        $billings = collect();

        $query = Tenant::where('is_active', true);

        if ($tenantIds) {
            $query->whereIn('id', $tenantIds);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            // Skip if billing already exists for this period
            $exists = TenantBilling::where('tenant_id', $tenant->id)
                ->where('billing_period', $billingPeriod)
                ->exists();

            if ($exists) {
                continue;
            }

            // Calculate due date from tenant's rent_due_day
            $dueDate = Carbon::createFromDate($month->year, $month->month, $tenant->rent_due_day);

            // If due date is in the past, mark as overdue immediately
            $status = $dueDate->isPast() ? 'overdue' : 'unpaid';

            $billing = TenantBilling::create([
                'tenant_id' => $tenant->id,
                'billing_period' => $billingPeriod,
                'amount' => $tenant->rent_amount,
                'due_date' => $dueDate,
                'status' => $status,
                'generated_by' => Auth::id(),
            ]);

            $billings->push($billing);
        }

        return $billings;
    }

    /**
     * Mark a billing as paid.
     */
    public function markAsPaid(TenantBilling $billing, float $amount, ?string $notes = null): TenantBilling
    {
        if ($billing->status === 'paid') {
            throw new \Exception('Billing is already marked as paid.');
        }

        if ($amount <= 0) {
            throw new \Exception('Payment amount must be greater than zero.');
        }

        $billing->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $amount,
            'notes' => $notes ? ($billing->notes ? $billing->notes . "\n" . $notes : $notes) : $billing->notes,
        ]);

        return $billing->fresh();
    }

    /**
     * Get all overdue billings.
     */
    public function getOverdueBillings(): Collection
    {
        return TenantBilling::with('tenant')
            ->where('status', 'overdue')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Check and mark unpaid billings past due date as overdue.
     * Returns count of newly marked overdue billings.
     */
    public function checkAndMarkOverdue(): int
    {
        return TenantBilling::where('status', 'unpaid')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get billing summary for a tenant.
     */
    public function getBillingSummary(Tenant $tenant): array
    {
        $billings = $tenant->billings();

        return [
            'total_outstanding' => (clone $billings)->whereIn('status', ['unpaid', 'overdue'])->sum('amount'),
            'total_paid' => (clone $billings)->where('status', 'paid')->sum('paid_amount'),
            'total_billings' => $billings->count(),
            'overdue_count' => (clone $billings)->where('status', 'overdue')->count(),
        ];
    }

    /**
     * Get monthly revenue for a tenant (used for commission calculation if needed).
     */
    public function getMonthlyRevenue(Tenant $tenant, Carbon $month): float
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $tenant->transactions()
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_amount');
    }
}
