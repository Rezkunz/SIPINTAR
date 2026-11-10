<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TellerInterestService
{
    /**
     * Ambil daftar rekening aktif untuk dropdown kalkulator teller.
     */
    public function activeAccounts(): Collection
    {
        return DB::table('tblREKENING as r')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('r.status_rekening', 'aktif')
            ->where('n.status_nasabah', 'aktif')
            ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nama_nasabah', 'n.jenis_nasabah')
            ->orderBy('r.no_rek')
            ->get();
    }

    /**
     * Hitung kalkulasi bunga dan periksa status pembukuan periode berjalan.
     */
    public function preview(?string $noRek): ?object
    {
        if (!$noRek) {
            return null;
        }

        $rekening = DB::table('tblREKENING as r')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('r.status_rekening', 'aktif')
            ->where('r.no_rek', $noRek)
            ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nama_nasabah', 'n.jenis_nasabah')
            ->first();

        if (!$rekening) {
            return null;
        }

        $kategori = $rekening->jenis_nasabah === 'pegawai' ? 'pegawai' : 'siswa';
        $kategoriLabel = $kategori === 'pegawai' ? 'Pegawai' : 'Siswa / Non-Pegawai';
        $persen = (float) config("sipintar.bunga_tabungan.{$kategori}", $kategori === 'pegawai' ? 0.002 : 0.001);
        $nominalBunga = (float) round((float) $rekening->saldo * $persen);

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $jenisBunga = DB::table('tblJENIS_TRANSAKSI')
            ->where('nama_jenis', 'jasa_tabungan')
            ->first();

        $sudahDibukukan = false;
        if ($jenisBunga) {
            $sudahDibukukan = DB::table('tblTRANSAKSI')
                ->where('rekening_id', $rekening->rekening_id)
                ->where('jenis_transaksi_id', $jenisBunga->jenis_transaksi_id)
                ->whereBetween('tgl_transaksi', [$startOfMonth, $endOfMonth])
                ->exists();
        }

        $rekening->kategori_bunga = $kategori;
        $rekening->kategori_label = $kategoriLabel;
        $rekening->persen_bunga = $persen * 100;
        $rekening->nominal_bunga = $nominalBunga;
        $rekening->saldo_setelah_bunga = (float) $rekening->saldo + $nominalBunga;
        $rekening->status_bunga_periode = $sudahDibukukan;
        $rekening->periode_label = $now->format('F Y');

        return $rekening;
    }
}
