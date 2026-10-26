<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifier
{
    /**
     * Kirim pesan ke Slack lewat Incoming Webhook. No-op kalau webhook belum diset.
     * Dibungkus try/catch + timeout supaya tidak pernah menggagalkan proses utama.
     */
    public static function send(string $text): void
    {
        $url = config('services.slack.webhook');

        if (empty($url)) {
            return;
        }

        try {
            Http::timeout(4)->post($url, ['text' => $text]);
        } catch (\Throwable $e) {
            Log::warning('Slack notify gagal: '.$e->getMessage());
        }
    }
}
