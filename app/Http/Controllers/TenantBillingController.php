<?php

namespace App\Http\Controllers;

use App\Models\TenantBilling;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class TenantBillingController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $tenant = auth()->user()->tenant;

        $billings = TenantBilling::where('tenant_id', $tenant->id)
            ->orderBy('billing_period', 'desc')
            ->paginate(25);

        $summary = [
            'total_outstanding' => TenantBilling::where('tenant_id', $tenant->id)
                ->whereIn('status', ['unpaid', 'overdue'])
                ->sum('amount'),
            'total_paid' => TenantBilling::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->sum('paid_amount'),
            'overdue_count' => TenantBilling::where('tenant_id', $tenant->id)
                ->where('status', 'overdue')
                ->count(),
        ];

        return view('tenant.billings.index', compact('billings', 'summary'));
    }

    public function show(TenantBilling $billing)
    {
        $tenant = auth()->user()->tenant;

        if ($billing->tenant_id !== $tenant->id) {
            abort(403, 'Access denied.');
        }

        $billing->load('generator');

        return view('tenant.billings.show', compact('billing'));
    }
}
