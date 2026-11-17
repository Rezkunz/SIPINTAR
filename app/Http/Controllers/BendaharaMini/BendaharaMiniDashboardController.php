<?php

namespace App\Http\Controllers\BendaharaMini;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BendaharaMiniDashboardController extends Controller
{
    /**
     * Tampilkan dashboard kas harian, bank, portofolio pembiayaan, dan tabungan Bendahara Bank Mini.
     */
    public function index()
    {
        // 1. Posisi Kas Fisik & Bank (Aset Likuid Bank Mini)
        $saldoKasFisik  = (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');
        $saldoBankMitra = (float) DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo');
        $totalKas       = $saldoKasFisik + $saldoBankMitra;

        // 2. Simpanan Dana Pihak Ketiga (DPK) — TAPEL (Siswa) & TAKAR (Guru/Staf)
        $tabunganSiswa = (float) DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblREKENING.status_rekening', 'aktif')
            ->where('tblNASABAH.jenis_nasabah', 'non_pegawai')
            ->sum('tblREKENING.saldo');

        $tabunganGuru = (float) DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblREKENING.status_rekening', 'aktif')
            ->where('tblNASABAH.jenis_nasabah', 'pegawai')
            ->sum('tblREKENING.saldo');

        $totalDanaTabungan   = $tabunganSiswa + $tabunganGuru;
        $rekeningAktifCount  = DB::table('tblREKENING')->where('status_rekening', 'aktif')->count();
        $rekeningSiswaCount  = DB::table('tblREKENING')->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')->where('tblREKENING.status_rekening', 'aktif')->where('tblNASABAH.jenis_nasabah', 'non_pegawai')->count();
        $rekeningGuruCount   = DB::table('tblREKENING')->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')->where('tblREKENING.status_rekening', 'aktif')->where('tblNASABAH.jenis_nasabah', 'pegawai')->count();

        // 3. Portofolio Pinjaman & Piutang Pegawai
        $totalOutstandingPiutang = (float) DB::table('tblJADWAL_ANGSURAN')
            ->where('status_bayar', 'belum')
            ->sum('pokok');

        $totalPokokTerbayar = (float) DB::table('tblANGSURAN as ang')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereNull('kor.koreksi_id')
            ->sum('ang.bayar_pokok');

        $totalJasaTerbayar  = (float) DB::table('tblANGSURAN as ang')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereNull('kor.koreksi_id')
            ->sum('ang.bayar_bunga');

        $totalPinjamanDisalurkan = (float) DB::table('tblPINJAMAN')
            ->whereIn('status_pinjaman', ['cair', 'lunas'])
            ->sum('nominal_pinjaman');

        $debiturAktif  = DB::table('tblPINJAMAN')->where('status_pinjaman', 'cair')->count();
        $pinjamanLunas = DB::table('tblPINJAMAN')->where('status_pinjaman', 'lunas')->count();
        $pengajuanBaru = DB::table('tblPINJAMAN')->whereIn('status_pinjaman', ['diajukan', 'layak'])->count();

        // 4. Arus Kas Bulan Berjalan
        $currentMonth = date('m');
        $currentYear  = date('Y');

        $kasMasukBulanIni = (float) DB::table('tblMUTASI_KAS')
            ->where('arah', 'masuk')
            ->whereMonth('tgl_mutasi', $currentMonth)
            ->whereYear('tgl_mutasi', $currentYear)
            ->sum('nominal');

        $kasKeluarBulanIni = (float) DB::table('tblMUTASI_KAS')
            ->where('arah', 'keluar')
            ->whereMonth('tgl_mutasi', $currentMonth)
            ->whereYear('tgl_mutasi', $currentYear)
            ->sum('nominal');

        $netCashflow = $kasMasukBulanIni - $kasKeluarBulanIni;

        // 5. Paket Potongan Menunggu Konfirmasi / Dibukukan
        $paketMenunggu = DB::table('tblRENCANA_POTONGAN')
            ->where('status_potongan', 'menunggu_verifikasi')
            ->get();

        $paketMenungguCount   = $paketMenunggu->count();
        $paketMenungguNominal = (float) $paketMenunggu->sum('total_realisasi');

        // 6. Catatan Mutasi Buku Kas Harian Terbaru
        $mutasiList = DB::table('tblMUTASI_KAS')
            ->join('tblKAS', 'tblMUTASI_KAS.kas_id', '=', 'tblKAS.kas_id')
            ->leftJoin('tblUSER', 'tblMUTASI_KAS.user_id', '=', 'tblUSER.user_id')
            ->select(
                'tblMUTASI_KAS.*',
                'tblKAS.nama_kas',
                'tblUSER.nama_user'
            )
            ->orderBy('tblMUTASI_KAS.tgl_mutasi', 'desc')
            ->orderBy('tblMUTASI_KAS.mutasi_kas_id', 'desc')
            ->limit(6)
            ->get();

        // 7. Riwayat Paket Rencana Potongan Bulanan Terkini
        $paketPotonganList = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->select('rp.*', 'p.bulan', 'p.tahun')
            ->orderBy('rp.rencana_potongan_id', 'desc')
            ->limit(4)
            ->get();

        return view('bendahara_mini.dashboard', compact(
            'saldoKasFisik',
            'saldoBankMitra',
            'totalKas',
            'tabunganSiswa',
            'tabunganGuru',
            'totalDanaTabungan',
            'rekeningAktifCount',
            'rekeningSiswaCount',
            'rekeningGuruCount',
            'totalOutstandingPiutang',
            'totalPokokTerbayar',
            'totalJasaTerbayar',
            'totalPinjamanDisalurkan',
            'debiturAktif',
            'pinjamanLunas',
            'pengajuanBaru',
            'kasMasukBulanIni',
            'kasKeluarBulanIni',
            'netCashflow',
            'paketMenungguCount',
            'paketMenungguNominal',
            'mutasiList',
            'paketPotonganList'
        ));
    }
}
