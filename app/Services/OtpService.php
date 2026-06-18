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

    public function sendOtp(string $canonicalPhone): array
    {
        if (!$this->apiToken) {
            return ['success' => false, 'message' => 'OTP service tidak terkonfigurasi. Hubungi administrator.'];
        }

        if (!preg_match('/^628[1-9][0-9]{7,11}$/', $canonicalPhone)) {
            return ['success' => false, 'message' => 'Format nomor HP tidak valid'];
        }

        $otp = Str::padLeft(random_int(0, 999999), 6, '0');

        OtpVerification::updateOrCreate(
            ['phone_canonical' => $canonicalPhone],
            [
                'otp_code' => $otp,
                'attempt' => 0,
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
            ]
        );

        $result = $this->send(
            $canonicalPhone,
            "Kode OTP Pelangganku Anda: *{$otp}*\n\nKode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapapun."
        );

        if ($result['success']) {
            return ['success' => true, 'message' => 'Kode OTP telah dikirim ke WhatsApp Anda', 'expires_in' => 600];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Gagal mengirim OTP'];
    }

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
     * Kirim notifikasi stempel ke pelanggan via WhatsApp.
     *
     * @param array $rewards  [['name'=>string, 'milestone'=>int, 'claimed'=>bool], ...]
     */
    public function sendStampNotification(
        string $canonicalPhone,
        string $customerName,
        string $merchantName,
        int $stampsAdded,
        int $stampsCurrent,
        int $cardSize,
        array $rewards
    ): void {
        if (!$this->apiToken) return;

        $sisaKartu = max(0, $cardSize - $stampsCurrent);
        $progress  = $this->progressBar($stampsCurrent, $cardSize);

        $lines   = [];
        $lines[] = "✅ *Stempel berhasil ditambahkan!*";
        $lines[] = "";
        $lines[] = "Halo *{$customerName}* 👋";
        $lines[] = "Kamu baru saja dapat *{$stampsAdded} stempel* di _{$merchantName}_";
        $lines[] = "";
        $lines[] = "🎯 *Stempel kamu: {$stampsCurrent} dari {$cardSize}*";
        $lines[] = $progress;

        if ($sisaKartu > 0) {
            $lines[] = "_Kartu penuh dalam {$sisaKartu} stempel lagi_";
        } else {
            $lines[] = "_Kartu kamu sudah penuh! 🎉_";
        }

        if (!empty($rewards)) {
            $lines[] = "";
            $lines[] = "🎁 *Hadiah yang bisa kamu dapatkan:*";
            foreach ($rewards as $r) {
                $kurang = max(0, $r['milestone'] - $stampsCurrent);
                if ($r['claimed']) {
                    $lines[] = "✅ _{$r['name']}_ (stempel ke-{$r['milestone']}) — sudah diklaim";
                } elseif ($kurang === 0) {
                    $lines[] = "🌟 *{$r['name']}* (stempel ke-{$r['milestone']}) — *BISA KLAIM SEKARANG!*";
                } else {
                    $lines[] = "• _{$r['name']}_ (stempel ke-{$r['milestone']}) — kurang {$kurang} stempel lagi";
                }
            }
        }

        $lines[] = "";
        $lines[] = "_Tunjukkan pesan ini ke kasir untuk klaim hadiah_ 😊";

        $this->send($canonicalPhone, implode("\n", $lines));
    }

    private function progressBar(int $current, int $total): string
    {
        $total  = max(1, $total);
        $slots  = min($total, 20);
        $filled = (int) round($current / $total * $slots);
        return str_repeat('▓', $filled) . str_repeat('░', $slots - $filled);
    }

    private function send(string $canonicalPhone, string $message): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => $this->apiToken])
                ->post('https://api.fonnte.com/send', [
                    'target'  => $canonicalPhone,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubungi API Fonnte'];
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === true) {
                return ['success' => true, 'message_id' => $data['data']['id'] ?? null];
            }

            return ['success' => false, 'message' => $data['reason'] ?? 'Gagal mengirim pesan'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
