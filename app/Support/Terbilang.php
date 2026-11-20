<?php

namespace App\Support;

class Terbilang
{
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Konversi bilangan bulat non-negatif menjadi teks terbilang Bahasa Indonesia.
     */
    public static function convert(int|float|string $angka): string
    {
        $nilai = (int) floor(abs((float) $angka));

        if ($nilai === 0) {
            return 'nol';
        }

        return trim(preg_replace('/\s+/', ' ', self::terbilangInt($nilai)));
    }

    /**
     * Format terbilang lengkap dengan akhiran "rupiah" (huruf awal kapital).
     */
    public static function rupiah(int|float|string $angka): string
    {
        return ucfirst(self::convert($angka)) . ' rupiah';
    }

    private static function terbilangInt(int $n): string
    {
        if ($n < 12) {
            return self::SATUAN[$n];
        }
        if ($n < 20) {
            return self::terbilangInt($n - 10) . ' belas';
        }
        if ($n < 100) {
            return self::terbilangInt(intdiv($n, 10)) . ' puluh ' . self::terbilangInt($n % 10);
        }
        if ($n < 200) {
            return 'seratus ' . self::terbilangInt($n - 100);
        }
        if ($n < 1000) {
            return self::terbilangInt(intdiv($n, 100)) . ' ratus ' . self::terbilangInt($n % 100);
        }
        if ($n < 2000) {
            return 'seribu ' . self::terbilangInt($n - 1000);
        }
        if ($n < 1000000) {
            return self::terbilangInt(intdiv($n, 1000)) . ' ribu ' . self::terbilangInt($n % 1000);
        }
        if ($n < 1000000000) {
            return self::terbilangInt(intdiv($n, 1000000)) . ' juta ' . self::terbilangInt($n % 1000000);
        }
        if ($n < 1000000000000) {
            return self::terbilangInt(intdiv($n, 1000000000)) . ' miliar ' . self::terbilangInt($n % 1000000000);
        }

        return self::terbilangInt(intdiv($n, 1000000000000)) . ' triliun ' . self::terbilangInt($n % 1000000000000);
    }
}
