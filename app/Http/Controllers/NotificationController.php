<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Transaction;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Send notification for booking confirmation.
     */
    public function sendBookingNotification(Request $request, Booking $booking)
    {
        $channel = $request->input('channel', 'wa'); // wa, email, print
        $booking->load(['guest', 'room.roomType', 'hotel']);

        if ($channel === 'wa') {
            return $this->sendBookingWA($booking);
        } elseif ($channel === 'email') {
            return $this->sendBookingEmail($booking);
        }

        return back()->with('error', 'Invalid notification channel.');
    }

    /**
     * Send notification for payment receipt.
     */
    public function sendPaymentNotification(Request $request, Booking $booking)
    {
        $channel = $request->input('channel', 'wa');
        $transactionId = $request->input('transaction_id');
        
        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction->booking_id !== $booking->id) {
            return back()->with('error', 'Transaction not found.');
        }

        $booking->load(['guest', 'room.roomType', 'hotel']);

        if ($channel === 'wa') {
            return $this->sendPaymentWA($booking, $transaction);
        } elseif ($channel === 'email') {
            return $this->sendPaymentEmail($booking, $transaction);
        }

        return back()->with('error', 'Invalid notification channel.');
    }

    /**
     * Send notification for booking cancellation.
     */
    public function sendCancelNotification(Request $request, Booking $booking)
    {
        $channel = $request->input('channel', 'wa');
        $booking->load(['guest', 'room.roomType', 'hotel']);

        if ($channel === 'wa') {
            return $this->sendCancelWA($booking);
        } elseif ($channel === 'email') {
            return $this->sendCancelEmail($booking);
        }

        return back()->with('error', 'Invalid notification channel.');
    }

    // --- Private Helper Methods ---

    private function sendBookingWA(Booking $booking)
    {
        if (!$booking->guest || !$booking->guest->phone) {
            return back()->with('error', 'Guest phone number not found.');
        }

        $booking->load(['guest', 'room.roomType', 'hotel', 'posOrders', 'transactions']);

        $manualExtraTotal = $booking->transactions
            ->where('type', 'charge')
            ->where('status', 'success')
            ->whereNull('reference_id')
            ->where('is_deposit', false)
            ->sum('amount');
        $posTotal = $booking->posOrders
            ->where('status', 'completed')
            ->sum('total_amount');
        $grandTotal = $booking->total_price + $manualExtraTotal + $posTotal;
        $totalPaid = $booking->transactions
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');
        $remainingBalance = max(0, $grandTotal - $totalPaid);

        $roomName = $booking->room
            ? $booking->room->room_number . ' (' . ($booking->room->roomType->name ?? '-') . ')'
            : ($booking->custom_room_name ?? 'N/A');

        $hotelName = $booking->hotel->name ?? 'Our Homestay';
        $text = "*KONFIRMASI BOOKING - {$hotelName}*\n";
        $text .= "------------------------------------------\n";
        $text .= "Halo {$booking->guest->name},\n\n";
        $text .= "Terima kasih telah memesan di {$hotelName}. Berikut adalah rincian pesanan Anda:\n\n";
        $text .= "*Booking ID:* #{$booking->id}\n";
        $text .= "*Kamar:* {$roomName}\n";
        $text .= "*Check-in:* " . $booking->check_in->format('d M Y') . ($booking->check_in_time ? " ({$booking->check_in_time})" : '') . "\n";
        $text .= "*Check-out:* " . $booking->check_out->format('d M Y') . ($booking->check_out_time ? " ({$booking->check_out_time})" : '') . "\n";
        $text .= "*Durasi:* " . $booking->total_nights . " Malam\n";
        if ($booking->include_breakfast) {
            $text .= "*Sarapan:* Termasuk ✓\n";
        }
        $text .= "*Grand Total:* Rp " . number_format($grandTotal, 0, ',', '.') . "\n";
        if ($totalPaid > 0) {
            $text .= "*Sudah Dibayar (DP):* Rp " . number_format($totalPaid, 0, ',', '.') . "\n";
            $text .= "*Sisa Tagihan:* Rp " . number_format($remainingBalance, 0, ',', '.') . "\n";
            $text .= "*Status:* " . ($remainingBalance <= 0 ? 'LUNAS' : 'TERBAYAR SEBAGIAN (DP)') . "\n";
        } else {
            $text .= "*Status:* BELUM DIBAYAR\n";
        }
        $text .= "\nKami tunggu kedatangan Anda!\n";
        $text .= "------------------------------------------\n";

        if ($this->waService->sendMessage($booking->guest->phone, $text)) {
            return back()->with('success', 'Booking confirmation sent to WhatsApp!');
        }
        return back()->with('error', 'Failed to send WhatsApp message.');
    }

    private function sendPaymentWA(Booking $booking, Transaction $transaction)
    {
        if (!$booking->guest || !$booking->guest->phone) {
            return back()->with('error', 'Guest phone number not found.');
        }

        $booking->load(['guest', 'room.roomType', 'hotel', 'posOrders', 'transactions']);

        $manualExtraTotal = $booking->transactions
            ->where('type', 'charge')
            ->where('status', 'success')
            ->whereNull('reference_id')
            ->where('is_deposit', false)
            ->sum('amount');
        $posTotal = $booking->posOrders
            ->where('status', 'completed')
            ->sum('total_amount');
        $grandTotal = $booking->total_price + $manualExtraTotal + $posTotal;
        $totalPaid = $booking->transactions
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');
        $remainingBalance = max(0, $grandTotal - $totalPaid);

        $roomName = $booking->room
            ? $booking->room->room_number . ' (' . ($booking->room->roomType->name ?? '-') . ')'
            : ($booking->custom_room_name ?? 'N/A');

        $hotelName = $booking->hotel->name ?? 'Our Homestay';
        $text = "*BUKTI PEMBAYARAN - {$hotelName}*\n";
        $text .= "------------------------------------------\n";
        $text .= "Halo {$booking->guest->name},\n\n";
        $text .= "Pembayaran Anda telah kami terima:\n\n";
        $text .= "*Booking ID:* #{$booking->id}\n";
        $text .= "*Kamar:* {$roomName}\n";
        $text .= "*Jumlah Dibayar:* Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        $text .= "*Metode:* " . ucfirst($transaction->payment_method) . "\n";
        $text .= "*Tanggal:* " . $transaction->created_at->format('d M Y, H:i') . "\n";
        $text .= "*Keterangan:* " . ($transaction->description ?? 'Pembayaran Booking') . "\n\n";
        $text .= "------------------------------------------\n";
        $text .= "*Total Tagihan:* Rp " . number_format($grandTotal, 0, ',', '.') . "\n";
        $text .= "*Total Terbayar:* Rp " . number_format($totalPaid, 0, ',', '.') . "\n";
        $text .= "*Sisa Tagihan:* Rp " . number_format($remainingBalance, 0, ',', '.') . "\n";
        $text .= "*Status Pembayaran:* " . ($remainingBalance <= 0 ? 'LUNAS ✓' : 'BELUM LUNAS (Sisa Rp ' . number_format($remainingBalance, 0, ',', '.') . ')') . "\n\n";
        $text .= "Terima kasih atas pembayaran Anda!\n";
        $text .= "------------------------------------------\n";

        if ($this->waService->sendMessage($booking->guest->phone, $text)) {
            return back()->with('success', 'Payment receipt sent to WhatsApp!');
        }
        return back()->with('error', 'Failed to send WhatsApp message.');
    }

    private function sendCancelWA(Booking $booking)
    {
        if (!$booking->guest || !$booking->guest->phone) {
            return back()->with('error', 'Guest phone number not found.');
        }

        $hotelName = $booking->hotel->name ?? 'Our Homestay';
        $text = "*PEMBATALAN BOOKING - {$hotelName}*\n";
        $text .= "------------------------------------------\n";
        $text .= "Halo {$booking->guest->name},\n\n";
        $text .= "Pesanan Anda dengan ID #{$booking->id} telah dibatalkan.\n\n";
        $text .= "*Rincian:* {$booking->room->room_number} ({$booking->room->roomType->name})\n";
        $text .= "*Tanggal:* " . $booking->check_in->format('d M Y') . " - " . $booking->check_out->format('d M Y') . "\n\n";
        $text .= "Jika ada pertanyaan, silakan hubungi kami.\n";
        $text .= "------------------------------------------\n";

        if ($this->waService->sendMessage($booking->guest->phone, $text)) {
            return back()->with('success', 'Cancellation notice sent to WhatsApp!');
        }
        return back()->with('error', 'Failed to send WhatsApp message.');
    }

    private function sendBookingEmail(Booking $booking)
    {
        if (!$booking->guest || !$booking->guest->email) {
            return back()->with('error', 'Guest email address not found.');
        }

        try {
            Mail::send('emails.booking_confirmation', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->guest->email)
                        ->subject('Booking Confirmation - ' . ($booking->hotel->name ?? 'Our Homestay'));
            });
            return back()->with('success', 'Booking confirmation sent to Email!');
        } catch (\Exception $e) {
            Log::error('Email Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to send Email: ' . $e->getMessage());
        }
    }

    private function sendPaymentEmail(Booking $booking, Transaction $transaction)
    {
        if (!$booking->guest || !$booking->guest->email) {
            return back()->with('error', 'Guest email address not found.');
        }

        try {
            Mail::send('emails.payment_receipt', ['booking' => $booking, 'transaction' => $transaction], function ($message) use ($booking) {
                $message->to($booking->guest->email)
                        ->subject('Payment Receipt - ' . ($booking->hotel->name ?? 'Our Homestay'));
            });
            return back()->with('success', 'Payment receipt sent to Email!');
        } catch (\Exception $e) {
            Log::error('Email Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to send Email: ' . $e->getMessage());
        }
    }

    private function sendCancelEmail(Booking $booking)
    {
        if (!$booking->guest || !$booking->guest->email) {
            return back()->with('error', 'Guest email address not found.');
        }

        try {
            Mail::send('emails.booking_cancellation', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->guest->email)
                        ->subject('Booking Cancellation - ' . ($booking->hotel->name ?? 'Our Homestay'));
            });
            return back()->with('success', 'Cancellation notice sent to Email!');
        } catch (\Exception $e) {
            Log::error('Email Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to send Email: ' . $e->getMessage());
        }
    }

    /**
     * Print methods (returning views designed for printing)
     */
    public function printBooking(Booking $booking)
    {
        $booking->load(['guest', 'room.roomType', 'hotel', 'posOrders', 'transactions']);

        $manualExtraTotal = $booking->transactions
            ->where('type', 'charge')
            ->where('status', 'success')
            ->whereNull('reference_id')
            ->where('is_deposit', false)
            ->sum('amount');

        $posTotal = $booking->posOrders
            ->where('status', 'completed')
            ->sum('total_amount');

        $grandTotal = $booking->total_price + $manualExtraTotal + $posTotal;

        $totalPaid = $booking->transactions
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        $remainingBalance = max(0, $grandTotal - $totalPaid);

        return view('notifications.print_booking', compact(
            'booking',
            'manualExtraTotal',
            'posTotal',
            'grandTotal',
            'totalPaid',
            'remainingBalance'
        ));
    }

    public function printPayment(Booking $booking, Transaction $transaction)
    {
        $booking->load(['guest', 'room.roomType', 'hotel', 'posOrders', 'transactions']);

        $manualExtraTotal = $booking->transactions
            ->where('type', 'charge')
            ->where('status', 'success')
            ->whereNull('reference_id')
            ->where('is_deposit', false)
            ->sum('amount');

        $posTotal = $booking->posOrders
            ->where('status', 'completed')
            ->sum('total_amount');

        $depositCharges = $booking->transactions
            ->where('type', 'charge')
            ->where('is_deposit', true);
        $totalDeposit = $depositCharges->sum('amount');
        if ($totalDeposit <= 0 && ($booking->deposit_amount ?? 0) > 0) {
            $totalDeposit = (float) $booking->deposit_amount;
        }

        $grandTotal = $booking->total_price + $manualExtraTotal + $posTotal;

        $totalPaid = $booking->transactions
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        $remainingBalance = max(0, $grandTotal - $totalPaid);

        return view('notifications.print_payment', compact(
            'booking',
            'transaction',
            'grandTotal',
            'totalPaid',
            'remainingBalance'
        ));
    }

    public function printCancel(Booking $booking)
    {
        $booking->load(['guest', 'room.roomType', 'hotel']);
        return view('notifications.print_cancel', compact('booking'));
    }

    /**
     * Mark a dynamic notification as read (store in session)
     */
    public function markAsRead(Request $request)
    {
        $type = $request->input('type'); // 'booking' or 'room'
        $id = $request->input('id');

        if (!$type || !$id) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $sessionKey = 'dismissed_notifications';
        $dismissed = session($sessionKey, []);

        if (!isset($dismissed[$type])) {
            $dismissed[$type] = [];
        }

        if (!in_array($id, $dismissed[$type])) {
            $dismissed[$type][] = (int)$id;
        }

        session([$sessionKey => $dismissed]);

        return response()->json(['success' => true]);
    }
}
