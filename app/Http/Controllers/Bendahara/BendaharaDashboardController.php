<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BendaharaDashboardController extends Controller
{
    /**
     * Tampilkan dashboard pemantauan & pencatatan hasil potongan gaji/tunjangan pegawai oleh Bendahara Sekolah.
     */
    public function index()
    {
        // 1. Metrik Rencana & Hasil Potongan
        $totalRencanaCount = DB::table('tblRENCANA_POTONGAN')->count();
        $totalNominalRencana = DB::table('tblRENCANA_POTONGAN')->sum('total_potongan');
        $totalPegawaiDipotong = DB::table('tblDETAIL_POTONGAN')->count();

        // 2. Daftar 5 Rencana Potongan Terbaru
        $rencanaList = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->select('rp.*', 'p.bulan', 'p.tahun')
            ->orderByDesc('rp.rencana_potongan_id')
            ->limit(5)
            ->get();

        return view('bendahara.dashboard', compact(
            'totalRencanaCount',
            'totalNominalRencana',
            'totalPegawaiDipotong',
            'rencanaList'
        ));
    }
}
