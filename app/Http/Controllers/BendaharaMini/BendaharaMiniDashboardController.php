<?php

namespace App\Http\Controllers\BendaharaMini;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BendaharaMiniDashboardController extends Controller
{
    /**
     * Tampilkan dashboard kas harian dan bank bendahara bank mini.
     */
    public function index()
    {
        // 1. Posisi Kas Fisik & Bank
        $saldoKasFisik = DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');
        $saldoBankMitra = DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo');

        // 2. Total Tabungan di Bank (DPK)
        $totalDanaTabungan = DB::table('tblREKENING')->where('status_rekening', 'aktif')->sum('saldo');

        // 3. Catatan Mutasi Kas Terakhir
        $mutasiList = DB::table('tblMUTASI_KAS')
            ->join('tblKAS', 'tblMUTASI_KAS.kas_id', '=', 'tblKAS.kas_id')
            ->leftJoin('tblUSER', 'tblMUTASI_KAS.user_id', '=', 'tblUSER.user_id')
            ->select(
                'tblMUTASI_KAS.*',
                'tblKAS.nama_kas',
                'tblUSER.nama_user'
            )
            ->orderBy('tblMUTASI_KAS.tgl_mutasi', 'desc')
            ->limit(5)
            ->get();

        return view('bendahara_mini.dashboard', compact(
            'saldoKasFisik',
            'saldoBankMitra',
            'totalDanaTabungan',
            'mutasiList'
        ));
    }
}
