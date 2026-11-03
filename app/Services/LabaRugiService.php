<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan laba rugi Bank Mini per bulan.
 * Dipakai Laporan Keuangan Tahunan dan Neraca (laba tahun berjalan) agar angkanya identik.
 *
 * Pendapatan = jasa angsuran pinjaman yang diterima + kas manual bendahara masuk.
 * Beban      = jasa tabungan (JasaTabunganService) + kas manual bendahara keluar.
 */
class LabaRugiService
{
    public const KOLOM = ['jasa_pinjaman', 'pendapatan_lain', 'jasa_siswa', 'jasa_guru', 'beban_operasional', 'pendapatan', 'beban', 'laba'];

    /**
     * @param  int  $sampaiBulan  bulan terakhir yang dihitung (0 = belum ada); bulan sesudahnya bernilai nol
     * @return array{bulanan: \Illuminate\Support\Collection, total: array, kasManual: \Illuminate\Support\Collection}
     */
    public static function hitung(int $tahun, int $sampaiBulan): array
    {
        $awalTahun = Carbon::create($tahun, 1, 1)->startOfDay();
        $batas     = $sampaiBulan > 0
            ? Carbon::create($tahun, $sampaiBulan, 1)->endOfMonth()
            : $awalTahun->copy()->subDay();

        // Jasa angsuran pinjaman per bulan (angsuran yang dikoreksi tidak dihitung)
        $jasaPinjaman = DB::table('tblANGSURAN as ang')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereNull('kor.koreksi_id')
            ->whereBetween('ang.tgl_bayar', [$awalTahun->toDateString(), $batas->toDateString()])
            ->get(['ang.tgl_bayar', 'ang.bayar_bunga'])
            ->groupBy(fn ($a) => Carbon::parse($a->tgl_bayar)->month)
            ->map(fn ($grup) => (float) $grup->sum('bayar_bunga'));

        // Kas manual bendahara: bukan angsuran, pencairan, transaksi tabungan, maupun paket potong gaji
        $kasManual = DB::table('tblMUTASI_KAS as mk')
            ->join('tblKAS as k', 'mk.kas_id', '=', 'k.kas_id')
            ->whereNull('mk.angsuran_id')
            ->whereNull('mk.pencairan_id')
            ->whereNull('mk.transaksi_id')
            ->where(function ($q) {
                $q->whereNull('mk.uraian')->orWhere('mk.uraian', 'not like', '%potongan gaji paket #RP-%');
            })
            ->whereBetween('mk.tgl_mutasi', [$awalTahun->toDateString(), $batas->toDateString()])
            ->orderBy('mk.tgl_mutasi')
            ->orderBy('mk.mutasi_kas_id')
            ->get(['mk.mutasi_kas_id', 'mk.tgl_mutasi', 'mk.arah', 'mk.nominal', 'mk.uraian', 'k.jenis_kas']);

        $kasManualPerBulan = $kasManual->groupBy(fn ($k) => Carbon::parse($k->tgl_mutasi)->month);

        $bulanan = collect(range(1, 12))->map(function ($bulan) use ($tahun, $sampaiBulan, $jasaPinjaman, $kasManualPerBulan) {
            $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $kas       = $kasManualPerBulan->get($bulan, collect());

            $b = (object) [
                'bulan'             => $bulan,
                'nama_bulan'        => $awalBulan->translatedFormat('F'),
                'dihitung'          => $bulan <= $sampaiBulan,
                'jasa_pinjaman'     => (float) ($jasaPinjaman[$bulan] ?? 0),
                'pendapatan_lain'   => (float) $kas->where('arah', 'masuk')->sum('nominal'),
                'beban_operasional' => (float) $kas->where('arah', 'keluar')->sum('nominal'),
                'jasa_siswa'        => 0.0,
                'jasa_guru'         => 0.0,
            ];

            // Beban jasa tabungan hanya untuk bulan yang sudah berjalan
            if ($b->dihitung) {
                $akhirBulan    = $awalBulan->copy()->endOfMonth();
                $b->jasa_siswa = (float) JasaTabunganService::rekapBulanan('siswa', $awalBulan, $akhirBulan)->sum('jasa');
                $b->jasa_guru  = (float) JasaTabunganService::rekapBulanan('guru', $awalBulan, $akhirBulan)->sum('jasa');
            }

            $b->pendapatan = $b->jasa_pinjaman + $b->pendapatan_lain;
            $b->beban      = $b->jasa_siswa + $b->jasa_guru + $b->beban_operasional;
            $b->laba       = $b->pendapatan - $b->beban;

            return $b;
        });

        $total = [];
        foreach (self::KOLOM as $kolom) {
            $total[$kolom] = (float) $bulanan->sum($kolom);
        }

        return ['bulanan' => $bulanan, 'total' => $total, 'kasManual' => $kasManual];
    }
}
