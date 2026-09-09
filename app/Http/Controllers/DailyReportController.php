<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PosOrder;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display the daily income & expense report.
     */
    public function index(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::parse(get_hotel_date());

            $hotel = current_hotel();
            $cutoffTime = $hotel->daily_report_cutoff ?? '13:00';
            $startOfDay = $date->copy()->subDay()->setTimeFromTimeString($cutoffTime);
            $endOfDay = $date->copy()->setTimeFromTimeString($cutoffTime);

            $data = $this->buildReportData($hotelId, $startOfDay, $endOfDay);
            $rows = $data['rows'];
            $totals = $data['totals'];
            $checkoutSummary = $data['checkoutSummary'];

            $shifts = \App\Models\Shift::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->where('is_off', false)
                ->orderBy('sort_order')
                ->get();

            // Bank accounts for dynamic wallet sub-columns
            $bankAccounts = \App\Models\BankAccount::where('hotel_id', $hotelId)
                ->orderBy('id')
                ->get();

            // Approval status
            try {
                $approval = \App\Models\DailyReportApproval::where('hotel_id', $hotelId)
                    ->whereDate('report_date', $date->toDateString())
                    ->first();
                $canApprove = auth()->user()->can('reports.daily.approve');
            } catch (\Exception $e) {
                $approval = null;
                $canApprove = false;
            }

            return view('reports.daily_report', compact(
                'rows', 'totals', 'date', 'hotel', 'checkoutSummary', 'shifts', 'bankAccounts', 'approval', 'canApprove'
            ));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export daily report to PDF.
     */
    public function exportPdf(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::parse(get_hotel_date());
            $hotel = current_hotel();

            $cutoffTime = $hotel->daily_report_cutoff ?? '13:00';
            $startOfDay = $date->copy()->subDay()->setTimeFromTimeString($cutoffTime);
            $endOfDay = $date->copy()->setTimeFromTimeString($cutoffTime);

            $data = $this->buildReportData($hotelId, $startOfDay, $endOfDay);

            // Bank accounts for dynamic wallet sub-columns
            $bankAccounts = \App\Models\BankAccount::where('hotel_id', $hotelId)
                ->orderBy('id')
                ->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_report_pdf', [
                'rows' => $data['rows'],
                'totals' => $data['totals'],
                'date' => $date,
                'hotel' => $hotel,
                'bankAccounts' => $bankAccounts,
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('laporan_harian_' . $date->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'notes' => 'nullable|string|max:500',
            ]);

            $hotelId = active_hotel_id();
            $reportDate = Carbon::parse($request->date)->toDateString();

            $approval = \App\Models\DailyReportApproval::firstOrCreate(
                ['hotel_id' => $hotelId, 'report_date' => $reportDate],
                ['status' => 'pending']
            );

            if ($approval->isApproved()) {
                return redirect()->back()->with('error', 'Laporan sudah disetujui sebelumnya.');
            }

            $approval->approve(auth()->id(), $request->notes);

            return redirect()->route('reports.daily', ['date' => $reportDate])
                ->with('success', 'Laporan harian tanggal ' . $reportDate . ' berhasil disetujui.');
        } catch (\QueryException $e) {
            return redirect()->back()->with('error', 'Silakan jalankan migration terlebih dahulu: php artisan migrate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'notes' => 'nullable|string|max:500',
            ]);

            $hotelId = active_hotel_id();
            $reportDate = Carbon::parse($request->date)->toDateString();

            $approval = \App\Models\DailyReportApproval::firstOrCreate(
                ['hotel_id' => $hotelId, 'report_date' => $reportDate],
                ['status' => 'pending']
            );

            $approval->reject(auth()->id(), $request->notes);

            return redirect()->route('reports.daily', ['date' => $reportDate])
                ->with('error', 'Laporan harian tanggal ' . $reportDate . ' ditolak.');
        } catch (\QueryException $e) {
            return redirect()->back()->with('error', 'Silakan jalankan migration terlebih dahulu: php artisan migrate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function buildReportData(int $hotelId, Carbon $startOfDay, Carbon $endOfDay): array
    {
        $transactions = Transaction::with(['booking.room.roomType', 'booking.guest', 'booking.bookingSource', 'guest', 'user', 'bankAccount', 'category'])
            ->where('hotel_id', $hotelId)
            ->where('status', 'success')
            ->where(function ($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            // Manual deposits are already represented via income_transactions below (kolom "Manual Income
            // Tercatat"); skip them here so they aren't counted twice in kas_masuk/total.
            ->where(function ($q) {
                $q->whereNull('reference_id')->orWhere('reference_id', '!=', 'MANUAL_INCOME');
            })
            ->where(function ($q) use ($startOfDay, $endOfDay) {
                // Booking payments: only if booking checked out within this report period
                $q->where(function ($bq) use ($startOfDay, $endOfDay) {
                    $bq->whereNotNull('booking_id')
                        ->where('type', 'payment')
                        ->whereHas('booking', function ($bookingQ) use ($startOfDay, $endOfDay) {
                            $bookingQ->whereBetween('actual_check_out', [$startOfDay, $endOfDay])
                                ->whereNotIn('status', ['cancelled', 'no_show']);
                        });
                });
                // Non-booking transactions created within this report period
                $q->orWhere(function ($nbq) use ($startOfDay, $endOfDay) {
                    $nbq->whereNull('booking_id')
                        ->whereBetween('created_at', [$startOfDay, $endOfDay]);
                });
                // Expense type (always by date created)
                $q->orWhere(function ($eq) use ($startOfDay, $endOfDay) {
                    $eq->where('type', 'expense')
                        ->whereBetween('created_at', [$startOfDay, $endOfDay]);
                });
                // Refunds created today
                $q->orWhere(function ($rq) use ($startOfDay, $endOfDay) {
                    $rq->where('type', 'refund')
                        ->whereBetween('created_at', [$startOfDay, $endOfDay]);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->unique('id');

        // Get bookings that checked out today
        $checkoutsToday = Booking::with(['room', 'guest'])
            ->where('hotel_id', $hotelId)
            ->whereBetween('actual_check_out', [$startOfDay, $endOfDay])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        $checkoutSummary = $checkoutsToday->map(function ($booking) {
        return [
                'room' => $booking->room?->room_number ?? ($booking->custom_room_name ?? '-'),
                'guest' => $booking->guest?->name ?? '-',
                'total' => (float) $booking->total_price,
                'paid' => (float) $booking->transactions()->where('type', 'payment')->where('status', 'success')->sum('amount'),
            ];
        });

        // Get purchases (pengeluaran/beli) for the day
        $purchases = [];
        if (class_exists(\App\Models\Purchase::class)) {
            $purchases = Purchase::where('hotel_id', $hotelId)
                ->whereBetween('purchase_date', [$startOfDay, $endOfDay])
                ->where('status', 'received')
                ->orderBy('purchase_date', 'asc')
                ->get();
        }

        // Get manual income for the day (use datetime comparison for cutoff consistency)
        $manualIncomes = \App\Models\IncomeTransaction::where('hotel_id', $hotelId)
            ->whereBetween('transaction_date', [$startOfDay, $endOfDay])
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Build report rows
        $rows = [];
        $totals = [
            'kas_masuk' => 0,
            'kas_keluar' => 0,
            'beli' => 0,
            'jual_retur' => 0,
            'bayar' => 0,
            'lain' => 0,
            'manual_income' => 0,
        ];

        // Preload POS orders for item details in keterangan
        $posRefs = $transactions->whereNull('booking_id')
            ->whereNotNull('reference_id')
            ->pluck('reference_id')
            ->filter(fn($ref) => str_starts_with($ref, 'POS-'));
        $posOrders = \App\Models\PosOrder::whereIn('order_number', $posRefs)
            ->with('items.inventory')
            ->get()
            ->keyBy('order_number');

        $no = 1;
        foreach ($transactions as $trx) {
            $roomNumber = '-';
            $keterangan = preg_replace('/^(Booking payment(\s*-\s*)?|Initial payment for booking #\d+(\s*-\s*)?)/i', '', $trx->description);
            $bookingId = null;
            $checkIn = '-';
            $checkOut = '-';
            $lamaStay = '-';
            $bookingSource = '-';
            $roomName = '-';

            if ($trx->booking) {
                $roomNumber = $trx->booking->room?->room_number ?? ($trx->booking->custom_room_name ?? '-');
                $bookingId = $trx->booking->id;
                $checkIn = $trx->booking->check_in ? $trx->booking->check_in->format('d/m/Y') : '-';
                $checkOut = $trx->booking->check_out ? $trx->booking->check_out->format('d/m/Y') : '-';
                $lamaStay = $trx->booking->check_in && $trx->booking->check_out
                    ? $trx->booking->check_in->diffInDays($trx->booking->check_out) . ' Malam'
                    : '-';
                $bookingSource = $trx->booking->bookingSource?->name
                    ?? ($trx->booking->source === 'online' ? 'Online' : ($trx->booking->source === 'walk_in' ? 'Walk-in' : ($trx->booking->source ?? '-')));
                $roomName = $roomNumber . ($trx->booking->room?->roomType ? ' (' . $trx->booking->room->roomType->name . ')' : '');
                $guestName = $trx->booking->guest?->name ?? '';
                if ($guestName && !str_contains($keterangan, $guestName)) {
                    $keterangan = trim($keterangan) === ''
                        ? $guestName
                        : $keterangan . ' - ' . $guestName;
                }
                // Flag payments that include a (refundable) deposit so the
                // amount is self-explanatory, e.g. "Febri (+50k deposit)".
                if ($trx->type === 'payment' && (float) ($trx->booking->deposit_amount ?? 0) > 0) {
                    $dep = (float) $trx->booking->deposit_amount;
                    $depLabel = fmod($dep, 1000) == 0.0
                        ? number_format($dep / 1000, 0, ',', '.') . 'k'
                        : number_format($dep, 0, ',', '.');
                    $keterangan .= ' (+' . $depLabel . ' deposit)';
                }
            } else {
                // Try to extract room number from description for non-booking transactions
                if (preg_match('/\bR\s*(\d{3,4})\b/i', $trx->description, $m)) {
                    $extractedRoomNum = $m[1];
                    $matchedBooking = Booking::where('hotel_id', $hotelId)
                        ->whereBetween('actual_check_out', [$startOfDay, $endOfDay])
                        ->whereHas('room', fn($q) => $q->where('room_number', $extractedRoomNum))
                        ->with(['room.roomType', 'bookingSource'])
                        ->first();
                    if ($matchedBooking) {
                        $bookingId = $matchedBooking->id;
                        $roomNumber = $matchedBooking->room->room_number;
                        $checkIn = $matchedBooking->check_in ? $matchedBooking->check_in->format('d/m/Y') : '-';
                        $checkOut = $matchedBooking->check_out ? $matchedBooking->check_out->format('d/m/Y') : '-';
                        $lamaStay = $matchedBooking->check_in && $matchedBooking->check_out
                            ? $matchedBooking->check_in->diffInDays($matchedBooking->check_out) . ' Malam'
                            : '-';
                        $bookingSource = $matchedBooking->bookingSource?->name
                            ?? ($matchedBooking->source === 'online' ? 'Online' : ($matchedBooking->source === 'walk_in' ? 'Walk-in' : ($matchedBooking->source ?? '-')));
                        $roomName = $roomNumber . ' (' . $matchedBooking->room->roomType->name . ')';
                        $guestName = $matchedBooking->guest?->name ?? '';
                        if ($guestName && !str_contains($keterangan, $guestName)) {
                            $keterangan = trim($keterangan) === ''
                                ? $guestName
                                : $keterangan . ' - ' . $guestName;
                        }
                    }
                }
            }

            // Append POS item details to keterangan
            if (!$trx->booking_id && $trx->reference_id) {
                $posOrder = $posOrders->get($trx->reference_id);
                if ($posOrder && $posOrder->items->isNotEmpty()) {
                    $itemLines = $posOrder->items->map(fn($item) =>
                        $item->inventory?->name . ' x' . $item->quantity . ' @Rp' . number_format($item->unit_price, 0, ',', '.')
                    );
                    $keterangan .= ' [' . $itemLines->implode(', ') . ']';
                }
            }

            $kasMasuk = 0;
            $kasKeluar = 0;
            $beli = 0;
            $jualRetur = 0;
            $bayar = 0;
            $lain = 0;

            if ($trx->type === 'payment') {
                if ($trx->booking_id) {
                    // Room payment
                    $bayar = (float) $trx->amount;
                } else {
                    // Other income (POS, etc)
                    $lain = (float) $trx->amount;
                }
                $kasMasuk = (float) $trx->amount;
            } elseif ($trx->type === 'refund') {
                $kasKeluar = (float) $trx->amount;
                $jualRetur = (float) $trx->amount;
            } elseif ($trx->type === 'expense') {
                $kasKeluar = (float) $trx->amount;
                $lain = (float) $trx->amount;
            } elseif ($trx->type === 'charge') {
                // Charges are billed but not cash movement unless paid
                continue;
            }

            $totals['kas_masuk'] += $kasMasuk;
            $totals['kas_keluar'] += $kasKeluar;
            $totals['beli'] += $beli;
            $totals['jual_retur'] += $jualRetur;
            $totals['bayar'] += $bayar;
            $totals['lain'] += $lain;

            $rows[] = [
                'no' => $no++,
                'checkin' => $checkIn,
                'checkout' => $checkOut,
                'lama_stay' => $lamaStay,
                'sumber' => $bookingSource,
                'room_name' => $roomName,
                'booking_id' => $bookingId,
                'keterangan' => $keterangan,
                'payment_date' => $trx->created_at ? $trx->created_at->format('d/m/Y H:i') : '-',
                'kas_masuk' => $kasMasuk,
                'kas_keluar' => $kasKeluar,
                'beli' => $beli,
                'jual_retur' => $jualRetur,
                'bayar' => $bayar,
                'lain' => $lain,
                'payment_method' => $trx->payment_method,
                'bank_account' => $trx->bankAccount->name ?? '-',
                'booking_id' => $trx->booking_id,
                'bank_account_id' => $trx->bank_account_id,
            ];
        }

        // Add purchases as pengeluaran
        foreach ($purchases as $purchase) {
            $kasKeluar = (float) $purchase->total_amount;
            $totals['kas_keluar'] += $kasKeluar;
            $totals['beli'] += $kasKeluar;

            $rows[] = [
                'no' => $no++,
                'checkin' => '-',
                'checkout' => '-',
                'lama_stay' => '-',
                'sumber' => '-',
                'room_name' => '-',
                'booking_id' => null,
                'keterangan' => 'Pembelian: ' . ($purchase->supplier?->name ?? 'Supplier'),
                'payment_date' => $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-',
                'kas_masuk' => 0,
                'kas_keluar' => $kasKeluar,
                'beli' => $kasKeluar,
                'jual_retur' => 0,
                'bayar' => 0,
                'lain' => 0,
                'payment_method' => '-',
                'bank_account' => '-',
                'bank_account_id' => null,
            ];
        }

        // Sort by room number (booking rows first, then non-booking)
        usort($rows, function ($a, $b) {
            $aHasRoom = $a['booking_id'] && $a['room_name'] !== '-';
            $bHasRoom = $b['booking_id'] && $b['room_name'] !== '-';
            if ($aHasRoom && !$bHasRoom) return -1;
            if (!$aHasRoom && $bHasRoom) return 1;
            return strnatcmp($a['room_name'], $b['room_name']);
        });

        // Add manual income rows
        foreach ($manualIncomes as $inc) {
            $manualIncome = (float) $inc->amount;
            $totals['kas_masuk'] += $manualIncome;
            $totals['manual_income'] += $manualIncome;

            $rows[] = [
                'no' => $no++,
                'checkin' => '-',
                'checkout' => '-',
                'lama_stay' => '-',
                'sumber' => '-',
                'room_name' => '-',
                'booking_id' => null,
                'keterangan' => 'Manual Income: ' . ($inc->notes ?? $inc->category ?? '-'),
                'payment_date' => $inc->transaction_date ? $inc->transaction_date->format('d/m/Y') : '-',
                'kas_masuk' => $manualIncome,
                'kas_keluar' => 0,
                'beli' => 0,
                'jual_retur' => 0,
                'bayar' => 0,
                'lain' => 0,
                'manual_income' => $manualIncome,
                'payment_method' => $inc->payment_method ?? '-',
                'bank_account' => $inc->bankAccount->name ?? '-',
                'bank_account_id' => $inc->bank_account_id,
            ];
        }

        // Re-number after sort
        $no = 1;
        foreach ($rows as &$row) {
            $row['no'] = $no++;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'checkoutSummary' => $checkoutSummary
        ];
    }
}
