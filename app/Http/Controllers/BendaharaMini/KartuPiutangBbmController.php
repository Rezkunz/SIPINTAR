<?php

namespace App\Http\Controllers\BendaharaMini;

use App\Http\Controllers\Controller;
use App\Services\PinjamanCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartuPiutangBbmController extends Controller
{
    /**
     * Tampilkan Kartu Piutang Digital per peminjam.
     */
    public function kartu(Request $request)
    {
        $selectedPinjamanId = $request->query('pinjaman_id');

        // Daftar peminjam aktif & riwayat pinjaman untuk galeri kartu
        $daftarPeminjam = DB::table('tblPINJAMAN as pin')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblREKENING as rek', 'n.nasabah_id', '=', 'rek.nasabah_id')
            ->select(
                'pin.pinjaman_id',
                'pin.nominal_pinjaman',
                'pin.tenor',
                'pin.metode_bayar',
                'pin.status_pinjaman',
                'pin.tgl_pengajuan',
                'n.nasabah_id',
                'n.nama_nasabah',
                'n.kelas_unit',
                'rek.no_rek'
            )
            ->whereIn('pin.status_pinjaman', ['cair', 'lunas'])
            ->orderByRaw("CASE WHEN pin.status_pinjaman = 'cair' THEN 1 ELSE 2 END")
            ->orderBy('n.nama_nasabah')
            ->get();

        foreach ($daftarPeminjam as $dp) {
            $jadwals = DB::table('tblJADWAL_ANGSURAN')
                ->where('pinjaman_id', $dp->pinjaman_id)
                ->get();
            $dp->total_jadwal = $jadwals->count();
            $dp->lunas_jadwal = $jadwals->where('status_bayar', 'lunas')->count();
            
            // Total pokok terbayar
            $pokokTerbayar = DB::table('tblANGSURAN as ang')
                ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                ->where('ja.pinjaman_id', $dp->pinjaman_id)
                ->whereNull('kor.koreksi_id')
                ->sum('ang.bayar_pokok');

            $dp->pokok_terbayar = (float)$pokokTerbayar;
            $dp->sisa_pokok = max(0, (float)$dp->nominal_pinjaman - $dp->pokok_terbayar);
            $dp->persen_lunas = $dp->nominal_pinjaman > 0 ? round(($dp->pokok_terbayar / $dp->nominal_pinjaman) * 100) : 0;
        }

        $pinjamanTerpilih = null;
        $jadwalList = collect();
        $riwayatAngsuran = collect();
        $totalPokokTerbayar = 0;
        $totalJasaTerbayar = 0;
        $sisaPokokPiutang = 0;

        if ($selectedPinjamanId) {
            $pinjamanTerpilih = DB::table('tblPINJAMAN as pin')
                ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
                ->leftJoin('tblPENCAIRAN as cair', 'pin.pinjaman_id', '=', 'cair.pinjaman_id')
                ->where('pin.pinjaman_id', $selectedPinjamanId)
                ->select(
                    'pin.*',
                    'n.nama_nasabah',
                    'n.kelas_unit',
                    'n.no_hp',
                    'cair.tgl_cair',
                    'cair.nominal_cair'
                )
                ->first();

            if ($pinjamanTerpilih) {
                // Jadwal angsuran
                $jadwalList = DB::table('tblJADWAL_ANGSURAN')
                    ->where('pinjaman_id', $pinjamanTerpilih->pinjaman_id)
                    ->orderBy('angsuran_ke')
                    ->get();

                // Riwayat pembayaran angsuran (tidak termasuk yang dikoreksi)
                $riwayatAngsuran = DB::table('tblANGSURAN as ang')
                    ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                    ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                    ->where('ja.pinjaman_id', $pinjamanTerpilih->pinjaman_id)
                    ->whereNull('kor.koreksi_id') // Jangan sertakan yang sudah dikoreksi
                    ->select('ang.*', 'ja.angsuran_ke', 'ja.jatuh_tempo')
                    ->orderBy('ang.tgl_bayar')
                    ->get();

                $totalPokokTerbayar = $riwayatAngsuran->sum('bayar_pokok');
                $totalJasaTerbayar  = $riwayatAngsuran->sum('bayar_bunga');
                $sisaPokokPiutang   = PinjamanCalculatorService::hitungSisaPokok(
                    $pinjamanTerpilih->nominal_pinjaman,
                    $totalPokokTerbayar
                );
            }
        }

        return view('bendahara_mini.kartu.index', compact(
            'daftarPeminjam',
            'pinjamanTerpilih',
            'jadwalList',
            'riwayatAngsuran',
            'totalPokokTerbayar',
            'totalJasaTerbayar',
            'sisaPokokPiutang'
        ));
    }

    /**
     * Tampilkan Rekapitulasi Portofolio Piutang Seluruh Peminjam.
     */
    public function rekap()
    {
        $semuaPinjaman = DB::table('tblPINJAMAN as pin')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblPENCAIRAN as cair', 'pin.pinjaman_id', '=', 'cair.pinjaman_id')
            ->whereIn('pin.status_pinjaman', ['cair', 'lunas'])
            ->select(
                'pin.*',
                'n.nama_nasabah',
                'n.kelas_unit',
                'cair.tgl_cair'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        // Hitung akumulasi pembayaran tiap pinjaman
        $rekapList = $semuaPinjaman->map(function ($p) {
            $angsuranValid = DB::table('tblANGSURAN as ang')
                ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                ->where('ja.pinjaman_id', $p->pinjaman_id)
                ->whereNull('kor.koreksi_id')
                ->select(DB::raw('SUM(ang.bayar_pokok) as total_pokok, SUM(ang.bayar_bunga) as total_bunga'))
                ->first();

            $pokokBayar = (float) ($angsuranValid->total_pokok ?? 0);
            $bungaBayar = (float) ($angsuranValid->total_bunga ?? 0);
            $sisaPokok  = PinjamanCalculatorService::hitungSisaPokok($p->nominal_pinjaman, $pokokBayar);

            $p->total_pokok_bayar = $pokokBayar;
            $p->total_bunga_bayar = $bungaBayar;
            $p->sisa_pokok_piutang = $sisaPokok;

            return $p;
        });

        // Metrik Ringkasan Portofolio
        $totalPlafonPortofolio = $rekapList->sum('nominal_pinjaman');
        $totalPokokTerbayar    = $rekapList->sum('total_pokok_bayar');
        $totalBungaTerbayar    = $rekapList->sum('total_bunga_bayar');
        $totalSisaPiutang      = $rekapList->sum('sisa_pokok_piutang');

        return view('bendahara_mini.kartu.rekap', compact(
            'rekapList',
            'totalPlafonPortofolio',
            'totalPokokTerbayar',
            'totalBungaTerbayar',
            'totalSisaPiutang'
        ));
    }
}

