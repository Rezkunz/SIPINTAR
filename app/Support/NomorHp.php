<?php

namespace App\Support;

/**
 * Format baku nomor HP SIPINTAR: hanya angka, diawali 0 (mis. 081234567890).
 * "+62 812-3456-7890", "62812...", dan "0812 3456 7890" dianggap nomor yang sama.
 */
final class NomorHp
{
    public static function normalisasi(?string $nomor): string
    {
        $angka = preg_replace('/\D/', '', (string) $nomor);

        if (str_starts_with($angka, '62')) {
            $angka = '0' . substr($angka, 2);
        } elseif ($angka !== '' && str_starts_with($angka, '8')) {
            $angka = '0' . $angka;
        }

        return $angka;
    }
}
