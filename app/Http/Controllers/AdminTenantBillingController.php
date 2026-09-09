<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Services\TenantBillingService;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminTenantBillingController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function __construct(
        private TenantBillingService $billingService
    ) {}

    public function index()
    {
        $billings = TenantBilling::with(['tenant', 'generator'])
            ->when(request('tenant_id'), fn($q) => $q->where('tenant_id', request('tenant_id')))
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('billing_period'), fn($q) => $q->where('billing_period', request('billing_period')))
            ->orderBy('due_date', 'desc')
            ->paginate(25);

        $tenants = Tenant::orderBy('name')->get();
        $periods = TenantBilling::distinct()->orderBy('billing_period', 'desc')->pluck('billing_period');

        return view('tenant-admin.billing.index', compact('billings', 'tenants', 'periods'));
    }

    public function generateForm()
    {
        $tenants = Tenant::active()->orderBy('name')->get();
        return view('tenant-admin.billing.generate', compact('tenants'));
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $billings = $this->billingService->generateMonthlyBillings($month, $request->tenant_ids);

            $skipped = 0;
            if ($request->tenant_ids) {
                $skipped = count($request->tenant_ids) - $billings->count();
            } else {
                $skipped = Tenant::active()->count() - $billings->count();
            }

            $message = "Generated {$billings->count()} billing(s).";
            if ($skipped > 0) {
                $message .= " {$skipped} skipped (already exist).";
            }

            return $this->ajaxOrRedirect($message, route('admin.tenant-billings.index'), $billings);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(TenantBilling $billing)
    {
        $billing->load(['tenant', 'generator']);
        return view('tenant-admin.billing.show', compact('billing'));
    }

    public function markAsPaidForm(TenantBilling $billing)
    {
        if ($billing->status === 'paid') {
            return redirect()->back()->with('error', 'Billing is already paid.');
        }
        return view('tenant-admin.billing.mark-paid', compact('billing'));
    }

    public function markAsPaid(Request $request, TenantBilling $billing)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $validator->errors());
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $this->billingService->markAsPaid($billing, $request->amount, $request->notes);
            return $this->ajaxOrRedirect('Billing marked as paid.', route('admin.tenant-billings.show', $billing));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function checkOverdue()
    {
        try {
            $count = $this->billingService->checkAndMarkOverdue();
            return $this->ajaxOrRedirect("Marked {$count} billing(s) as overdue.", back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
