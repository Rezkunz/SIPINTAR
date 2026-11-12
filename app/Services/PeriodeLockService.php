<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeriodeLockService
{
    /**
     * Pastikan periode pada tanggal tertentu tidak terkunci/tutup.
     * Melempar RuntimeException jika periode telah ditutup oleh Manajer.
     *
     * @param string|Carbon $tanggal
     * @return object
     * @throws RuntimeException
     */
    public static function assertPeriodeTerbuka(string|Carbon $tanggal): object
    {
        $dt = Carbon::parse($tanggal);
        $tahun = $dt->year;
        $bulan = $dt->month;

        $periode = DB::table('tblPERIODE')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        // Jika periode ada dan berstatus 'tutup', tolak seluruh transaksi mutasi
        if ($periode && $periode->status_periode === 'tutup') {
            throw new RuntimeException("Transaksi ditolak: Periode {$bulan}/{$tahun} telah ditutup oleh Manajer dan terkunci.");
        }

        // Jika periode belum ada sama sekali, buat periode terbuka secara otomatis
        if (!$periode) {
            $periodeId = DB::table('tblPERIODE')->insertGetId([, 'periode_id')
                'user_id'        => auth()->id(),
                'tahun'          => $tahun,
                'bulan'          => $bulan,
                'status_periode' => 'buka',
                'tgl_tutup'      => null,
            ]);
            $periode = DB::table('tblPERIODE')->where('periode_id', $periodeId)->first();
        }

        return $periode;
    }

    /**
     * Dapatkan objek periode aktif pada tanggal tertentu.
     */
    public static function getPeriode(string|Carbon $tanggal): object
    {
        return self::assertPeriodeTerbuka($tanggal);
    }
}

