<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BendaharaDashboardController extends Controller
{
    /**
     * Tampilkan dashboard pengelolaan pinjaman dan angsuran bendahara sekolah.
     */
    public function index()
    {
        // 1. Metrik Pinjaman
        $totalPinjamanCair = DB::table('tblPINJAMAN')
            ->where('status_pinjaman', 'cair')
            ->sum('nominal_pinjaman');

        $pengajuanPendingCount = DB::table('tblPINJAMAN')
            ->where('status_pinjaman', 'diajukan')
            ->count();

        $pinjamanLunasCount = DB::table('tblPINJAMAN')
            ->where('status_pinjaman', 'lunas')
            ->count();

        // 2. Daftar 5 Pengajuan Pinjaman Terbaru
        $pinjamanList = DB::table('tblPINJAMAN')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->select('tblPINJAMAN.*', 'tblNASABAH.nama_nasabah', 'tblNASABAH.kelas_unit', 'tblNASABAH.jenis_nasabah')
            ->orderBy('tblPINJAMAN.tgl_pengajuan', 'desc')
            ->limit(5)
            ->get();

        return view('bendahara.dashboard', compact(
            'totalPinjamanCair',
            'pengajuanPendingCount',
            'pinjamanLunasCount',
            'pinjamanList'
        ));
    }
}
