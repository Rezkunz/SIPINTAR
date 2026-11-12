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

        // 2. Total Pinjaman Disalurkan
        $totalPinjamanCair = DB::table('tblPINJAMAN')
            ->where('status_pinjaman', 'cair')
            ->sum('nominal_pinjaman');

        // 3. Total Nasabah Aktif
        $totalNasabah = DB::table('tblNASABAH')
            ->where('status_nasabah', 'aktif')
            ->count();

        // 4. Saldo Kas Brankas Fisik
        $saldoKasFisik = DB::table('tblKAS')
            ->where('jenis_kas', 'kas')
            ->sum('saldo');

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
            ->orderByRaw(
                'GREATEST("tblTRANSAKSI"."debit", "tblTRANSAKSI"."kredit") DESC'
            )
            ->limit(5)
            ->get();

        return view('manager.dashboard', compact(
            'totalTabungan',
            'totalPinjamanCair',
            'totalNasabah',
            'saldoKasFisik',
            'periodeAktif',
            'transaksiTerbesar'
        ));
    }
}
