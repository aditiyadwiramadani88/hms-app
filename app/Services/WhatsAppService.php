<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl = 'https://api-wa.greenenergiutama.cloud/api/v1';
    protected $apiKey = '1234567890';
    protected $sessionId = 'Test';

    /**
     * Send a text message via WhatsApp API.
     *
     * @param string $to Recipient phone number (e.g., 628123456789)
     * @param string $text The message content
     * @return bool
     */
    public function sendMessage(string $to, string $text): bool
    {
        try {
            // Clean phone number: remove non-digits
            $to = preg_replace('/[^0-9]/', '', $to);
            
            // Basic Indonesian format correction (08... to 628...)
            if (str_starts_with($to, '0')) {
                $to = '62' . substr($to, 1);
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/send-message", [
                'sessionId' => $this->sessionId,
                'to' => $to,
                'text' => $text,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('WhatsApp API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception: ' . $e->getMessage());
            return false;
        }
    }
}
