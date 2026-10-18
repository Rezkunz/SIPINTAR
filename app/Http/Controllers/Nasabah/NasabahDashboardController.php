<?php

namespace App\Http\Controllers\Nasabah;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\Rekening;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NasabahDashboardController extends Controller
{
    /**
     * Tampilkan dashboard informasi rekening mandiri nasabah.
     * Siswa (non_pegawai) → dashboard_siswa (tabungan & tarik tunai saja)
     * Pegawai → dashboard_pegawai (tabungan, tarik, pinjaman & angsuran)
     */
    public function index()
    {
        $user = Auth::user();

        // Cari data nasabah terkait
        $nasabah = null;
        if ($user->nasabah_id) {
            $nasabah = Nasabah::find($user->nasabah_id);
        }
        if (!$nasabah) {
            $nasabah = Nasabah::where('no_hp', $user->no_hp)->first();
        }

        // Ambil rekening aktif nasabah
        $rekening = null;
        $transaksi = collect();
        if ($nasabah) {
            $rekening = Rekening::where('nasabah_id', $nasabah->nasabah_id)->first();
            if ($rekening) {
                $transaksi = DB::table('tblTRANSAKSI')
                    ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
                    ->where('tblTRANSAKSI.rekening_id', $rekening->rekening_id)
                    ->orderBy('tblTRANSAKSI.tgl_transaksi', 'desc')
                    ->limit(5)
                    ->get();
            }
        }

        // Cek pinjaman aktif (relevan untuk pegawai)
        $pinjamanAktif = null;
        $jadwalAngsuran = collect();
        if ($nasabah) {
            $pinjamanAktif = DB::table('tblPINJAMAN')
                ->where('nasabah_id', $nasabah->nasabah_id)
                ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
                ->first();

            if ($pinjamanAktif) {
                $jadwalAngsuran = DB::table('tblJADWAL_ANGSURAN')
                    ->select(
                        'tblJADWAL_ANGSURAN.*',
                        'tblJADWAL_ANGSURAN.jatuh_tempo as tgl_jatuh_tempo',
                        'tblJADWAL_ANGSURAN.pokok as nominal_angsuran',
                        DB::raw('(tblJADWAL_ANGSURAN.pokok + tblJADWAL_ANGSURAN.bunga) as total_angsuran'),
                        DB::raw("CASE WHEN tblJADWAL_ANGSURAN.status_bayar = 'lunas' THEN 'lunas' ELSE 'belum_bayar' END as status_angsuran")
                    )
                    ->where('pinjaman_id', $pinjamanAktif->pinjaman_id)
                    ->orderBy('jatuh_tempo')
                    ->limit(6)
                    ->get();
            }
        }

        $viewData = compact('user', 'nasabah', 'rekening', 'transaksi', 'pinjamanAktif', 'jadwalAngsuran');

        // Route ke view sesuai jenis nasabah
        $jenis = $nasabah?->jenis_nasabah ?? 'non_pegawai';

        return view(
            $jenis === 'pegawai' ? 'nasabah.dashboard_pegawai' : 'nasabah.dashboard_siswa',
            $viewData
        );
    }

    /**
     * Halaman info pinjaman lengkap (khusus pegawai).
     */
    public function pinjaman()
    {
        $user = Auth::user();
        $nasabah = null;

        if ($user->nasabah_id) {
            $nasabah = Nasabah::find($user->nasabah_id);
        }

        // Pastikan ini pegawai
        if (($nasabah?->jenis_nasabah ?? 'non_pegawai') !== 'pegawai') {
            abort(403, 'Halaman ini hanya untuk nasabah pegawai.');
        }

        $pinjaman = $nasabah
            ? DB::table('tblPINJAMAN')->where('nasabah_id', $nasabah->nasabah_id)->orderBy('tgl_pengajuan', 'desc')->get()
            : collect();

        $pinjamanAktif = $pinjaman->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])->first();
        $jadwalAngsuran = collect();
        if ($pinjamanAktif) {
            $jadwalAngsuran = DB::table('tblJADWAL_ANGSURAN')
                ->select(
                    'tblJADWAL_ANGSURAN.*',
                    'tblJADWAL_ANGSURAN.jatuh_tempo as tgl_jatuh_tempo',
                    'tblJADWAL_ANGSURAN.pokok as nominal_angsuran',
                    DB::raw('(tblJADWAL_ANGSURAN.pokok + tblJADWAL_ANGSURAN.bunga) as total_angsuran'),
                    DB::raw("CASE WHEN tblJADWAL_ANGSURAN.status_bayar = 'lunas' THEN 'lunas' ELSE 'belum_bayar' END as status_angsuran")
                )
                ->where('pinjaman_id', $pinjamanAktif->pinjaman_id)
                ->orderBy('jatuh_tempo')
                ->get();
        }

        return view('nasabah.pinjaman', compact('user', 'nasabah', 'pinjaman', 'pinjamanAktif', 'jadwalAngsuran'));
    }
}
