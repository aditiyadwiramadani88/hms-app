<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\FinalCashService;
use App\Services\ReportService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BankAccountMutationExport;
use App\Exports\FinalCashMutationExport;
use Barryvdh\DomPDF\Facade\Pdf;

class BankAccountController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected FinalCashService $finalCashService;
    protected ReportService $reportService;

    public function __construct(FinalCashService $finalCashService, ReportService $reportService)
    {
        $this->finalCashService = $finalCashService;
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of all bank accounts/wallets.
     */
    public function index(Request $request)
    {
        $accounts = BankAccount::where('hotel_id', active_hotel_id())
            ->withCount(['transactions' => function($query) {
                $query->where('status', 'success');
            }])
            ->get();

        $totalBalance = $accounts->sum('balance');

        $month = $request->filled('month')
            ? \Carbon\Carbon::createFromFormat('Y-m', $request->input('month'))
            : now();
        $monthlyReport = $this->reportService->getRevenueReport($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
        $monthlyRevenue = $monthlyReport['cash_received'] ?? 0;
        $realizedRevenue = $monthlyReport['realized_revenue'] ?? 0;
        $cashReceivedByMethod = $monthlyReport['cash_received_by_method'] ?? collect();
        $realizedByMethod = $monthlyReport['realized_by_method'] ?? collect();

        return view('admin.bank-accounts.index', compact('accounts', 'totalBalance', 'monthlyRevenue', 'realizedRevenue', 'month', 'cashReceivedByMethod', 'realizedByMethod'));
    }

    /**
     * Store a newly created bank account.
     */
    public function store(Request $request)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin'])) {
                abort(403, 'Only Admin can create bank accounts.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'account_holder' => 'nullable|string|max:255',
                'balance' => 'nullable|numeric|min:0',
            ]);

            $validated['hotel_id'] = active_hotel_id();
            $validated['balance'] = $validated['balance'] ?? 0;
            $validated['available_balance'] = $validated['balance'];

            $account = BankAccount::create($validated);

            return $this->ajaxOrRedirect('Bank account created successfully.', route('bank-accounts.index'), $account, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the mutation/history of a specific account.
     */
    public function show(BankAccount $bankAccount)
    {
        if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
            abort(403, 'You do not have permission to access this account.');
        }

        $query = Transaction::where('bank_account_id', $bankAccount->id)
            ->with(['booking.guest', 'guest', 'user', 'category'])
            ->where('status', 'success');

        // Search filter
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.room', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filter
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        // Income/expense filter. category_id is virtually never set on real
        // transactions (booking payments, POS, expense entries all skip it), so
        // filtering by TransactionCategory would return nothing for most data.
        // type is populated and already drives the +/- coloring in the table
        // below, so that's the real "income vs expense" dimension here.
        if (request('flow') === 'income') {
            $query->where('type', 'payment');
        } elseif (request('flow') === 'expense') {
            $query->whereIn('type', ['expense', 'refund']);
        }

        // Force tab=mutasi on this paginator's links (and tab=final_cash below on
        // finalCashMutations') so pagination/filter within one tab can't carry a
        // stale tab param from the other and land the page back on the wrong tab.
        $transactions = $query->latest()->paginate(20)->withQueryString()->appends(['tab' => 'mutasi']);

        $incomeCategories = \App\Models\TransactionCategory::where('hotel_id', active_hotel_id())
            ->where('type', 'income')
            ->where('is_active', true)
            ->get();

        $expenseCategories = \App\Models\TransactionCategory::where('hotel_id', active_hotel_id())
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        $finalCashMutations = null;
        if ($bankAccount->isCashAccount()) {
            $query = \App\Models\FinalCashMutation::where('bank_account_id', $bankAccount->id)
                ->with(['user', 'booking.guest', 'booking.room']);

            if (request('fc_search')) {
                $fcSearch = request('fc_search');
                $query->where(function ($q) use ($fcSearch) {
                    $q->where('description', 'like', "%{$fcSearch}%")
                      ->orWhereHas('booking.guest', function ($q2) use ($fcSearch) {
                          $q2->where('name', 'like', "%{$fcSearch}%");
                      })
                      ->orWhereHas('booking.room', function ($q2) use ($fcSearch) {
                          $q2->where('room_number', 'like', "%{$fcSearch}%");
                      });
                });
            }
            if (request('fc_from')) {
                $query->whereDate('created_at', '>=', request('fc_from'));
            }
            if (request('fc_to')) {
                $query->whereDate('created_at', '<=', request('fc_to'));
            }

            $finalCashMutations = $query->latest()->paginate(20, ['*'], 'fc_page')->withQueryString()->appends(['tab' => 'final_cash']);
        }

        return view('admin.bank-accounts.show', compact('bankAccount', 'transactions', 'incomeCategories', 'expenseCategories', 'finalCashMutations'));
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
                abort(403, 'You do not have permission to access this account.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'account_holder' => 'nullable|string|max:255',
            ]);

            $bankAccount->update($validated);

            return $this->ajaxOrRedirect('Account updated successfully.', route('bank-accounts.show', $bankAccount), $bankAccount);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified account.
     */
    public function destroy(BankAccount $bankAccount)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
                abort(403, 'You do not have permission to access this account.');
            }

            // Check if account has transactions
            if ($bankAccount->transactions()->exists()) {
                $msg = 'Cannot delete account with existing transactions. Deactivate it instead.';
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return back()->with('error', $msg);
            }

            $bankAccount->delete();

            return $this->ajaxOrRedirect('Account deleted successfully.', route('bank-accounts.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record a custom income (deposit) to an account.
     */
    public function deposit(Request $request, BankAccount $bankAccount)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
                abort(403, 'You do not have permission to access this account.');
            }

            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string|max:255',
                'category_id' => 'required|exists:transaction_categories,id',
                'transaction_date' => 'required|date',
            ]);

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $bankAccount) {
                \App\Models\Transaction::create([
                    'hotel_id' => active_hotel_id(),
                    'bank_account_id' => $bankAccount->id,
                    'category_id' => $validated['category_id'],
                    'user_id' => auth()->id(),
                    'type' => 'payment',
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'payment_method' => str_contains(strtolower($bankAccount->name), 'tunai') ? 'cash' : 'bank_transfer',
                    'status' => 'success',
                    'is_realized' => true, // Manual deposits are realized immediately
                    // Marks this row as a manual income deposit so reports that also read
                    // income_transactions (e.g. daily report) can exclude it and avoid double-counting.
                    'reference_id' => 'MANUAL_INCOME',
                    'created_at' => \Carbon\Carbon::parse($validated['transaction_date']),
                    'updated_at' => \Carbon\Carbon::parse($validated['transaction_date']),
                ]);

                $bankAccount->increment('balance', $validated['amount']);
                $bankAccount->increment('available_balance', $validated['amount']);

                // Also record in income_transactions for daily report manual income column
                \App\Models\IncomeTransaction::create([
                    'hotel_id' => active_hotel_id(),
                    'amount' => $validated['amount'],
                    'payment_method' => str_contains(strtolower($bankAccount->name), 'tunai') ? 'cash' : 'bank_transfer',
                    'bank_account_id' => $bankAccount->id,
                    'transaction_date' => $validated['transaction_date'],
                    'category' => $validated['description'],
                    'notes' => $validated['description'],
                ]);
            });

            return $this->ajaxOrRedirect('Income recorded successfully.', route('bank-accounts.show', $bankAccount));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record an expense (withdraw) from an account.
     */
    public function exportPdf(Request $request, BankAccount $bankAccount)
    {
        if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
            abort(403, 'You do not have permission to access this account.');
        }

        $query = Transaction::where('bank_account_id', $bankAccount->id)
            ->with(['booking.guest', 'guest', 'user', 'category'])
            ->where('status', 'success');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.room', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->get();
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $pdf = Pdf::loadView('admin.bank-accounts.exports.pdf', compact('bankAccount', 'transactions', 'dateFrom', 'dateTo'));
        return $pdf->download('mutasi_' . str_replace(' ', '_', strtolower($bankAccount->name)) . '_' . now()->format('YmdHi') . '.pdf');
    }

    public function exportExcel(Request $request, BankAccount $bankAccount)
    {
        if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
            abort(403, 'You do not have permission to access this account.');
        }

        return Excel::download(
            new BankAccountMutationExport($bankAccount, $request->all()),
            'mutasi_' . str_replace(' ', '_', strtolower($bankAccount->name)) . '_' . now()->format('YmdHi') . '.xlsx'
        );
    }

    public function withdraw(Request $request, BankAccount $bankAccount)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
                abort(403, 'You do not have permission to access this account.');
            }

            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string|max:255',
                'category_id' => 'required|exists:transaction_categories,id',
                'transaction_date' => 'required|date',
            ]);

            if ($bankAccount->available_balance < $validated['amount']) {
                $msg = 'Insufficient available balance (ready to withdraw) in this account.';
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return back()->with('error', $msg);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $bankAccount) {
                \App\Models\Transaction::create([
                    'hotel_id' => active_hotel_id(),
                    'bank_account_id' => $bankAccount->id,
                    'category_id' => $validated['category_id'],
                    'user_id' => auth()->id(),
                    'type' => 'expense',
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'status' => 'success',
                    'is_realized' => true,
                    'created_at' => \Carbon\Carbon::parse($validated['transaction_date']),
                    'updated_at' => \Carbon\Carbon::parse($validated['transaction_date']),
                ]);

                $bankAccount->decrement('balance', $validated['amount']);
                $bankAccount->decrement('available_balance', $validated['amount']);

                if ($bankAccount->isCashAccount()) {
                    $this->finalCashService->recordOut(
                        $bankAccount,
                        $validated['amount'],
                        'owner_withdrawal',
                        null,
                        $validated['description']
                    );
                }
            });

            return $this->ajaxOrRedirect('Expense recorded successfully.', route('bank-accounts.show', $bankAccount));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function exportFinalCashPdf(Request $request, BankAccount $bankAccount)
    {
        if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
            abort(403, 'You do not have permission to access this account.');
        }

        if (!$bankAccount->isCashAccount()) {
            abort(404, 'Final Cash export is only available for cash accounts.');
        }

        $query = \App\Models\FinalCashMutation::where('bank_account_id', $bankAccount->id)
            ->with(['user', 'booking.guest', 'booking.room']);

        if ($request->filled('fc_search')) {
            $search = $request->fc_search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('booking.guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.room', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('fc_from')) {
            $query->whereDate('created_at', '>=', $request->fc_from);
        }
        if ($request->filled('fc_to')) {
            $query->whereDate('created_at', '<=', $request->fc_to);
        }

        $mutations = $query->latest()->get();
        $dateFrom  = $request->fc_from;
        $dateTo    = $request->fc_to;

        $pdf = Pdf::loadView('admin.bank-accounts.exports.final-cash-pdf', compact('bankAccount', 'mutations', 'dateFrom', 'dateTo'));
        return $pdf->download('final_cash_' . str_replace(' ', '_', strtolower($bankAccount->name)) . '_' . now()->format('YmdHi') . '.pdf');
    }

    public function exportFinalCashExcel(Request $request, BankAccount $bankAccount)
    {
        if (!auth()->user()->hasRole(['Admin', 'Super Admin']) && $bankAccount->hotel_id !== active_hotel_id()) {
            abort(403, 'You do not have permission to access this account.');
        }

        if (!$bankAccount->isCashAccount()) {
            abort(404, 'Final Cash export is only available for cash accounts.');
        }

        return Excel::download(
            new FinalCashMutationExport($bankAccount, $request->all()),
            'final_cash_' . str_replace(' ', '_', strtolower($bankAccount->name)) . '_' . now()->format('YmdHi') . '.xlsx'
        );
    }

    public function adjustFinalCash(Request $request, BankAccount $bankAccount)
    {
        try {
            if (!auth()->user()->hasRole(['Admin', 'Super Admin'])) {
                abort(403, 'Only Admin or Super Admin can make manual adjustments.');
            }

            if (!$bankAccount->isCashAccount()) {
                $msg = 'Final Cash adjustments only apply to cash accounts.';
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return back()->with('error', $msg);
            }

            $validated = $request->validate([
                'type' => 'required|in:in,out',
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string|max:255',
            ]);

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $bankAccount) {
                if ($validated['type'] === 'in') {
                    // Mixing → Final: uang keluar dari kas mixing, masuk ke final (hak owner)
                    $bankAccount->decrement('balance', $validated['amount']);
                    $bankAccount->increment('available_balance', $validated['amount']);
                    $this->finalCashService->recordIn(
                        $bankAccount,
                        $validated['amount'],
                        'manual_adjustment',
                        null,
                        $validated['description']
                    );
                } else {
                    // Final → Mixing: uang kembali dari final ke kas mixing
                    $bankAccount->increment('balance', $validated['amount']);
                    $bankAccount->decrement('available_balance', $validated['amount']);
                    $this->finalCashService->recordOut(
                        $bankAccount,
                        $validated['amount'],
                        'manual_adjustment',
                        null,
                        $validated['description']
                    );
                }
            });

            return $this->ajaxOrRedirect('Final Cash adjustment recorded successfully.', route('bank-accounts.show', $bankAccount));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
