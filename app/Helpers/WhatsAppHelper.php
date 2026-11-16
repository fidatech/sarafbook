<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    public static function sendMessage($to, $message): bool
    {
        // switched off in the plan of this Sarafi, or no sender set up
        if (! feature('whatsapp') || ! config('services.wasender.url') || ! config('services.wasender.key')) {
            return false;
        }

        if (empty($to) || empty($message)) {
            Log::warning('❌ WhatsApp message not sent: missing to/text', [
                'to' => $to,
                'message' => $message,
            ]);
            return false;
        }

        $response = retry(3, function () use ($to, $message) {
            return Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . config('services.wasender.key'),
                'Content-Type'  => 'application/json',
            ])->post(config('services.wasender.url'), [
                'to'   => $to,
                'text' => $message,
            ]);
        }, 500); // retry 3 times, wait 500ms between tries


        if (!$response->successful()) {
            Log::error('❌ Failed to send WhatsApp message', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } else {
            Log::info('✅ WhatsApp message sent', [
                'to' => $to,
                'length' => strlen($message),
            ]);
        }

        return $response->successful();
    }
}

