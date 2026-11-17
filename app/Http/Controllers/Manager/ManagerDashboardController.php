<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    /**
     * Tampilkan dashboard eksekutif dan audit keuangan manager.
     */
    public function index()
    {
        // 1. Total Dana Pihak Ketiga (Tabungan)
        $totalTabungan = DB::table('tblREKENING')
            ->where('status_rekening', 'aktif')
            ->sum('saldo');

        $jumlahRekeningAktif = DB::table('tblREKENING')->where('status_rekening', 'aktif')->count();

        // 2. Sisa piutang pinjaman (pokok belum dibayar), dihitung sama dengan Laporan Pinjaman
        $portofolio = app(LaporanPinjamanController::class)->ringkasanPortofolio(now()->month, now()->year);

        // 3. Total Nasabah Aktif
        $totalNasabah = DB::table('tblNASABAH')
            ->where('status_nasabah', 'aktif')
            ->count();

        // 4. Total kas Bank Mini = kas tunai + rekening bank
        $saldoKasTunai = (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');
        $saldoKasBank  = (float) DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo');
        $totalKas      = $saldoKasTunai + $saldoKasBank;

        // 5. Periode Berjalan
        $periodeAktif = DB::table('tblPERIODE')
            ->where('status_periode', 'buka')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        // 6. Ringkasan 5 Transaksi Terbesar Terkini

        $transaksiTerbesar = DB::table('tblTRANSAKSI')
            ->join(
                'tblREKENING',
                'tblTRANSAKSI.rekening_id',
                '=',
                'tblREKENING.rekening_id'
            )
            ->join(
                'tblNASABAH',
                'tblREKENING.nasabah_id',
                '=',
                'tblNASABAH.nasabah_id'
            )
            ->join(
                'tblJENIS_TRANSAKSI',
                'tblTRANSAKSI.jenis_transaksi_id',
                '=',
                'tblJENIS_TRANSAKSI.jenis_transaksi_id'
            )
            ->select(
                'tblTRANSAKSI.*',
                'tblNASABAH.nama_nasabah',
                'tblREKENING.no_rek',
                'tblJENIS_TRANSAKSI.nama_jenis as nama_jenis_transaksi'
            )
            ->orderByRaw(match (DB::getDriverName()) {
                'sqlite' => 'MAX(tblTRANSAKSI.debit, tblTRANSAKSI.kredit) DESC',
                'pgsql'  => 'GREATEST("tblTRANSAKSI"."debit", "tblTRANSAKSI"."kredit") DESC',
                default  => 'GREATEST(tblTRANSAKSI.debit, tblTRANSAKSI.kredit) DESC',
            })
            ->limit(5)
            ->get();

        return view('manager.dashboard', compact(
            'totalTabungan',
            'jumlahRekeningAktif',
            'portofolio',
            'totalNasabah',
            'saldoKasTunai',
            'saldoKasBank',
            'totalKas',
            'periodeAktif',
            'transaksiTerbesar'
        ));
    }
}
