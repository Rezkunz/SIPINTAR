<?php

namespace App\Http\Controllers\SiswaPraktik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiswaPraktikDashboardController extends Controller
{
    /**
     * Tampilkan dashboard ruang praktik simulasi perbankan siswa.
     */
    public function index()
    {
        $userId = Auth::id();

        // 1. Rekap Transaksi Simulasi Siswa
        $totalDraft = DB::table('tblDRAFT')->where('siswa_id', $userId)->count();
        $draftPending = DB::table('tblDRAFT')->where('siswa_id', $userId)->where('status_verifikasi', 'pending')->count();
        $draftDisetujui = DB::table('tblDRAFT')->where('siswa_id', $userId)->where('status_verifikasi', 'disetujui')->count();

        // 2. Daftar 5 Draft Terakhir
        $drafts = DB::table('tblDRAFT')
            ->join('tblREKENING', 'tblDRAFT.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->join('tblJENIS_TRANSAKSI', 'tblDRAFT.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->leftJoin('tblUSER as teller', 'tblDRAFT.teller_id', '=', 'teller.user_id')
            ->select(
                'tblDRAFT.*',
                'tblNASABAH.nama_nasabah',
                'tblREKENING.no_rek',
                'tblJENIS_TRANSAKSI.nama_jenis as nama_jenis_transaksi',
                'teller.nama_user as nama_teller'
            )
            ->where('tblDRAFT.siswa_id', $userId)
            ->orderBy('tblDRAFT.tgl_input', 'desc')
            ->limit(5)
            ->get();

        return view('siswa_praktik.dashboard', compact('totalDraft', 'draftPending', 'draftDisetujui', 'drafts'));
    }
}
