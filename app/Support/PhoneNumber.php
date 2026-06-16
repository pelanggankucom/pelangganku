<?php

namespace App\Support;

/**
 * Normalisasi & validasi nomor telepon Indonesia ke bentuk kanonik E.164
 * tanpa tanda '+', mis. 081234567890 -> 6281234567890.
 */
class PhoneNumber
{
    /** Ubah berbagai format input menjadi bentuk kanonik (628xxxxxxxxx) atau null jika tidak valid. */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Buang semua karakter selain angka dan '+'.
        $value = preg_replace('/[^0-9+]/', '', $raw);

        if ($value === '' || $value === null) {
            return null;
        }

        // +62..., 62..., 0..., 8...
        if (str_starts_with($value, '+')) {
            $value = substr($value, 1);
        }

        if (str_starts_with($value, '0')) {
            $value = '62' . substr($value, 1);
        } elseif (str_starts_with($value, '8')) {
            $value = '62' . $value;
        }

        return self::isValid($value) ? $value : null;
    }

    /** Apakah nomor (yang sudah kanonik) valid untuk Indonesia. */
    public static function isValid(string $canonical): bool
    {
        return (bool) preg_match('/^628[1-9][0-9]{7,11}$/', $canonical);
    }
}
