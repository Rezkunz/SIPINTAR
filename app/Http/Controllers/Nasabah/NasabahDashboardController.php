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
                        DB::raw('(pokok + bunga) as total_angsuran'),
                        DB::raw("CASE WHEN status_bayar = 'lunas' THEN 'lunas' ELSE 'belum_bayar' END as status_angsuran")
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
                    DB::raw('(pokok + bunga) as total_angsuran'),
                    DB::raw("CASE WHEN status_bayar = 'lunas' THEN 'lunas' ELSE 'belum_bayar' END as status_angsuran")
                )
                ->where('pinjaman_id', $pinjamanAktif->pinjaman_id)
                ->orderBy('jatuh_tempo')
                ->get();
        }

        return view('nasabah.pinjaman', compact('user', 'nasabah', 'pinjaman', 'pinjamanAktif', 'jadwalAngsuran'));
    }

    /**
     * Halaman jadwal angsuran dan sisa piutang nasabah pegawai (Anti-IDOR).
     */
    public function jadwalAngsuran()
    {
        $user = Auth::user();
        $nasabah = null;

        if ($user->nasabah_id) {
            $nasabah = Nasabah::find($user->nasabah_id);
        }

        // Ambil pinjaman aktif yang berstatus cair milik nasabah yang sedang login
        $semuaPinjaman = $nasabah
            ? DB::table('tblPINJAMAN')
                ->where('nasabah_id', $nasabah->nasabah_id)
                ->orderByDesc('tgl_pengajuan')
                ->get()
            : collect();

        $pinjamanAktif = $semuaPinjaman->whereIn('status_pinjaman', ['cair'])->first();

        $jadwalAngsuran = collect();
        $riwayatAngsuran = collect();
        $sisaPokokPiutang = 0;

        if ($pinjamanAktif) {
            $jadwalAngsuran = DB::table('tblJADWAL_ANGSURAN')
                ->select(
                    'tblJADWAL_ANGSURAN.*',
                    DB::raw('(pokok + bunga) as total_angsuran')
                )
                ->where('pinjaman_id', $pinjamanAktif->pinjaman_id)
                ->orderBy('angsuran_ke')
                ->get();

            $riwayatAngsuran = DB::table('tblANGSURAN as ang')
                ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                ->where('ja.pinjaman_id', $pinjamanAktif->pinjaman_id)
                ->whereNull('kor.koreksi_id')
                ->select('ang.*', 'ja.angsuran_ke', 'ja.jatuh_tempo')
                ->orderBy('ang.tgl_bayar')
                ->get();

            $totalPokokTerbayar = $riwayatAngsuran->sum('bayar_pokok');
            $sisaPokokPiutang = \App\Services\PinjamanCalculatorService::hitungSisaPokok(
                $pinjamanAktif->nominal_pinjaman,
                $totalPokokTerbayar
            );
        }

        return view('nasabah.jadwal_angsuran', compact(
            'user',
            'nasabah',
            'pinjamanAktif',
            'semuaPinjaman',
            'jadwalAngsuran',
            'riwayatAngsuran',
            'sisaPokokPiutang'
        ));
    }

    /**
     * Formulir mandiri pengajuan pinjaman khusus nasabah pegawai.
     */
    public function formAjukanPinjaman()
    {
        $user = Auth::user();
        $nasabah = $user->nasabah_id ? Nasabah::find($user->nasabah_id) : null;

        if (($nasabah?->jenis_nasabah ?? 'non_pegawai') !== 'pegawai') {
            abort(403, 'Fasilitas pinjaman hanya diperuntukkan bagi guru dan tenaga kependidikan.');
        }

        // Cek apakah rekening sudah diaktivasi oleh Teller
        $rekening = Rekening::where('nasabah_id', $nasabah->nasabah_id)->first();
        if (!$rekening || $rekening->status_rekening !== 'aktif') {
            return redirect()->route('dashboard')->with('error', 'Layanan Perbankan Terkunci: Rekening Anda belum diaktivasi oleh Siswa Praktik / Teller Bank Mini. Anda belum dapat mengajukan pinjaman.');
        }

        // Cek apakah ada pinjaman aktif yang belum lunas
        $pinjamanAktif = DB::table('tblPINJAMAN')
            ->where('nasabah_id', $nasabah->nasabah_id)
            ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
            ->first();

        $sisaAngsuranBulan = 0;
        if ($pinjamanAktif && $pinjamanAktif->status_pinjaman === 'cair') {
            $sisaAngsuranBulan = DB::table('tblJADWAL_ANGSURAN')
                ->where('pinjaman_id', $pinjamanAktif->pinjaman_id)
                ->whereIn('status_bayar', ['belum', 'belum_bayar'])
                ->count();
        }

        return view('nasabah.ajukan_pinjaman', compact('user', 'nasabah', 'pinjamanAktif', 'sisaAngsuranBulan'));
    }

    /**
     * Simpan formulir pengajuan pinjaman mandiri dari nasabah pegawai.
     */
    public function simpanAjukanPinjaman(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $nasabah = $user->nasabah_id ? Nasabah::find($user->nasabah_id) : null;

        if (($nasabah?->jenis_nasabah ?? 'non_pegawai') !== 'pegawai') {
            abort(403, 'Fasilitas pinjaman hanya diperuntukkan bagi guru dan tenaga kependidikan.');
        }

        // Cek apakah rekening sudah diaktivasi oleh Teller
        $rekening = Rekening::where('nasabah_id', $nasabah->nasabah_id)->first();
        if (!$rekening || $rekening->status_rekening !== 'aktif') {
            return redirect()->route('dashboard')->with('error', 'Layanan Perbankan Terkunci: Rekening Anda belum diaktivasi oleh Siswa Praktik / Teller Bank Mini.');
        }

        $request->validate([
            'nominal_pinjaman' => 'required|numeric|min:500000|max:30000000',
            'tenor'            => 'required|integer|min:1|max:36',
            'metode_bayar'     => 'required|in:potong_gaji,tunai',
        ], [
            'nominal_pinjaman.min'  => 'Nominal pinjaman minimal Rp 500.000.',
            'nominal_pinjaman.max'  => 'Nominal pinjaman maksimal Rp 30.000.000.',
            'tenor.min'             => 'Tenor pinjaman minimal 1 bulan.',
            'tenor.max'             => 'Tenor pinjaman maksimal 36 bulan.',
            'metode_bayar.required' => 'Metode pengembalian angsuran wajib dipilih.',
        ]);

        $pinjamanAktif = DB::table('tblPINJAMAN')
            ->where('nasabah_id', $nasabah->nasabah_id)
            ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
            ->first();

        if ($pinjamanAktif) {
            return redirect()->route('nasabah.pinjaman')
                ->with('error', "Pengajuan ditolak: Anda masih memiliki fasilitas pinjaman yang sedang berjalan (#PINJ-{$pinjamanAktif->pinjaman_id}). Seluruh kewajiban angsuran harus dilunasi terlebih dahulu.");
        }

        try {
            \App\Services\PeriodeLockService::assertPeriodeTerbuka(now()->toDateString());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $pinjamanId = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $nasabah->nasabah_id,
            'user_id'          => $user->user_id,
            'nominal_pinjaman' => $request->nominal_pinjaman,
            'tenor'            => $request->tenor,
            'bunga_persen'     => 2.00,
            'tgl_pengajuan'    => now()->toDateString(),
            'metode_bayar'     => $request->metode_bayar,
            'status_pinjaman'  => 'diajukan',
        ], 'pinjaman_id');

        \App\Services\AuditLogService::log(
            aksi: 'PENGAJUAN_PINJAMAN_NASABAH',
            entitas: 'tblPINJAMAN',
            entitasId: $pinjamanId,
            dataBaru: [
                'nasabah_id'       => $nasabah->nasabah_id,
                'nominal_pinjaman' => $request->nominal_pinjaman,
                'tenor'            => $request->tenor,
                'metode_bayar'     => $request->metode_bayar,
            ]
        );

        return redirect()->route('nasabah.pinjaman')
            ->with('success', 'Permohonan pinjaman Anda berhasil diajukan! Saat ini sedang dalam antrean verifikasi & penilaian kelayakan oleh Komite Bank Mini.');
    }

    /**
     * Halaman Informasi Saldo & Riwayat Mutasi Rekening Nasabah (Use Case: Melihat Saldo dan Mutasi Saya).
     */
    public function mutasi(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $nasabah = $user->nasabah_id ? Nasabah::find($user->nasabah_id) : Nasabah::where('no_hp', $user->no_hp)->first();

        if (!$nasabah) {
            abort(404, 'Data nasabah tidak ditemukan.');
        }

        $rekening = Rekening::where('nasabah_id', $nasabah->nasabah_id)->first();
        if (!$rekening) {
            abort(404, 'Rekening nasabah tidak ditemukan.');
        }

        // Filter jenis transaksi
        $jenisFilter = $request->query('jenis', 'semua');

        $query = DB::table('tblTRANSAKSI')
            ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->leftJoin('tblUSER', 'tblTRANSAKSI.user_id', '=', 'tblUSER.user_id')
            ->where('tblTRANSAKSI.rekening_id', $rekening->rekening_id)
            ->select(
                'tblTRANSAKSI.*',
                'tblJENIS_TRANSAKSI.nama_jenis',
                'tblUSER.nama_user as nama_petugas'
            )
            ->orderBy('tblTRANSAKSI.tgl_transaksi', 'desc');

        if ($jenisFilter === 'setoran') {
            $query->where('tblTRANSAKSI.kredit', '>', 0);
        } elseif ($jenisFilter === 'penarikan') {
            $query->where('tblTRANSAKSI.debit', '>', 0);
        }

        $transaksiList = $query->paginate(15)->withQueryString();

        // Ringkasan Finansial Rekening
        $totalSetoran = (float) DB::table('tblTRANSAKSI')
            ->where('rekening_id', $rekening->rekening_id)
            ->sum('kredit');

        $totalPenarikan = (float) DB::table('tblTRANSAKSI')
            ->where('rekening_id', $rekening->rekening_id)
            ->sum('debit');

        $totalTransaksi = DB::table('tblTRANSAKSI')
            ->where('rekening_id', $rekening->rekening_id)
            ->count();

        return view('nasabah.mutasi', compact(
            'user',
            'nasabah',
            'rekening',
            'transaksiList',
            'totalSetoran',
            'totalPenarikan',
            'totalTransaksi',
            'jenisFilter'
        ));
    }
}
