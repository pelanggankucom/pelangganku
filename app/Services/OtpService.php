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
        $bar       = $this->progressBar($stampsCurrent, $cardSize);
        $sisaTeks  = $sisaKartu > 0
            ? "_Kartu penuh dalam {$sisaKartu} stempel lagi_"
            : "_Kartu kamu sudah penuh! 🎉_";

        $hadiahLines = [];
        if (!empty($rewards)) {
            $hadiahLines[] = "";
            $hadiahLines[] = "🎁 *Hadiah yang bisa kamu dapatkan:*";
            foreach ($rewards as $r) {
                $kurang = max(0, $r['milestone'] - $stampsCurrent);
                if ($r['claimed']) {
                    $hadiahLines[] = "✅ _{$r['name']}_ (stempel ke-{$r['milestone']}) — sudah diklaim";
                } elseif ($kurang === 0) {
                    $hadiahLines[] = "🌟 *{$r['name']}* (stempel ke-{$r['milestone']}) — *BISA KLAIM SEKARANG!*";
                } else {
                    $hadiahLines[] = "• _{$r['name']}_ (stempel ke-{$r['milestone']}) — kurang {$kurang} lagi";
                }
            }
        }
        $hadiah = implode("\n", $hadiahLines);

        $tpl = $this->randomStampTemplate();

        $message = str_replace(
            ['{nama}', '{jumlah}', '{merchant}', '{saat_ini}', '{total}', '{sisa}', '{bar}', '{hadiah}'],
            [$customerName, $stampsAdded, $merchantName, $stampsCurrent, $cardSize, $sisaTeks, $bar, $hadiah],
            $tpl
        );

        $this->send($canonicalPhone, $message);
    }

    private function randomStampTemplate(): string
    {
        $t = [
            "Halo *{nama}* 👋\nStempel kamu di _{merchant}_ bertambah *{jumlah}*!\n\n🎯 *Koleksi: {saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir untuk klaim hadiah_ 😊",
            "✅ Stempel berhasil!\n\nHai *{nama}*, kamu baru dapat *{jumlah} stempel* di _{merchant}_.\n\n📊 Progres kamu: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Kasir siap bantu kamu tukar hadiah_ 🎁",
            "Yeay *{nama}*! 🎉\nStempel dari _{merchant}_ sudah masuk nih.\n\n🎯 Total stempelmu: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Simpan pesan ini ya, buat klaim hadiahmu!_ 😄",
            "Makasih udah mampir, *{nama}*! 🙏\nKamu baru dapat *{jumlah} stempel* di _{merchant}_.\n\n📌 Stempel: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ ✨",
            "Stempel masuk, *{nama}*! ⭐\nDapat *{jumlah}* di _{merchant}_ — mantap!\n\n🗂️ Koleksi: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 😊",
            "Kepada *{nama}*,\nTerima kasih atas kunjungan Anda di _{merchant}_.\n\nStempel sebanyak *{jumlah}* telah berhasil dicatat.\n\n📊 Total stempel: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir untuk penukaran hadiah._",
            "Terima kasih, *{nama}*!\nKunjungan Anda di _{merchant}_ telah dicatat.\n\nStempel diterima: *{jumlah}*\nTotal: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Harap tunjukkan pesan ini kepada kasir untuk klaim hadiah._",
            "Notifikasi stempel untuk *{nama}*\n\n_{merchant}_ mencatat *{jumlah} stempel* baru untuk Anda.\n\n🎯 Terkumpul: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir._",
            "🌟 *+{jumlah} STEMPEL!*\n\nSelamat *{nama}*! Stempel kamu di _{merchant}_ bertambah!\n\n💪 Progres: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Kasir bisa bantu kamu tukar hadiah_ 🎊",
            "🏆 Level up, *{nama}*!\nKamu dapat *{jumlah} stempel* di _{merchant}_!\n\n🎯 Stempel: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir buat tukar hadiahmu!_ 🥳",
            "🔥 *{jumlah} stempel* baru untuk *{nama}*!\nDari: _{merchant}_\n\n📈 Progress: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 😎",
            "Hai *{nama}*! 👋\n_{merchant}_ baru saja menambahkan *{jumlah} stempel* ke kartu kamu.\n\nSekarang kamu punya *{saat_ini} dari {total} stempel*:\n{bar}\n{sisa}{hadiah}\n\n_Simpan pesan ini dan tunjukkan ke kasir_ 🎁",
            "Kabar baik, *{nama}*! 📣\nKamu baru kunjungi _{merchant}_ dan dapat *{jumlah} stempel*!\n\nStatus kartu: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 😊",
            "Kamu semakin dekat, *{nama}*! 🚀\n*{jumlah} stempel* baru dari _{merchant}_ sudah masuk!\n\n📊 Koleksimu: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 🎁",
            "*{nama}* — _{merchant}_\n\n✅ +{jumlah} stempel\n🎯 {saat_ini}/{total} terkumpul\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 😊",
            "📌 Catatan stempel untuk *{nama}*\n\nMerchant: _{merchant}_\nStempel hari ini: +{jumlah}\nTotal: {saat_ini} dari {total}\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir_",
            "_{merchant}_ ✅\n\nHalo *{nama}*, stempel kamu bertambah *{jumlah}*!\n\n🎯 {saat_ini}/{total} stempel\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir untuk tukar hadiah_ 🎁",
            "_{merchant}_ mengucapkan terima kasih, *{nama}*! 🙏\n\nStempel kamu bertambah *{jumlah}*.\nTotal: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ ✨",
            "Dari _{merchant}_ untuk *{nama}* 💌\n\nStempel +{jumlah} sudah kami catat!\n\n📊 Stempel kamu: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir ya!_ 😄",
            "Tim _{merchant}_ senang melayani kamu, *{nama}*! 😊\n\nStempel kamu bertambah *{jumlah}* — terimakasih!\n\n🎯 Koleksi: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 🎊",
            "Update stempel *{nama}* 📊\n\n_{merchant}_ → +{jumlah} stempel\n\nProgres kartu:\n🎯 *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 😊",
            "Stempel kamu bertambah, *{nama}*! 📈\n+{jumlah} dari _{merchant}_\n\nBegini progres kartumu:\n{saat_ini}/{total} ➜ {bar}\n{sisa}{hadiah}\n\n_Kasir siap bantu klaim hadiah kamu_ 🎁",
            "Progres stempel *{nama}* diperbarui! 🔄\n\n_{merchant}_ → *+{jumlah}*\n\n🎯 Total: {saat_ini}/{total}\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ ✨",
            "Halo *{nama}*! Kamu makin dekat ke hadiahmu! 🎁\n\nStempel dari _{merchant}_: +{jumlah}\nTotal: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim_ 😊",
            "Satu langkah lebih dekat ke hadiah, *{nama}*! 🌟\n\n+{jumlah} stempel dari _{merchant}_\nTerkumpul: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 🎊",
            "Stempelmu nambah lagi, *{nama}*! 🥳\nAsik! Dapat *{jumlah}* di _{merchant}_!\n\n🎯 Sekarang punya: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir ya, biar bisa klaim!_ 😄",
            "Wuih *{nama}*, stempel kamu makin banyak! ⭐\n*{jumlah} stempel* baru dari _{merchant}_!\n\n📊 Status: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 🎁",
            "Mantap *{nama}*! 👏\nKunjungan di _{merchant}_ = *{jumlah} stempel* buat kamu!\n\n🎯 Koleksi stempel: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Kasir bisa bantu tukar hadiahmu_ 😊",
            "Makasih sudah setia sama _{merchant}_, *{nama}*! 💛\n\nStempel kamu: +{jumlah}\nTotal: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 🎁",
            "Kesetiaan kamu dihargai, *{nama}*! 💝\n_{merchant}_ memberikan *{jumlah} stempel* untuk kamu.\n\n🏅 Koleksi: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ ✨",
            "Terima kasih sudah datang, *{nama}*! 🙌\n*{jumlah} stempel* dari _{merchant}_ sudah kamu dapat!\n\n📌 Total: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 😊",
            "✅ *+{jumlah} stempel untuk {nama}!*\n_{merchant}_\n\n{saat_ini}/{total} terkumpul\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir_ 🎁",
            "🎉 *{nama}* dapat *{jumlah} stempel*!\n_{merchant}_\n\n📊 {saat_ini} dari {total}\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk hadiah_ 😊",
            "✨ Stempel +{jumlah} untuk *{nama}*\nDari _{merchant}_\n\n🎯 {saat_ini}/{total}\n{bar}\n{sisa}{hadiah}\n\n_Kasir siap bantu kamu!_ 🎁",
            "Senang kamu mampir lagi, *{nama}*! 😊\n_{merchant}_ menambahkan *{jumlah} stempel* untuk kamu.\n\n🎯 Stempel kamu: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 💛",
            "Kami selalu senang melihat kamu, *{nama}*! 🤗\n+{jumlah} stempel dari _{merchant}_ sudah masuk!\n\n📊 Total stempel: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 🎊",
            "Sampai jumpa lagi, *{nama}*! 👋\nStempel dari _{merchant}_ sudah kami catat — *+{jumlah}*!\n\n🎯 Progres: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 💝",
            "🏆 *Pencapaian baru, {nama}!*\n\nKamu baru dapat *{jumlah} stempel* di _{merchant}_.\nTotal: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 🎊",
            "🌟 *{nama}*, stempelmu terus bertambah!\n\n+{jumlah} dari _{merchant}_\n🎯 {saat_ini}/{total} stempel terkumpul\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ ✨",
            "📣 Selamat *{nama}*!\nKamu berhasil dapat *{jumlah} stempel* di _{merchant}_!\n\n🎯 Status: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 🎁",
            "INFO STEMPEL — *{nama}*\n\n✅ Lokasi: _{merchant}_\n✅ Stempel: +{jumlah}\n✅ Total: {saat_ini}/{total}\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 😊",
            "📋 Laporan stempel *{nama}*\n\nMerchant: _{merchant}_\nDitambahkan: +{jumlah} stempel\nTotal sekarang: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir_ 🎁",
            "Hai *{nama}*! 🌟 Just FYI, _{merchant}_ baru tambahin *{jumlah} stempel* ke kartumu!\n\n🎯 Total: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah ya!_ 😄",
            "Hey *{nama}*! 👋 _{merchant}_ nambah *{jumlah} stempel* buat kamu nih!\n\n📊 Status kartu: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir_ 🎊",
            "Kamu keren, *{nama}*! 💪\nStempel dari _{merchant}_: *+{jumlah}* masuk!\n\n🎯 Koleksi: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir untuk tukar hadiah_ 🎁",
            "⏳ Semakin dekat, *{nama}*!\n\n+{jumlah} stempel dari _{merchant}_\nKartu kamu: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 🎊",
            "🎯 Kamu lagi on track, *{nama}*!\n_{merchant}_ → +{jumlah} stempel\n\nTotal terkumpul: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Kasir siap tukar hadiah kamu!_ ✨",
            "Keep it up, *{nama}*! 🔥\n*{jumlah} stempel* dari _{merchant}_ sudah kami catat.\n\n📊 Progres: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk tukar hadiah_ 😊",
            "Halo *{nama}*! Stempelmu bertambah nih 🎉\n\n_{merchant}_ → *+{jumlah}*\n\n🎯 Total stempel: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir yuk!_ 💛",
            "Hei *{nama}*, ada kabar baik! 📬\nStempelmu di _{merchant}_ bertambah *{jumlah}* nih!\n\n🎯 Progres: *{saat_ini}/{total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan ke kasir untuk klaim hadiah_ 🌟",
            "Hai *{nama}*! Kunjunganmu tercatat 📍\n_{merchant}_ → *+{jumlah} stempel*\n\nTotal: *{saat_ini} dari {total}*\n{bar}\n{sisa}{hadiah}\n\n_Tunjukkan pesan ini ke kasir_ 🎁",
        ];

        return $t[array_rand($t)];
    }

    private function randomOtpMessage(string $otp): string
    {
        $brand   = 'Pelangganku';
        $trx     = $this->buildTrxId();
        $tanggal = now()->locale('id')->translatedFormat('d M Y, H:i');

        $templates = [
            "Kode verifikasi Anda untuk {brand} adalah {otp}. (ID: {trx} - {tgl})",
            "Rahasia: Gunakan kode rahasia {otp} untuk masuk ke akun {brand} Anda. Ref: {trx} pada {tgl}.",
            "{brand}: Masukkan kode {otp} untuk memverifikasi transaksi {trx} Anda tanggal {tgl}.",
            "Halo! Ini kode akses Anda: {otp}. Valid untuk transaksi {trx} per {tgl}.",
            "Demi keamanan {brand}, jangan bagikan kode {otp} ini. {trx} / {tgl}.",
            "Kode keamanan Anda adalah {otp}. Masuk ke {brand} sekarang. No. Ref: {trx} - {tgl}.",
            "Deteksi aktivitas masuk {brand} pada {tgl}. Masukkan kode {otp} untuk konfirmasi. ID: {trx}.",
            "Verifikasi Akun {brand}: Kode Anda {otp}. Jangan berikan ke siapa pun. Log: {trx} ({tgl}).",
            "Selesaikan proses pendaftaran {brand} Anda dengan kode {otp}. ID Transaksi: {trx} / {tgl}.",
            "Kode masuk Anda hari ini adalah {otp}. Selalu jaga kerahasiaan data Anda bersama {brand}. {trx} - {tgl}.",
            "{brand} verifikasi: {otp}. Berlaku 5 menit untuk transaksi {trx} pada {tgl}.",
            "Gunakan akses kode {otp} untuk melanjutkan di {brand}. Kode Ref: {trx} ({tgl}).",
            "Konfirmasi login {brand} terdeteksi. Gunakan kode: {otp}. Keamanan ID: {trx} per {tgl}.",
            "Masuk ke {brand} dengan aman. Kode rahasia Anda: {otp}. Ref: {trx} - {tgl}.",
            "Waspada Penipuan! Kode keamanan {brand} Anda adalah {otp}. ID Sesi: {trx}, Waktu: {tgl}.",
            "Akun {brand} Anda dilindungi. Masukkan kode {otp} untuk verifikasi {trx} per {tgl}.",
            "Lindungi data Anda. Gunakan kode verifikasi {otp} di {brand}. Ref: {trx} ({tgl}).",
            "Untuk keamanan transaksi {trx} di {brand}, silakan pakai kode: {otp} ({tgl}).",
            "Sesi aman {brand}: Gunakan {otp} untuk transaksi {trx} Anda pada {tgl}.",
            "Kode konfirmasi untuk {brand} Anda: {otp}. Jika ini bukan Anda, abaikan. ID: {trx} - {tgl}.",
            "Your verification code for {brand} is {otp}. (Ref ID: {trx} at {tgl}).",
            "Security alert {brand}: Use code {otp} to authorize transaction {trx} on {tgl}.",
            "{brand} Passcode: {otp}. Valid for request {trx} ({tgl}).",
            "Enter code {otp} to complete your registration at {brand}. ID: {trx} - {tgl}.",
            "Access code for {brand}: {otp}. Our team never asks for this. Ref: {trx} ({tgl}).",
            "Authorized transaction {trx} on {brand} requires code {otp}. Timestamp: {tgl}.",
            "{brand} Security: Input {otp} for verif {trx}. Generated on {tgl}.",
            "Verification successful requires this code: {otp}. {brand} Svc ID: {trx} - {tgl}.",
            "Confirm your {brand} action with code: {otp}. Transaction: {trx} / {tgl}.",
            "Kode sekali pakai {brand} Anda adalah {otp}. No. Seri: {trx} per {tgl}.",
            "Aktivitas finansial {brand} memerlukan verifikasi. Masukkan {otp}. ID: {trx} ({tgl}).",
            "Konfirmasi perubahan data {brand} Anda dengan kode ini: {otp}. Trx: {trx} pada {tgl}.",
            "{brand} Sistem: Masukkan kode {otp} untuk memproses permintaan {trx} Anda ({tgl}).",
            "Jaga keamanan digital bersama {brand}. Kode masuk Anda: {otp}. Ref: {trx} - {tgl}.",
            "Validasi akun {brand} terdaftar. Gunakan PIN ini: {otp}. ID Tiket: {trx} ({tgl}).",
            "Selesaikan pembayaran/akses Anda di {brand} dengan kode: {otp}. ID: {trx} - {tgl}.",
            "Kode autentikasi dua faktor {brand} Anda: {otp}. Log transaksi: {trx} / {tgl}.",
            "Masuk {brand} berhasil dikonfirmasi menggunakan kode {otp}. Ref: {trx} pada {tgl}.",
            "Permintaan kode {brand} untuk nomor Anda. Kode: {otp}. ID Laporan: {trx} ({tgl}).",
            "Konfirmasi Profil {brand}: Input {otp} untuk menyetujui. ID Transaksi: {trx} - {tgl}.",
            "{brand} - Kode akses Anda adalah {otp}. Segera gunakan untuk sesi {trx} per {tgl}.",
            "Gunakan kombinasi angka {otp} untuk verifikasi instan {brand}. Trx Ref: {trx} ({tgl}).",
            "Jangan sebutkan kode {otp} ke pihak lain demi keamanan akun {brand} Anda. ID: {trx} - {tgl}.",
            "Kode aktivasi {brand} Anda siap: {otp}. Diproses otomatis untuk {trx} pada {tgl}.",
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
