<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan saldo pokok akhir bulan & jasa tabungan per rekening.
 * Dipakai Rekap Tabungan & Jasa Bulanan dan Laporan Keuangan Tahunan agar angkanya identik.
 */
class JasaTabunganService
{
    /**
     * Kategori tabungan → nilai tblNASABAH.jenis_nasabah & tarif jasa bulanan (%).
     */
    public const KATEGORI = [
        'siswa' => ['jenis' => 'non_pegawai', 'label' => 'Siswa',           'persen_jasa' => 0.1],
        'guru'  => ['jenis' => 'pegawai',     'label' => 'Guru / Karyawan', 'persen_jasa' => 0.2],
    ];

    public const JENIS_JASA_TABUNGAN = 3;

    /**
     * Pokok = jumlah transaksi s.d. akhir bulan, dikurangi jasa yang sudah
     * dibukukan pada bulan itu, agar jasa bulan berjalan tidak terhitung dua kali.
     *
     * @param  callable|null  $filterTambahan  dipanggil dengan query rekening (mis. filter pencarian)
     */
    public static function rekapBulanan(string $kategori, Carbon $awalBulan, Carbon $akhirBulan, ?callable $filterTambahan = null): Collection
    {
        $persenJasa = self::KATEGORI[$kategori]['persen_jasa'];

        $rekeningList = DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblNASABAH.jenis_nasabah', self::KATEGORI[$kategori]['jenis'])
            ->where('tblREKENING.status_rekening', '!=', 'pending')
            ->whereDate('tblREKENING.tgl_buka', '<=', $akhirBulan)
            ->when($filterTambahan, fn ($q) => $q->tap($filterTambahan))
            ->orderBy('tblREKENING.no_rek')
            ->select('tblREKENING.rekening_id', 'tblREKENING.no_rek', 'tblREKENING.status_rekening', 'tblNASABAH.nama_nasabah', 'tblNASABAH.kelas_unit')
            ->get();

        $rekeningIds = $rekeningList->pluck('rekening_id')->all();

        $saldoAkhir = self::saldoAkhirPerRekening($rekeningIds, $akhirBulan);

        $jasaDibukukan = DB::table('tblTRANSAKSI')
            ->whereIn('rekening_id', $rekeningIds)
            ->where('jenis_transaksi_id', self::JENIS_JASA_TABUNGAN)
            ->whereBetween('tgl_transaksi', [$awalBulan, $akhirBulan])
            ->groupBy('rekening_id')
            ->select('rekening_id', DB::raw('SUM(kredit - debit) as total'))
            ->pluck('total', 'rekening_id');

        return $rekeningList
            ->map(function ($rek) use ($saldoAkhir, $jasaDibukukan, $persenJasa) {
                $rek->jasa_dibukukan = (float) ($jasaDibukukan[$rek->rekening_id] ?? 0);
                $rek->pokok = (float) ($saldoAkhir[$rek->rekening_id] ?? 0) - $rek->jasa_dibukukan;
                // Jasa = hak nasabah bulan ini menurut tarif; jasa_dibukukan = yang benar-benar sudah masuk rekening
                $rek->jasa  = $rek->pokok > 0 ? round($rek->pokok * $persenJasa / 100) : 0.0;
                $rek->total = $rek->pokok + $rek->jasa;

                return $rek;
            })
            // Rekening yang sudah ditutup dan bersaldo nol tidak perlu direkap
            ->reject(fn ($rek) => $rek->status_rekening === 'tutup' && $rek->pokok == 0)
            ->values();
    }

    /**
     * Saldo tiap rekening s.d. batas waktu = jumlah seluruh transaksinya (kredit - debit):
     * setoran + jasa - penarikan, termasuk pembalik koreksi → [rekening_id => saldo].
     * Dihitung dari arus transaksi (bukan kolom saldo_akhir) agar selalu sama dengan Buku Kas
     * dan tidak terpengaruh saldo yang diisi langsung tanpa transaksi.
     */
    public static function saldoAkhirPerRekening(array $rekeningIds, Carbon $batas): Collection
    {
        return DB::table('tblTRANSAKSI')
            ->whereIn('rekening_id', $rekeningIds)
            ->where('tgl_transaksi', '<=', $batas)
            ->groupBy('rekening_id')
            ->select('rekening_id', DB::raw('SUM(kredit - debit) as saldo'))
            ->pluck('saldo', 'rekening_id');
    }

    /**
     * Total saldo tabungan satu kategori pada batas waktu (kewajiban Bank Mini kepada nasabah).
     */
    public static function totalSaldo(string $kategori, Carbon $batas): float
    {
        $rekeningIds = DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblNASABAH.jenis_nasabah', self::KATEGORI[$kategori]['jenis'])
            ->pluck('tblREKENING.rekening_id')
            ->all();

        return (float) self::saldoAkhirPerRekening($rekeningIds, $batas)->sum();
    }

    /**
     * Jasa tabungan yang sudah dibukukan ke rekening (transaksi jenis jasa) dalam rentang waktu.
     */
    public static function jasaDibukukan(Carbon $dari, Carbon $sampai): float
    {
        return (float) DB::table('tblTRANSAKSI')
            ->where('jenis_transaksi_id', self::JENIS_JASA_TABUNGAN)
            ->whereBetween('tgl_transaksi', [$dari, $sampai])
            ->sum(DB::raw('kredit - debit'));
    }
}
