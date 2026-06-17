<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OtpService
{
    private ?string $apiKey;
    private ?string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.otpcepat.api_key');
        $this->apiUrl = config('services.otpcepat.api_url') ?? 'https://otpcepat.org/api/handler_api.php';
    }

    /**
     * Kirim OTP ke nomor WhatsApp (format kanonik: 628xxxxxxxxx)
     */
    public function sendOtp(string $canonicalPhone): array
    {
        // Check API key tersedia
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'OTP service tidak terkonfigurasi. Hubungi administrator.'];
        }

        // Validasi format kanonik
        if (!preg_match('/^628[1-9][0-9]{7,11}$/', $canonicalPhone)) {
            return ['success' => false, 'message' => 'Format nomor HP tidak valid'];
        }

        // Generate 6-digit OTP
        $otp = Str::padLeft(random_int(0, 999999), 6, '0');

        // Format untuk WhatsApp: +62xxxxxxxxx
        $whatsappPhone = '+' . $canonicalPhone;

        // Store OTP di database
        OtpVerification::updateOrCreate(
            ['phone_canonical' => $canonicalPhone],
            [
                'otp_code' => $otp,
                'attempt' => 0,
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
            ]
        );

        // Kirim via WhatsApp
        $result = $this->sendWhatsAppMessage(
            $whatsappPhone,
            "Kode OTP Pelangganku Anda: *{$otp}*\n\nKode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapapun."
        );

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke WhatsApp Anda',
                'expires_in' => 600, // 10 menit dalam detik
            ];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Gagal mengirim OTP'];
    }

    /**
     * Verifikasi kode OTP
     */
    public function verifyOtp(string $canonicalPhone, string $code): array
    {
        $verification = OtpVerification::where('phone_canonical', $canonicalPhone)
            ->where('verified_at', null)
            ->latest()
            ->first();

        if (!$verification) {
            return ['success' => false, 'message' => 'OTP tidak ditemukan atau sudah digunakan'];
        }

        if ($verification->expires_at < now()) {
            return ['success' => false, 'message' => 'OTP telah kadaluarsa'];
        }

        if ($verification->attempt >= 3) {
            return ['success' => false, 'message' => 'Terlalu banyak percobaan. Silakan minta OTP baru.'];
        }

        if ($verification->otp_code !== $code) {
            $verification->increment('attempt');
            return ['success' => false, 'message' => 'Kode OTP tidak sesuai'];
        }

        $verification->update(['verified_at' => now()]);
        return ['success' => true, 'message' => 'OTP berhasil diverifikasi'];
    }

    /**
     * Check balance API OTCEPAT
     */
    public function checkBalance(): array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getBalance',
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubungi API'];
            }

            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'true') {
                return [
                    'success' => true,
                    'email' => $data['data']['email'] ?? null,
                    'balance' => $data['data']['saldo'] ?? 0,
                ];
            }

            return ['success' => false, 'message' => $data['message'] ?? 'API error'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function sendWhatsAppMessage(string $phone, string $message): array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl, [
                'api_key' => $this->apiKey,
                'action' => 'sendMessage',
                'phone' => $phone,
                'message' => $message,
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubungi API'];
            }

            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'true') {
                return ['success' => true, 'message_id' => $data['data'] ?? null];
            }

            return ['success' => false, 'message' => $data['message'] ?? 'Gagal mengirim pesan'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
