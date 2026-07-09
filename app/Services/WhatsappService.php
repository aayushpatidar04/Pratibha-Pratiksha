<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendText(string $number, string $message): bool
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.whatsapp.token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('services.whatsapp.url'), [
                    'phone' => $this->formatNumber($number),
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::warning('WhatsApp message failed', [
                    'number' => $number,
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp message error', [
                'number' => $number,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function formatNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);

        if (strlen($number) === 10) {
            return '91' . $number;
        }

        return $number;
    }
}