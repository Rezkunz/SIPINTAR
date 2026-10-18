<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TellerDashboardController extends Controller
{
    /**
     * Tampilkan dashboard loket operasional kasir teller dengan metrik transaksi nyata.
     */
    public function index()
    {
        $today = now()->toDateString();

        // 1. Hitung total draft pending dari siswa praktik
        $draftPendingCount = DB::table('tblDRAFT')
            ->where('status_verifikasi', 'pending')
            ->count();

        // 2. Transaksi Hari Ini
        $totalSetoranHariIni = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->sum('kredit');

        $totalPenarikanHariIni = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->sum('debit');

        $transaksiHariIniCount = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->count();

        // 3. Draft Menunggu Verifikasi (limit 5)
        $drafts = DB::table('tblDRAFT')
            ->join('tblREKENING', 'tblDRAFT.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->leftJoin('tblUSER as siswa', 'tblDRAFT.siswa_id', '=', 'siswa.user_id')
            ->join('tblJENIS_TRANSAKSI', 'tblDRAFT.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->select(
                'tblDRAFT.*',
                'tblNASABAH.nama_nasabah',
                'tblREKENING.no_rek',
                'siswa.nama_user as nama_siswa',
                'tblJENIS_TRANSAKSI.nama_jenis as nama_jenis_transaksi'
            )
            ->where('tblDRAFT.status_verifikasi', 'pending')
            ->orderBy('tblDRAFT.tgl_input', 'asc')
            ->limit(5)
            ->get();

        // 4. Saldo Kas Brankas / Kas Harian
        $kasHariIni = DB::table('tblKAS')
            ->where('jenis_kas', 'kas')
            ->first();

        return view('teller.dashboard', compact(
            'draftPendingCount',
            'totalSetoranHariIni',
            'totalPenarikanHariIni',
            'transaksiHariIniCount',
            'drafts',
            'kasHariIni'
        ));
    }
}
