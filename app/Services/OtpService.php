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
            $this->randomOtpMessage($otp)
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

    private function randomOtpMessage(string $otp): string
    {
        $brand   = 'Pelangganku';
        $trx     = $this->buildTrxId();
        $tanggal = now()->locale('id')->translatedFormat('d M Y, H:i');

        $templates = [
            "Kode verifikasi Anda untuk {brand} adalah {otp}. (ID: {trx} - {tgl})",
            "Rahasia: Gunakan OTP {otp} untuk masuk ke akun {brand} Anda. Ref: {trx} pada {tgl}.",
            "{brand}: Masukkan kode {otp} untuk memverifikasi transaksi {trx} Anda tanggal {tgl}.",
            "Halo! Ini kode OTP Anda: {otp}. Valid untuk transaksi {trx} per {tgl}.",
            "Demi keamanan {brand}, jangan bagikan kode {otp} ini. {trx} / {tgl}.",
            "Kode keamanan Anda adalah {otp}. Masuk ke {brand} sekarang. No. Ref: {trx} - {tgl}.",
            "Deteksi aktivitas masuk {brand} pada {tgl}. Masukkan kode {otp} untuk konfirmasi. ID: {trx}.",
            "Verifikasi Akun {brand}: Kode Anda {otp}. Jangan berikan ke siapa pun. Log: {trx} ({tgl}).",
            "Selesaikan proses pendaftaran {brand} Anda dengan kode {otp}. ID Transaksi: {trx} / {tgl}.",
            "OTP Anda hari ini adalah {otp}. Selalu jaga kerahasiaan data Anda bersama {brand}. {trx} - {tgl}.",
            "{brand} OTP: {otp}. Berlaku 5 menit untuk verifikasi {trx} pada {tgl}.",
            "Gunakan akses kode {otp} untuk melanjutkan di {brand}. Kode Ref: {trx} ({tgl}).",
            "Konfirmasi login {brand} terdeteksi. Gunakan kode: {otp}. Keamanan ID: {trx} per {tgl}.",
            "Masuk ke {brand} dengan aman. Kode rahasia Anda: {otp}. Ref: {trx} - {tgl}.",
            "Waspada Penipuan! OTP {brand} Anda adalah {otp}. ID Sesi: {trx}, Waktu: {tgl}.",
            "Akun {brand} Anda dilindungi. Masukkan kode {otp} untuk verifikasi {trx} per {tgl}.",
            "Lindungi data Anda. Gunakan kode verifikasi {otp} di {brand}. Ref: {trx} ({tgl}).",
            "Untuk keamanan transaksi {trx} di {brand}, silakan pakai kode: {otp} ({tgl}).",
            "Sesi aman {brand}: Gunakan {otp} untuk transaksi {trx} Anda pada {tgl}.",
            "OTP untuk {brand} Anda: {otp}. Jika ini bukan Anda, abaikan. ID: {trx} - {tgl}.",
            "Your verification code for {brand} is {otp}. (Ref ID: {trx} at {tgl}).",
            "Security alert {brand}: Use code {otp} to authorize transaction {trx} on {tgl}.",
            "{brand} One-Time Password: {otp}. Valid for request {trx} ({tgl}).",
            "Enter code {otp} to complete your registration at {brand}. ID: {trx} - {tgl}.",
            "Access code for {brand}: {otp}. Our team never asks for this. Ref: {trx} ({tgl}).",
            "Authorized transaction {trx} on {brand} requires code {otp}. Timestamp: {tgl}.",
            "{brand} Security: Input {otp} for verif {trx}. Generated on {tgl}.",
            "Verification successful requires this code: {otp}. {brand} Svc ID: {trx} - {tgl}.",
            "Confirm your {brand} action with code: {otp}. Transaction: {trx} / {tgl}.",
            "Kode sekali pakai {brand} Anda adalah {otp}. No. Seri: {trx} per {tgl}.",
            "Aktivitas finansial {brand} memerlukan verifikasi. Masukkan {otp}. ID: {trx} ({tgl}).",
            "Konfirmasi perubahan data {brand} Anda dengan OTP: {otp}. Trx: {trx} pada {tgl}.",
            "{brand} Sistem: Masukkan kode {otp} untuk memproses permintaan {trx} Anda ({tgl}).",
            "Jaga keamanan digital bersama {brand}. Kode masuk Anda: {otp}. Ref: {trx} - {tgl}.",
            "Validasi akun {brand} terdaftar. Gunakan OTP: {otp}. ID Tiket: {trx} ({tgl}).",
            "Selesaikan pembayaran/akses Anda di {brand} dengan kode: {otp}. ID: {trx} - {tgl}.",
            "Kode autentikasi dua faktor {brand} Anda: {otp}. Log transaksi: {trx} / {tgl}.",
            "Masuk {brand} berhasil dikonfirmasi menggunakan kode {otp}. Ref: {trx} pada {tgl}.",
            "Permintaan OTP {brand} untuk nomor Anda. Kode: {otp}. ID Laporan: {trx} ({tgl}).",
            "Konfirmasi Profil {brand}: Input {otp} untuk menyetujui. ID Transaksi: {trx} - {tgl}.",
            "{brand} - OTP Anda adalah {otp}. Segera gunakan untuk sesi {trx} per {tgl}.",
            "Gunakan kombinasi angka {otp} untuk verifikasi instan {brand}. Trx Ref: {trx} ({tgl}).",
            "Jangan sebutkan kode {otp} ke pihak lain demi keamanan akun {brand} Anda. ID: {trx} - {tgl}.",
            "OTP {brand} Anda siap: {otp}. Diproses otomatis untuk {trx} pada {tgl}.",
            "Sesi login {brand} aman diaktifkan. Masukkan {otp}. Nomor pelacak: {trx} ({tgl}).",
            "Kode persetujuan transaksi {brand}: {otp}. ID Penarikan/Pembayaran: {trx} - {tgl}.",
            "Pembaruan keamanan {brand}: Kode rahasia Anda adalah {otp}. No. Seri: {trx} per {tgl}.",
            "Akun {brand} meminta otorisasi {trx}. Ketik kode: {otp} ({tgl}).",
            "Verifikasi identitas Anda di {brand} selesai dengan kode: {otp}. ID: {trx} - {tgl}.",
            "{brand} mengirimkan kode keamanan sekali pakai: {otp}. Referensi Sesi: {trx} ({tgl}).",
        ];

        $tpl = $templates[array_rand($templates)];

        return str_replace(
            ['{brand}', '{otp}', '{trx}', '{tgl}'],
            [$brand,    $otp,    $trx,    $tanggal],
            $tpl
        );
    }

    private function buildTrxId(): string
    {
        return 'PLK' . now()->format('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
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
