<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle Midtrans webhook callback.
     */
    public function midtransCallback(Request $request)
    {
        $callback = $request->all();

        // Verify the notification signature
        $serverKey = config('services.midtrans.server_key');
        $receivedSignature = $callback['signature_key'] ?? '';

        // Build the expected signature
        $orderId = $callback['order_id'] ?? '';
        $statusCode = $callback['status_code'] ?? '';
        $grossAmount = $callback['gross_amount'] ?? '';
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($receivedSignature !== $expectedSignature) {
            Log::warning('Midtrans callback: Invalid signature', [
                'order_id' => $orderId,
                'received_signature' => $receivedSignature,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature',
            ], 403);
        }

        try {
            $result = $this->paymentService->handleMidtransCallback($callback);

            Log::info('Midtrans callback processed successfully', [
                'order_id' => $orderId,
                'transaction_status' => $callback['transaction_status'] ?? '',
                'result' => $result,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Callback processed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans callback processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'callback' => $callback,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Callback processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
