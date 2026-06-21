<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DokuService
{
    private string $clientId;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.doku.client_id', '');
        $this->apiKey   = config('services.doku.api_key', '');
        $this->baseUrl  = config('services.doku.sandbox', false)
            ? 'https://api-sandbox.doku.com'
            : 'https://api.doku.com';
    }

    /**
     * Buat sesi pembayaran DOKU JOKUL.
     * Mengembalikan ['url' => 'https://...'] atau melempar RuntimeException.
     */
    public function createPayment(array $data): array
    {
        $requestId     = (string) Str::uuid();
        $timestamp     = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $target        = '/checkout/v1/payment';

        $body = [
            'order' => [
                'amount'               => (int) $data['amount'],
                'invoice_number'       => $data['invoice_number'],
                'currency'             => 'IDR',
                'session_id'           => $requestId,
                'callback_url'         => $data['callback_url'],
                'callback_url_cancel'  => $data['callback_url_cancel'],
            ],
            'payment' => [
                'payment_due_date' => 1440, // 24 jam
            ],
            'customer' => [
                'name'  => $data['customer_name'],
                'email' => $data['customer_email'] ?? 'owner@pelangganku.local',
            ],
            'additional_info' => [
                'notify_url' => $data['notify_url'] ?? '',
            ],
        ];

        $bodyJson  = json_encode($body, JSON_UNESCAPED_SLASHES);
        $digest    = base64_encode(hash('sha256', $bodyJson, true));

        $components = implode("\n", [
            "Client-Id:{$this->clientId}",
            "Request-Id:{$requestId}",
            "Request-Timestamp:{$timestamp}",
            "Request-Target:{$target}",
            "Digest:{$digest}",
        ]);

        $signature = 'HMAC SHA256=' . base64_encode(
            hash_hmac('sha256', $components, $this->apiKey, true)
        );

        $response = Http::timeout(30)
            ->withHeaders([
                'Client-Id'         => $this->clientId,
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $signature,
                'Content-Type'      => 'application/json',
            ])
            ->send('POST', $this->baseUrl . $target, ['body' => $bodyJson]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'DOKU payment gagal: ' . $response->status() . ' — ' . $response->body()
            );
        }

        $json = $response->json();

        // DOKU mengembalikan URL di beberapa kemungkinan path
        $url = $json['response']['payment']['url']
            ?? $json['payment']['url']
            ?? $json['checkout_url']
            ?? null;

        if (!$url) {
            throw new \RuntimeException('DOKU tidak mengembalikan URL pembayaran: ' . $response->body());
        }

        return ['url' => $url, 'request_id' => $requestId, 'raw' => $json];
    }

    /**
     * Verifikasi webhook notification dari DOKU.
     * Mengembalikan true jika signature valid.
     */
    public function verifyWebhook(array $headers, string $bodyRaw): bool
    {
        $signature = $headers['doku-signature'] ?? $headers['Doku-Signature'] ?? '';
        if (!$signature) return false;

        $notifyId    = $headers['doku-notify-id']    ?? '';
        $timestamp   = $headers['doku-timestamp']    ?? '';
        $requestTarget = '/webhook/pos/doku';

        $digest = base64_encode(hash('sha256', $bodyRaw, true));

        $components = implode("\n", [
            "Client-Id:{$this->clientId}",
            "Notify-Id:{$notifyId}",
            "Timestamp:{$timestamp}",
            "Request-Target:{$requestTarget}",
            "Digest:{$digest}",
        ]);

        $expected = 'HMAC SHA256=' . base64_encode(
            hash_hmac('sha256', $components, $this->apiKey, true)
        );

        return hash_equals($expected, $signature);
    }
}
