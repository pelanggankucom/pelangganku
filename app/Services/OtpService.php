<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OtpService
{
    private ?string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.fonnte.api_token');
    }

    /**
     * Kirim OTP ke nomor WhatsApp (format kanonik: 628xxxxxxxxx)
     */
    public function sendOtp(string $canonicalPhone): array
    {
        // Check API token tersedia
        if (!$this->apiToken) {
            return ['success' => false, 'message' => 'OTP service tidak terkonfigurasi. Hubungi administrator.'];
        }

        // Validasi format kanonik
        if (!preg_match('/^628[1-9][0-9]{7,11}$/', $canonicalPhone)) {
            return ['success' => false, 'message' => 'Format nomor HP tidak valid'];
        }

        // Generate 6-digit OTP
        $otp = Str::padLeft(random_int(0, 999999), 6, '0');

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
            $canonicalPhone,
            "Kode OTP Pelangganku Anda: *{$otp}*\n\nKode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapapun."
        );

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke WhatsApp Anda',
                'expires_in' => 600,
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

    private function sendWhatsAppMessage(string $canonicalPhone, string $message): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => $this->apiToken])
                ->post('https://api.fonnte.com/send', [
                    'target' => $canonicalPhone,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubungi API Fonnte'];
            }

            $data = $response->json();

            // Fonnte returns status: true if success
            if (isset($data['status']) && $data['status'] === true) {
                return ['success' => true, 'message_id' => $data['data']['id'] ?? null];
            }

            return ['success' => false, 'message' => $data['reason'] ?? 'Gagal mengirim pesan'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
