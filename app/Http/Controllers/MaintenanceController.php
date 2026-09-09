<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceCategory;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\FinalCashService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected FinalCashService $finalCashService;

    public function __construct(FinalCashService $finalCashService)
    {
        $this->finalCashService = $finalCashService;
    }

    /**
     * Keep a MaintenanceRecord's cost mirrored as an expense Transaction, so it
     * automatically shows up in Laporan Harian (kas keluar) and Financial Report,
     * which both read Transaction type=expense -- the record's cost field alone
     * was never turned into a transaction before this fix.
     */
    protected function syncExpenseTransaction(MaintenanceRecord $record): void
    {
        $referenceId = 'MAINTENANCE-' . $record->id;
        $existing = Transaction::where('reference_id', $referenceId)->first();
        $cost = (float) $record->cost;

        DB::transaction(function () use ($record, $referenceId, $existing, $cost) {
            if ($cost <= 0) {
                if ($existing) {
                    $account = BankAccount::withoutGlobalScope('hotel')->find($existing->bank_account_id);
                    $account?->increment('balance', $existing->amount);
                    $account?->increment('available_balance', $existing->amount);
                    $existing->delete();
                }
                return;
            }

            // BankAccount/TransactionCategory auto-scope to the session's active
            // hotel via BelongsToHotel's global scope. A maintenance record's
            // hotel_id can differ from that (e.g. an Admin/Super Admin editing
            // across hotels), which would silently combine with the explicit
            // filter below into an impossible WHERE and no-op the whole sync --
            // bypass the scope so this always resolves by the record's own hotel.
            $bankAccount = BankAccount::withoutGlobalScope('hotel')->where('hotel_id', $record->hotel_id)->where('is_cash', true)->first()
                ?? BankAccount::withoutGlobalScope('hotel')->where('hotel_id', $record->hotel_id)->first();

            if (!$bankAccount) {
                return;
            }

            $categoryId = TransactionCategory::withoutGlobalScope('hotel')->where('hotel_id', $record->hotel_id)
                ->where('type', 'expense')
                ->where('name', 'like', '%MAINTEN%')
                ->value('id');

            $description = 'Maintenance: ' . ($record->description ?: ($record->category->name ?? 'Service')) . ' (Record #' . $record->id . ')';

            if ($existing) {
                $delta = $cost - (float) $existing->amount;
                $existing->update([
                    'amount' => $cost,
                    'description' => $description,
                    'created_at' => $record->maintenance_date,
                    'updated_at' => $record->maintenance_date,
                ]);
                $bankAccount->decrement('balance', $delta);
                $bankAccount->decrement('available_balance', $delta);
                if ($bankAccount->isCashAccount() && $delta != 0) {
                    $delta > 0
                        ? $this->finalCashService->recordOut($bankAccount, $delta, 'maintenance_cost', $record->id, $description)
                        : $this->finalCashService->recordIn($bankAccount, abs($delta), 'maintenance_cost', $record->id, $description);
                }
                return;
            }

            Transaction::create([
                'hotel_id' => $record->hotel_id,
                'bank_account_id' => $bankAccount->id,
                'category_id' => $categoryId,
                'user_id' => auth()->id(),
                'type' => 'expense',
                'amount' => $cost,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => 'success',
                'is_realized' => true,
                'created_at' => $record->maintenance_date,
                'updated_at' => $record->maintenance_date,
            ]);

            $bankAccount->decrement('balance', $cost);
            $bankAccount->decrement('available_balance', $cost);

            if ($bankAccount->isCashAccount()) {
                $this->finalCashService->recordOut($bankAccount, $cost, 'maintenance_cost', $record->id, $description);
            }
        });
    }

    public function index(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = MaintenanceRecord::where('hotel_id', $hotelId)
            ->with(['room', 'category', 'creator']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('maintenance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('maintenance_date', '<=', $request->date_to);
        }

        $records = $query->latest('maintenance_date')->paginate(20)->withQueryString();
        $categories = MaintenanceCategory::where('hotel_id', $hotelId)->orderBy('sort_order')->get();
        $rooms = Room::where('hotel_id', $hotelId)->orderBy('room_number')->get();

        return view('maintenance.index', compact('records', 'categories', 'rooms'));
    }

    public function create()
    {
        $hotelId = active_hotel_id();
        $categories = MaintenanceCategory::where('hotel_id', $hotelId)->active()->orderBy('sort_order')->get();
        $rooms = Room::where('hotel_id', $hotelId)->orderBy('room_number')->get();

        return view('maintenance.create', compact('categories', 'rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'category_id' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'new' && !MaintenanceCategory::where('id', $value)->exists()) {
                    $fail('Kategori yang dipilih tidak valid.');
                }
            }],
            'maintenance_date' => 'required|date',
            'description' => 'nullable|string',
            'actions' => 'nullable|array',
            'technician_name' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,in_progress,completed',
            'new_category_name' => 'nullable|string|max:255|required_if:category_id,new',
        ]);

        try {
            // Handle quick-add category
            $categoryId = $validated['category_id'];
            if ($categoryId === 'new' && !empty($validated['new_category_name'])) {
                $newCat = MaintenanceCategory::create([
                    'hotel_id' => active_hotel_id(),
                    'name' => $validated['new_category_name'],
                    'is_active' => true,
                ]);
                $categoryId = $newCat->id;
            }

            $record = MaintenanceRecord::create([
                'hotel_id' => active_hotel_id(),
                'room_id' => $validated['room_id'] ?? null,
                'category_id' => $categoryId,
                'maintenance_date' => $validated['maintenance_date'],
                'description' => $validated['description'] ?? null,
                'actions' => $validated['actions'] ?? [],
                'technician_name' => $validated['technician_name'] ?? null,
                'cost' => $validated['cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
                'created_by' => auth()->id(),
            ]);

            $this->syncExpenseTransaction($record);

            return redirect()->route('maintenance.records.index')
                ->with('success', 'Record maintenance berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(MaintenanceRecord $record)
    {
        $hotelId = active_hotel_id();
        $categories = MaintenanceCategory::where('hotel_id', $hotelId)->active()->orderBy('sort_order')->get();
        $rooms = Room::where('hotel_id', $hotelId)->orderBy('room_number')->get();

        return view('maintenance.edit', compact('record', 'categories', 'rooms'));
    }

    public function update(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'category_id' => 'required|exists:maintenance_categories,id',
            'maintenance_date' => 'required|date',
            'description' => 'nullable|string',
            'actions' => 'nullable|array',
            'technician_name' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);

        try {
            $record->update([
                'room_id' => $validated['room_id'] ?? null,
                'category_id' => $validated['category_id'],
                'maintenance_date' => $validated['maintenance_date'],
                'description' => $validated['description'] ?? null,
                'actions' => $validated['actions'] ?? [],
                'technician_name' => $validated['technician_name'] ?? null,
                'cost' => $validated['cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            $this->syncExpenseTransaction($record);

            return redirect()->route('maintenance.records.index')
                ->with('success', 'Record maintenance berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(MaintenanceRecord $record)
    {
        try {
            $record->cost = 0;
            $this->syncExpenseTransaction($record);
            $record->delete();
            return $this->ajaxOrRedirect('Record maintenance berhasil dihapus.', route('maintenance.records.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
