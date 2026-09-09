<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Midtrans\Config;
use Midtrans\Snap;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentService
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Process a payment for a transaction.
     *
     * @param Transaction $transaction
     * @param string $paymentMethod cash, credit_card, bank_transfer, midtrans, xendit, charge_to_room
     * @param array $data Additional payment data
     * @return Transaction
     * @throws \Exception
     */
    public function processPayment(Transaction $transaction, string $paymentMethod, array $data = []): Transaction
    {
        return DB::transaction(function () use ($transaction, $paymentMethod, $data) {
            $booking = $transaction->booking;

            // Create payment transaction
            $paymentTransaction = Transaction::create([
                'booking_id' => $transaction->booking_id,
                'guest_id' => $transaction->guest_id,
                'user_id' => Auth::id(),
                'type' => 'payment',
                'amount' => $transaction->amount,
                'payment_method' => $paymentMethod,
                'reference_id' => $data['reference_id'] ?? ('PAY-' . $transaction->id . '-' . time()),
                'description' => $data['description'] ?? "Payment via {$paymentMethod} for transaction #{$transaction->id}",
                'status' => 'success',
            ]);

            // Update booking payment status
            if ($booking) {
                $totalCharged = Transaction::where('booking_id', $booking->id)
                    ->whereIn('type', ['charge'])
                    ->where('status', 'success')
                    ->sum('amount');

                $totalPaid = Transaction::where('booking_id', $booking->id)
                    ->where('type', 'payment')
                    ->where('status', 'success')
                    ->sum('amount');

                if ($totalPaid >= $totalCharged) {
                    $booking->update(['payment_status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $booking->update(['payment_status' => 'partial']);
                }
            }

            \App\Models\AuditLog::log(
                'payment.processed',
                "Payment of {$transaction->amount} processed via {$paymentMethod} for transaction #{$transaction->id}",
                $paymentTransaction
            );

            return $paymentTransaction;
        });
    }

    /**
     * Create a Midtrans payment transaction for a booking.
     *
     * @param Booking $booking
     * @return string snapToken
     * @throws \Exception
     */
    public function createMidtransTransaction(Booking $booking): string
    {
        // Configure Midtrans
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $outstandingBalance = $booking->total_price - $booking->transactions()
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        if ($outstandingBalance <= 0) {
            throw new \Exception('Booking is already fully paid.');
        }

        $orderId = 'BOOKING-' . $booking->id . '-' . time();
        $grossAmount = (int) $outstandingBalance;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $booking->guest->name ?? 'Guest',
                'email' => $booking->guest->email ?? '',
                'phone' => $booking->guest->phone ?? '',
            ],
            'item_details' => [
                [
                    'id' => 'ROOM-' . $booking->room->room_number,
                    'price' => $grossAmount,
                    'quantity' => 1,
                    'name' => "Booking #{$booking->id} - Room {$booking->room->room_number}",
                ],
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/payment/midtrans/callback',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        // Create pending transaction
        Transaction::create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'user_id' => Auth::id(),
            'type' => 'charge',
            'amount' => $outstandingBalance,
            'payment_method' => 'midtrans',
            'reference_id' => $orderId,
            'description' => 'Midtrans payment for booking #' . $booking->id,
            'status' => 'pending',
        ]);

        return $snapToken;
    }

    /**
     * Handle Midtrans webhook callback.
     *
     * @param array $callback
     * @return array
     */
    public function handleMidtransCallback(array $callback): array
    {
        $orderId = $callback['order_id'] ?? '';
        $transactionStatus = $callback['transaction_status'] ?? '';
        $fraudStatus = $callback['fraud_status'] ?? '';

        // Extract booking ID from order_id
        preg_match('/BOOKING-(\d+)/', $orderId, $matches);
        $bookingId = $matches[1] ?? null;

        if (!$bookingId) {
            return ['status' => 'error', 'message' => 'Invalid order ID'];
        }

        $transaction = Transaction::where('reference_id', $orderId)
            ->where('payment_method', 'midtrans')
            ->first();

        if (!$transaction) {
            return ['status' => 'error', 'message' => 'Transaction not found'];
        }

        return DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $callback) {
            $status = 'failed';

            if ($transactionStatus === 'capture') {
                $status = ($fraudStatus === 'accept') ? 'success' : 'failed';
            } elseif (in_array($transactionStatus, ['settlement', 'capture'])) {
                $status = 'success';
            } elseif (in_array($transactionStatus, ['pending'])) {
                $status = 'pending';
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $status = 'failed';
            } elseif (in_array($transactionStatus, ['refund', 'partial_refund'])) {
                $status = 'success'; // Already settled, refund is separate
            }

            $transaction->update([
                'status' => $status,
                'description' => json_encode($callback),
            ]);

            if ($status === 'success' && $transaction->booking) {
                $this->processPayment($transaction, 'midtrans', [
                    'reference_id' => $orderId,
                    'description' => "Midtrans payment settled: {$transactionStatus}",
                ]);
            }

            return [
                'status' => 'success',
                'message' => 'Callback processed',
                'transaction_status' => $status,
            ];
        });
    }

    /**
     * Add a charge to a guest's room bill.
     *
     * @param Guest $guest
     * @param Booking $booking
     * @param float $amount
     * @param string $description
     * @return Transaction
     */
    public function chargeToRoom(Guest $guest, Booking $booking, float $amount, string $description): Transaction
    {
        return DB::transaction(function () use ($guest, $booking, $amount, $description) {
            $transaction = Transaction::create([
                'booking_id' => $booking->id,
                'guest_id' => $guest->id,
                'user_id' => Auth::id(),
                'type' => 'charge',
                'amount' => $amount,
                'payment_method' => 'charge_to_room',
                'reference_id' => 'CHARGE-' . $booking->id . '-' . time(),
                'description' => $description,
                'status' => 'success',
            ]);

            // Update booking total price
            $booking->increment('total_price', $amount);

            \App\Models\AuditLog::log(
                'charge.room',
                "Charge of {$amount} added to room {$booking->room->room_number}: {$description}",
                $transaction
            );

            return $transaction;
        });
    }

    /**
     * Generate a PDF invoice for a booking.
     *
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function generateInvoice(Booking $booking)
    {
        $booking->load(['guest', 'room.roomType', 'transactions', 'posOrders.items']);

        $transactions = $booking->transactions()->where('status', 'success')->get();

        $totalCharges = $booking->total_price
            + $booking->posOrders()->where('status', 'completed')->sum('total_amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        $totalRefunds = $transactions->where('type', 'refund')->sum('amount');
        $balance = $totalCharges - $totalPayments + $totalRefunds;

        $data = [
            'booking' => $booking,
            'transactions' => $transactions,
            'totalCharges' => $totalCharges,
            'totalPayments' => $totalPayments,
            'totalRefunds' => $totalRefunds,
            'balance' => $balance,
            'generatedAt' => now(),
            'invoiceNumber' => 'INV-' . $booking->id . '-' . $booking->created_at->format('Ymd'),
        ];

        $pdf = Pdf::loadView('invoices.booking', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("invoice-{$booking->id}.pdf");
    }

    /**
     * Export transactions to Excel.
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return StreamedResponse
     */
    public function exportTransactions($startDate, $endDate): StreamedResponse
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $transactions = Transaction::with(['booking.room', 'guest', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'transactions_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.xlsx';

        return Excel::download(new \App\Exports\TransactionExport($transactions), $filename);
    }
}
