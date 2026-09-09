<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BankAccount;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinalCashService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftReportController extends Controller
{
    protected FinalCashService $finalCashService;

    public function __construct(FinalCashService $finalCashService)
    {
        $this->finalCashService = $finalCashService;
    }

    /**
     * Display the shift report.
     */
    public function index(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::parse(get_hotel_date());
        $shiftId = $request->input('shift_id');

        $shifts = Shift::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->where('is_off', false)
            ->orderBy('sort_order')
            ->get();

        // Default to first shift if none selected
        $selectedShift = $shiftId ? Shift::find($shiftId) : $shifts->first();

        $reportData = [];
        $totals = [
            'pemasukan_room' => 0,
            'pemasukan_room_cash' => 0,
            'pemasukan_room_transfer' => 0,
            'pemasukan_lain' => 0,
            'pengeluaran' => 0,
        ];

        if ($selectedShift) {
            $reportData = $this->buildShiftReport($hotelId, $date, $selectedShift);
            $totals['pemasukan_room'] = collect($reportData)->sum('pemasukan_room');
            $totals['pemasukan_room_cash'] = collect($reportData)->sum('pemasukan_room_cash');
            $totals['pemasukan_room_transfer'] = collect($reportData)->sum('pemasukan_room_transfer');
            $totals['pemasukan_lain'] = collect($reportData)->sum('pemasukan_lain');
            $totals['pengeluaran'] = collect($reportData)->sum('pengeluaran');
        }

        // Get employees working this shift on this date
        $employeesOnShift = [];
        if ($selectedShift) {
            $employeesOnShift = EmployeeSchedule::with('employee')
                ->where('hotel_id', $hotelId)
                ->where('schedule_date', $date->format('Y-m-d'))
                ->where('shift_id', $selectedShift->id)
                ->get()
                ->pluck('employee')
                ->filter();
        }

        $confirmedHandovers = $this->getConfirmedHandovers($hotelId, $date, $selectedShift);

        $expenseCategories = \App\Models\TransactionCategory::where('type', 'expense')->orderBy('name')->get();

        return view('reports.shift_report', compact(
            'shifts', 'selectedShift', 'date', 'reportData', 'totals', 'employeesOnShift', 'confirmedHandovers', 'expenseCategories'
        ));
    }

    /**
     * Serah terima yang sudah dikonfirmasi masuk shift ini pada tanggal ini
     * (uang kas titipan dari shift sebelumnya, bukan pendapatan baru).
     */
    protected function getConfirmedHandovers(int $hotelId, Carbon $date, ?Shift $shift)
    {
        if (!$shift) {
            return collect();
        }

        return \App\Models\ShiftHandover::with(['outgoingEmployee', 'outgoingShift'])
            ->where('hotel_id', $hotelId)
            ->forShift($shift->id, $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get();
    }

    /**
     * Build the shift report data.
     */
    public function buildShiftReport(int $hotelId, Carbon $date, Shift $shift): array
    {
        // Determine the time window for this shift
        $shiftStart = Carbon::parse($date->format('Y-m-d') . ' ' . $shift->start_time);
        $shiftEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $shift->end_time);

        // Handle overnight shifts (end_time < start_time means it crosses midnight)
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }

        // Get transactions within this shift time window
        $transactions = Transaction::with(['booking.room', 'booking.guest', 'guest', 'bankAccount'])
            ->where('hotel_id', $hotelId)
            ->where('status', 'success')
            ->where(function ($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = [];
        $no = 1;

        foreach ($transactions as $trx) {
            $roomNumber = '-';
            if ($trx->booking && $trx->booking->room) {
                $roomNumber = $trx->booking->room->room_number;
            } elseif ($trx->booking && $trx->booking->custom_room_name) {
                $roomNumber = $trx->booking->custom_room_name;
            }

            $pemasukanRoom = 0;
            $pemasukanRoomCash = 0;
            $pemasukanRoomTransfer = 0;
            $pemasukanLain = 0;
            $pengeluaran = 0;

            if ($trx->type === 'payment') {
                // Check if it's a room payment or other
                if ($trx->booking_id) {
                    // Split by cash/transfer based on bank account name
                    $isCash = $trx->bankAccount && str_contains(strtolower($trx->bankAccount->name), 'tunai');
                    if ($isCash) {
                        $pemasukanRoomCash = (float) $trx->amount;
                    } else {
                        $pemasukanRoomTransfer = (float) $trx->amount;
                    }
                    $pemasukanRoom = (float) $trx->amount;
                } else {
                    $pemasukanLain = (float) $trx->amount;
                }
            } elseif ($trx->type === 'charge') {
                // Charges are billed items (not actual income yet), skip or treat as lain
                $pemasukanLain = (float) $trx->amount;
            } elseif ($trx->type === 'refund' || $trx->type === 'expense') {
                $pengeluaran = (float) $trx->amount;
            }

            $rows[] = [
                'no' => $no++,
                'room' => $roomNumber,
                'hari' => $trx->created_at->format('d/m'),
                'description' => $trx->description,
                'guest_name' => $trx->guest->name ?? ($trx->booking?->guest?->name ?? '-'),
                'pemasukan_room' => $pemasukanRoom,
                'pemasukan_room_cash' => $pemasukanRoomCash,
                'pemasukan_room_transfer' => $pemasukanRoomTransfer,
                'pemasukan_lain' => $pemasukanLain,
                'pengeluaran' => $pengeluaran,
                'payment_method' => $trx->payment_method,
                'transaction_id' => $trx->id,
                'time' => $trx->created_at->format('H:i'),
            ];
        }

        // Also include POS orders within this shift
        $posOrders = \App\Models\PosOrder::with(['guest', 'booking.room'])
            ->where('hotel_id', $hotelId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->whereNull('booking_id') // Only standalone POS (not charged to room)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($posOrders as $order) {
            $rows[] = [
                'no' => $no++,
                'room' => 'POS',
                'hari' => $order->created_at->format('d/m'),
                'description' => 'POS Order #' . $order->order_number,
                'guest_name' => $order->guest->name ?? '-',
                'pemasukan_room' => 0,
                'pemasukan_room_cash' => 0,
                'pemasukan_room_transfer' => 0,
                'pemasukan_lain' => (float) $order->total_amount,
                'pengeluaran' => 0,
                'payment_method' => $order->payment_method ?? '-',
                'transaction_id' => null,
                'time' => $order->created_at->format('H:i'),
            ];
        }

        // Sort by time
        usort($rows, fn($a, $b) => strcmp($a['time'], $b['time']));

        // Re-number
        foreach ($rows as $i => &$row) {
            $row['no'] = $i + 1;
        }

        return $rows;
    }

    /**
     * Record a shift expense (kas keluar). Creates an expense Transaction so it's
     * picked up by buildShiftReport() (already reads type=expense in the shift
     * window) and by Laporan Harian / Financial Report, which read the same table.
     */
    public function storeExpense(Request $request)
    {
        $hotelId = active_hotel_id();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'category_id' => 'required|exists:transaction_categories,id',
            'date' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $bankAccount = BankAccount::where('hotel_id', $hotelId)->where('is_cash', true)->first()
            ?? BankAccount::where('hotel_id', $hotelId)->firstOrFail();

        if ($bankAccount->available_balance < $validated['amount']) {
            return back()->with('error', 'Saldo kas tidak cukup untuk pengeluaran ini.');
        }

        // Timestamp must land inside the shift's time window, or buildShiftReport()
        // won't pick it up. Use "now" when recording live; fall back to shift start
        // for a backfilled (past date) entry.
        $shift = Shift::findOrFail($validated['shift_id']);
        $shiftStart = Carbon::parse($validated['date'] . ' ' . $shift->start_time);
        $shiftEnd = Carbon::parse($validated['date'] . ' ' . $shift->end_time);
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }
        $timestamp = now()->between($shiftStart, $shiftEnd) ? now() : $shiftStart;

        DB::transaction(function () use ($validated, $bankAccount, $hotelId, $timestamp) {
            Transaction::create([
                'hotel_id' => $hotelId,
                'bank_account_id' => $bankAccount->id,
                'category_id' => $validated['category_id'],
                'user_id' => auth()->id(),
                'type' => 'expense',
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'status' => 'success',
                'is_realized' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $bankAccount->decrement('balance', $validated['amount']);
            $bankAccount->decrement('available_balance', $validated['amount']);

            if ($bankAccount->isCashAccount()) {
                $this->finalCashService->recordOut(
                    $bankAccount,
                    $validated['amount'],
                    'shift_expense',
                    null,
                    $validated['description']
                );
            }
        });

        return redirect()->route('reports.shift', ['date' => $validated['date'], 'shift_id' => $validated['shift_id']])
            ->with('success', 'Pengeluaran shift berhasil dicatat.');
    }

    /**
     * Export shift report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::parse(get_hotel_date());
        $shiftId = $request->input('shift_id');

        $selectedShift = $shiftId ? Shift::find($shiftId) : Shift::where('hotel_id', $hotelId)->active()->first();

        $reportData = [];
        $totals = ['pemasukan_room' => 0, 'pemasukan_room_cash' => 0, 'pemasukan_room_transfer' => 0, 'pemasukan_lain' => 0, 'pengeluaran' => 0];

        if ($selectedShift) {
            $reportData = $this->buildShiftReport($hotelId, $date, $selectedShift);
            $totals['pemasukan_room'] = collect($reportData)->sum('pemasukan_room');
            $totals['pemasukan_room_cash'] = collect($reportData)->sum('pemasukan_room_cash');
            $totals['pemasukan_room_transfer'] = collect($reportData)->sum('pemasukan_room_transfer');
            $totals['pemasukan_lain'] = collect($reportData)->sum('pemasukan_lain');
            $totals['pengeluaran'] = collect($reportData)->sum('pengeluaran');
        }

        $employeesOnShift = [];
        if ($selectedShift) {
            $employeesOnShift = EmployeeSchedule::with('employee')
                ->where('hotel_id', $hotelId)
                ->where('schedule_date', $date->format('Y-m-d'))
                ->where('shift_id', $selectedShift->id)
                ->get()
                ->pluck('employee')
                ->filter();
        }

        $confirmedHandovers = $this->getConfirmedHandovers($hotelId, $date, $selectedShift);

        $hotel = current_hotel();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.shift_report_pdf', [
            'reportData' => $reportData,
            'totals' => $totals,
            'selectedShift' => $selectedShift,
            'date' => $date,
            'hotel' => $hotel,
            'employeesOnShift' => $employeesOnShift,
            'confirmedHandovers' => $confirmedHandovers,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'laporan_shift_' . ($selectedShift->code ?? 'all') . '_' . $date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
