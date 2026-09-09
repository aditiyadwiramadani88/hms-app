<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\KostPaymentApproval;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KostPaymentDetailController extends Controller
{
    /**
     * Show payment detail (rincian pembayaran) for a specific kost booking.
     */
    public function show(Booking $booking)
    {
        $this->authorize('viewKostPaymentDetail', $booking);

        $booking->load(['guest', 'room.roomType', 'user']);

        // deposit_amount only reflects the deposit set at booking time; a
        // deposit added mid-stay via addItemCharge (refundable inventory
        // item) only creates an is_deposit charge Transaction (see
        // KostReportController for the same fix).
        $extraDeposit = Transaction::where('booking_id', $booking->id)
            ->where('type', 'charge')
            ->where('is_deposit', true)
            ->where(fn($q) => $q->where('reference_id', '!=', 'DEPOSIT-' . $booking->id)->orWhereNull('reference_id'))
            ->sum('amount');
        $totalDeposit = (float) $booking->deposit_amount + (float) $extraDeposit;

        // Get all payment transactions for this booking (excluding markups)
        $payments = Transaction::with(['user', 'bankAccount', 'kostApproval.approver'])
            ->where('booking_id', $booking->id)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->where(function ($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Ensure each payment has an approval record
        foreach ($payments as $payment) {
            if (!$payment->kostApproval) {
                KostPaymentApproval::create([
                    'transaction_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'status' => 'pending',
                ]);
                $payment->load('kostApproval.approver');
            }
        }

        // Build payment rows with period calculation
        $paymentRows = [];
        foreach ($payments as $index => $payment) {
            // Calculate period based on booking check_in + month offset
            $periodStart = $booking->check_in->copy()->addMonths($index);
            $periodEnd = $periodStart->copy()->addMonth();

            $paymentRows[] = [
                'no' => $index + 1,
                'transaction' => $payment,
                'harga_kost' => (float) $booking->base_price,
                'deposit' => $index === 0 ? $totalDeposit : 0,
                'jumlah' => (float) $payment->amount,
                'tanggal_tf' => $payment->payment_method !== 'cash' ? $payment->created_at->format('d/m') : null,
                'tanggal_cash' => $payment->payment_method === 'cash' ? $payment->created_at->format('d/m') : null,
                'periode' => $periodStart->format('d/m') . ' - ' . $periodEnd->format('d/m Y'),
                'kasir' => $payment->user->name ?? '-',
                'approval' => $payment->kostApproval,
            ];
        }

        $hotel = current_hotel();

        return view('reports.kost_payment_detail', compact(
            'booking', 'paymentRows', 'hotel'
        ));
    }

    /**
     * Approve a kost payment.
     */
    public function approve(Request $request, KostPaymentApproval $approval)
    {
        $this->authorize('approveKostPayment');

        $approval->approve(auth()->id(), $request->input('notes'));

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    /**
     * Reject a kost payment.
     */
    public function reject(Request $request, KostPaymentApproval $approval)
    {
        $this->authorize('approveKostPayment');

        $request->validate(['notes' => 'required|string|max:500']);

        $approval->reject(auth()->id(), $request->input('notes'));

        return back()->with('success', 'Pembayaran ditolak.');
    }

    /**
     * Export payment detail to PDF.
     */
    public function exportPdf(Booking $booking)
    {
        $this->authorize('viewKostPaymentDetail', $booking);

        $booking->load(['guest', 'room.roomType', 'user']);

        // deposit_amount only reflects the deposit set at booking time; a
        // deposit added mid-stay via addItemCharge (refundable inventory
        // item) only creates an is_deposit charge Transaction (see
        // KostReportController for the same fix).
        $extraDeposit = Transaction::where('booking_id', $booking->id)
            ->where('type', 'charge')
            ->where('is_deposit', true)
            ->where(fn($q) => $q->where('reference_id', '!=', 'DEPOSIT-' . $booking->id)->orWhereNull('reference_id'))
            ->sum('amount');
        $totalDeposit = (float) $booking->deposit_amount + (float) $extraDeposit;

        $payments = Transaction::with(['user', 'bankAccount', 'kostApproval.approver'])
            ->where('booking_id', $booking->id)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->where(function ($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $paymentRows = [];
        foreach ($payments as $index => $payment) {
            $periodStart = $booking->check_in->copy()->addMonths($index);
            $periodEnd = $periodStart->copy()->addMonth();

            $paymentRows[] = [
                'no' => $index + 1,
                'harga_kost' => (float) $booking->base_price,
                'deposit' => $index === 0 ? $totalDeposit : 0,
                'jumlah' => (float) $payment->amount,
                'tanggal_tf' => $payment->payment_method !== 'cash' ? $payment->created_at->format('d/m') : null,
                'tanggal_cash' => $payment->payment_method === 'cash' ? $payment->created_at->format('d/m') : null,
                'periode' => $periodStart->format('d/m') . ' - ' . $periodEnd->format('d/m Y'),
                'kasir' => $payment->user->name ?? '-',
                'approval' => $payment->kostApproval,
            ];
        }

        $hotel = current_hotel();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.kost_payment_detail_pdf', [
            'booking' => $booking,
            'paymentRows' => $paymentRows,
            'hotel' => $hotel,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'rincian_pembayaran_' . ($booking->room?->room_number ?? 'custom') . '_' . ($booking->guest->name ?? 'guest') . '.pdf';

        return $pdf->download($filename);
    }
}
