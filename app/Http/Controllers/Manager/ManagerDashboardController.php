<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\JasaTabunganService;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    /**
     * Dashboard eksekutif Manager. Setiap angka diambil dari sumber yang sama dengan laporan resmi
     * (Neraca, Rekap Tabungan, Laporan Pinjaman) agar dashboard tidak pernah berbeda dengan laporan.
     */
    public function index()
    {
        $hariIni   = now();
        $awalBulan = $hariIni->copy()->startOfMonth();

        // 1. Dana Pihak Ketiga = saldo tabungan dari riwayat transaksi (sumber Neraca & Rekap Bulanan)
        $tabunganSiswa = JasaTabunganService::totalSaldo('siswa', $hariIni->copy()->endOfDay());
        $tabunganGuru  = JasaTabunganService::totalSaldo('guru', $hariIni->copy()->endOfDay());
        $totalTabungan = $tabunganSiswa + $tabunganGuru;

        $jumlahRekeningAktif = DB::table('tblREKENING')->where('status_rekening', 'aktif')->count();

        // 2. Sisa piutang pinjaman (pokok belum dibayar), dihitung sama dengan Laporan Pinjaman
        $portofolio = app(LaporanPinjamanController::class)->ringkasanPortofolio($hariIni->month, $hariIni->year);

        // 3. Nasabah aktif
        $totalNasabah = DB::table('tblNASABAH')->where('status_nasabah', 'aktif')->count();

        // 4. Total kas Bank Mini = kas tunai + rekening bank (posisi hari ini, sama dengan Neraca)
        $saldoKasTunai = (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');
        $saldoKasBank  = (float) DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo');
        $totalKas      = $saldoKasTunai + $saldoKasBank;

        // 5. Periode bulan berjalan beserta status aslinya, dan periode lampau yang belum ditutup
        $periodeAktif = DB::table('tblPERIODE')
            ->where('tahun', $hariIni->year)
            ->where('bulan', $hariIni->month)
            ->first();

        $periodeBelumDitutup = DB::table('tblPERIODE')
            ->where('status_periode', 'buka')
            ->where(fn ($q) => $q->where('tahun', '<', $hariIni->year)
                ->orWhere(fn ($w) => $w->where('tahun', $hariIni->year)->where('bulan', '<', $hariIni->month)))
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get(['bulan', 'tahun']);

        // 6. Lima transaksi tabungan terbesar pada bulan berjalan
        $transaksiTerbesar = DB::table('tblTRANSAKSI')
            ->join('tblREKENING', 'tblTRANSAKSI.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->whereBetween('tblTRANSAKSI.tgl_transaksi', [$awalBulan, $hariIni->copy()->endOfMonth()])
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
            ->orderByDesc('tblTRANSAKSI.tgl_transaksi')
            ->limit(5)
            ->get()
            ->map(function ($t) {
                // Pembalik koreksi: arah uang berlawanan dengan jenis transaksinya
                $t->pembalik = ($t->nama_jenis_transaksi === 'penarikan' && (float) $t->kredit > 0)
                    || ($t->nama_jenis_transaksi !== 'penarikan' && (float) $t->debit > 0);

                return $t;
            });

        return view('manager.dashboard', compact(
            'totalTabungan',
            'tabunganSiswa',
            'tabunganGuru',
            'jumlahRekeningAktif',
            'portofolio',
            'totalNasabah',
            'saldoKasTunai',
            'saldoKasBank',
            'totalKas',
            'periodeAktif',
            'periodeBelumDitutup',
            'awalBulan',
            'transaksiTerbesar'
        ));
    }
}
