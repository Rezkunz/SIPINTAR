<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class PinjamanCalculatorService
{
    public const DEFAULT_BUNGA_PERSEN = 2.0;

    /**
     * Hitung nominal jasa pinjaman flat per bulan berdasarkan pokok awal pinjaman.
     * Pure function.
     */
    public static function hitungJasaPerBulan(int|float $pokokAwal, float $persenBunga = self::DEFAULT_BUNGA_PERSEN): int
    {
        if ($pokokAwal <= 0) {
            return 0;
        }

        // Bunga flat 2% per bulan dari plafon awal
        return (int) round(($pokokAwal * $persenBunga) / 100);
    }

    /**
     * Hitung simulasi lengkap pinjaman.
     * Pure function.
     *
     * @return array{
     *     pokok_per_bulan: int,
     *     bunga_per_bulan: int,
     *     total_per_bulan: int,
     *     total_bunga: int,
     *     total_kewajiban: int
     * }
     */
    public static function hitungSimulasi(int|float $pokokAwal, int $tenor, float $persenBunga = self::DEFAULT_BUNGA_PERSEN): array
    {
        if ($pokokAwal <= 0) {
            throw new InvalidArgumentException('Nominal pokok pinjaman harus lebih besar dari 0.');
        }

        if ($tenor <= 0) {
            throw new InvalidArgumentException('Tenor pinjaman minimal 1 bulan.');
        }

        $bungaPerBulan = self::hitungJasaPerBulan($pokokAwal, $persenBunga);
        $pokokPerBulan = (int) floor($pokokAwal / $tenor);
        $totalPerBulan = $pokokPerBulan + $bungaPerBulan;
        $totalBunga    = $bungaPerBulan * $tenor;
        $totalKewajiban = (int) ($pokokAwal + $totalBunga);

        return [
            'pokok_per_bulan' => $pokokPerBulan,
            'bunga_per_bulan' => $bungaPerBulan,
            'total_per_bulan' => $totalPerBulan,
            'total_bunga'    => $totalBunga,
            'total_kewajiban' => $totalKewajiban,
        ];
    }

    /**
     * Bentuk baris jadwal angsuran lengkap dengan penanganan sisa pembulatan pada angsuran terakhir.
     * Pure function.
     *
     * @return array<int, array{
     *     pinjaman_id: int,
     *     angsuran_ke: int,
     *     jatuh_tempo: string,
     *     pokok: int,
     *     bunga: int,
     *     status_bayar: string
     * }>
     */
    public static function generateJadwalAngsuran(
        int $pinjamanId,
        int|float $pokokAwal,
        int $tenor,
        string $tglMulai,
        float $persenBunga = self::DEFAULT_BUNGA_PERSEN
    ): array {
        if ($tenor <= 0 || $pokokAwal <= 0) {
            throw new InvalidArgumentException('Pokok dan tenor harus bernilai positif.');
        }

        $bungaPerBulan = self::hitungJasaPerBulan($pokokAwal, $persenBunga);
        $pokokPerBulanStandard = (int) floor($pokokAwal / $tenor);
        
        $totalPokokDialokasikan = 0;
        $jadwal = [];

        for ($i = 1; $i <= $tenor; $i++) {
            $jatuhTempo = Carbon::parse($tglMulai)->addMonthsNoOverflow($i)->toDateString();

            // Pada angsuran terakhir, alokasikan sisa selisih pembulatan agar total persis sama dengan pokokAwal
            if ($i === $tenor) {
                $pokokBulanIni = (int) ($pokokAwal - $totalPokokDialokasikan);
            } else {
                $pokokBulanIni = $pokokPerBulanStandard;
                $totalPokokDialokasikan += $pokokBulanIni;
            }

            $jadwal[] = [
                'pinjaman_id'  => $pinjamanId,
                'angsuran_ke'  => $i,
                'jatuh_tempo'  => $jatuhTempo,
                'pokok'        => $pokokBulanIni,
                'bunga'        => $bungaPerBulan,
                'status_bayar' => 'belum',
            ];
        }

        return $jadwal;
    }

    /**
     * Hitung sisa pokok piutang dinamis.
     * Pure function.
     */
    public static function hitungSisaPokok(int|float $plafonPinjaman, int|float $totalPokokTerbayar): int
    {
        $sisa = (int) ($plafonPinjaman - $totalPokokTerbayar);
        return max(0, $sisa);
    }

    /**
     * Tentukan apakah pinjaman sudah lunas sempurna.
     * Pure function.
     */
    public static function isLunas(int|float $plafonPinjaman, int|float $totalPokokTerbayar): bool
    {
        return self::hitungSisaPokok($plafonPinjaman, $totalPokokTerbayar) === 0;
    }
}

