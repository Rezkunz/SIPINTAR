<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\JasaTabunganService;
use App\Services\LabaRugiService;
use App\Services\PinjamanCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanNeracaController extends Controller
{
    /**
     * Neraca (balance sheet) Bank Mini per akhir bulan terpilih.
     */
    public function index(Request $request)
    {
        $neraca = $this->susunNeraca($request);

        // Riwayat seluruh entri pos manual untuk dikelola, plus entri yang sedang diubah (?edit=id)
        $neraca['riwayatPos'] = DB::table('tblPOS_NERACA as p')
            ->leftJoin('tblUSER as u', 'p.user_id', '=', 'u.user_id')
            ->orderByDesc('p.tgl_berlaku')
            ->orderByDesc('p.pos_neraca_id')
            ->get(['p.*', 'u.nama_user']);
        $neraca['posDiubah'] = $request->filled('edit')
            ? $neraca['riwayatPos']->firstWhere('pos_neraca_id', $request->integer('edit'))
            : null;

        return view('manager.laporan.neraca', $neraca);
    }

    /**
     * Unduh neraca dalam format CSV (kompatibel Excel).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $n = $this->susunNeraca($request);

        $filename = 'NERACA_' . $n['tanggalPosisi']->format('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($n) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['NERACA (BALANCE SHEET)'], ';');
            fputcsv($output, ['BANK MINI WIYATA CIPTA NIAGA - SMK NEGERI 1 LEMAHABANG'], ';');
            fputcsv($output, ['PER ' . strtoupper($n['tanggalPosisi']->translatedFormat('d F Y'))], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['AKTIVA'], ';');
            fputcsv($output, ['Kas Tunai', $n['aktiva']['kas_tunai']], ';');
            fputcsv($output, ['Kas Bank', $n['aktiva']['kas_bank']], ';');
            fputcsv($output, ['Piutang Pinjaman Pegawai', $n['aktiva']['piutang']], ';');
            foreach ($n['posManual']['aktiva'] as $p) {
                fputcsv($output, [$p->nama_pos . ' (input manual)', (float) $p->nilai], ';');
            }
            fputcsv($output, ['TOTAL AKTIVA', $n['totalAktiva']], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['KEWAJIBAN'], ';');
            fputcsv($output, ['Tabungan Siswa', $n['kewajiban']['tabungan_siswa']], ';');
            fputcsv($output, ['Tabungan Guru/Karyawan', $n['kewajiban']['tabungan_guru']], ';');
            fputcsv($output, ['Jasa Tabungan Terutang (tahun berjalan)', $n['kewajiban']['jasa_terutang']], ';');
            foreach ($n['posManual']['kewajiban'] as $p) {
                fputcsv($output, [$p->nama_pos . ' (input manual)', (float) $p->nilai], ';');
            }
            fputcsv($output, ['TOTAL KEWAJIBAN', $n['totalKewajiban']], ';');
            fputcsv($output, ['EKUITAS'], ';');
            foreach ($n['posManual']['ekuitas'] as $p) {
                fputcsv($output, [$p->nama_pos . ' (input manual)', (float) $p->nilai], ';');
            }
            fputcsv($output, ['Laba (Rugi) Tahun Berjalan', $n['ekuitas']['laba_berjalan']], ';');
            fputcsv($output, ['Selisih Belum Terjelaskan', $n['ekuitas']['modal_selisih']], ';');
            fputcsv($output, ['TOTAL EKUITAS', $n['totalEkuitas']], ';');
            fputcsv($output, ['TOTAL KEWAJIBAN & EKUITAS', $n['totalKewajiban'] + $n['totalEkuitas']], ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Aktiva    = kas tunai + kas bank (dihitung mundur dari saldo kas saat ini) + sisa pokok piutang.
     * Kewajiban = saldo tabungan siswa & guru + jasa tabungan tahun berjalan yang belum dibukukan.
     * Pos manual (tblPOS_NERACA) menambah baris aktiva/kewajiban/ekuitas sesuai nilai yang berlaku.
     * Ekuitas   = aktiva - kewajiban; sisanya setelah laba berjalan & pos ekuitas manual
     *             ditampilkan sebagai selisih belum terjelaskan.
     */
    private function susunNeraca(Request $request): array
    {
        $validated = $request->validate([
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|between:2000,2100',
        ]);

        $bulan = (int) ($validated['bulan'] ?? now()->month);
        $tahun = (int) ($validated['tahun'] ?? now()->year);

        $awalBulan  = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->startOfDay();
        // Bulan berjalan/mendatang dihitung per hari ini
        $tanggalPosisi = $akhirBulan->gt(today()) ? today() : $akhirBulan;
        $batas         = $tanggalPosisi->copy()->endOfDay();

        $aktiva = [
            'kas_tunai' => $this->saldoKasPada('kas', $tanggalPosisi),
            'kas_bank'  => $this->saldoKasPada('bank', $tanggalPosisi),
            'piutang'   => $this->sisaPiutangPada($tanggalPosisi),
        ];

        $labaRugi    = LabaRugiService::hitung($tanggalPosisi->year, $tanggalPosisi->month)['total'];
        $jasaBeban   = $labaRugi['jasa_siswa'] + $labaRugi['jasa_guru'];
        $jasaDibayar = JasaTabunganService::jasaDibukukan($tanggalPosisi->copy()->startOfYear(), $batas);

        $kewajiban = [
            'tabungan_siswa' => JasaTabunganService::totalSaldo('siswa', $batas),
            'tabungan_guru'  => JasaTabunganService::totalSaldo('guru', $batas),
            'jasa_terutang'  => $jasaBeban - $jasaDibayar,
        ];

        $posManual = $this->posManualPada($tanggalPosisi);

        $totalAktiva    = array_sum($aktiva) + (float) $posManual['aktiva']->sum('nilai');
        $totalKewajiban = array_sum($kewajiban) + (float) $posManual['kewajiban']->sum('nilai');
        $totalEkuitas   = $totalAktiva - $totalKewajiban;

        $ekuitas = [
            'laba_berjalan' => $labaRugi['laba'],
            // Selisih yang belum terjelaskan oleh laba berjalan maupun pos ekuitas manual (modal, hibah, dll)
            'modal_selisih' => $totalEkuitas - $labaRugi['laba'] - (float) $posManual['ekuitas']->sum('nilai'),
        ];

        $totalTabungan = $kewajiban['tabungan_siswa'] + $kewajiban['tabungan_guru'];
        $totalKas      = $aktiva['kas_tunai'] + $aktiva['kas_bank'];

        return [
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'awalBulan'      => $awalBulan,
            'tanggalPosisi'  => $tanggalPosisi,
            'aktiva'         => $aktiva,
            'kewajiban'      => $kewajiban,
            'ekuitas'        => $ekuitas,
            'posManual'      => $posManual,
            'totalAktiva'    => $totalAktiva,
            'totalKewajiban' => $totalKewajiban,
            'totalEkuitas'   => $totalEkuitas,
            'rasioKas'       => $totalTabungan > 0 ? round($totalKas / $totalTabungan * 100, 2) : null,
        ];
    }

    /**
     * Nilai pos manual yang berlaku pada tanggal posisi: entri terakhir per (kelompok, nama pos)
     * dengan tgl_berlaku <= posisi. Pos bernilai nol (mis. deposito sudah cair) tidak ditampilkan.
     *
     * @return array{aktiva: \Illuminate\Support\Collection, kewajiban: \Illuminate\Support\Collection, ekuitas: \Illuminate\Support\Collection}
     */
    private function posManualPada(Carbon $tanggal): array
    {
        $berlaku = DB::table('tblPOS_NERACA')
            ->whereDate('tgl_berlaku', '<=', $tanggal)
            ->orderBy('tgl_berlaku')
            ->orderBy('pos_neraca_id')
            ->get()
            ->groupBy(fn ($p) => $p->kelompok . '|' . mb_strtolower($p->nama_pos))
            ->map(fn ($riwayat) => $riwayat->last())
            ->filter(fn ($p) => (float) $p->nilai != 0)
            ->sortBy('nama_pos')
            ->values();

        return [
            'aktiva'    => $berlaku->where('kelompok', 'aktiva')->values(),
            'kewajiban' => $berlaku->where('kelompok', 'kewajiban')->values(),
            'ekuitas'   => $berlaku->where('kelompok', 'ekuitas')->values(),
        ];
    }

    /**
     * Saldo kas pada akhir tanggal = saldo saat ini dikurangi mutasi sesudah tanggal tersebut.
     */
    private function saldoKasPada(string $jenisKas, Carbon $tanggal): float
    {
        $kasIds = DB::table('tblKAS')->where('jenis_kas', $jenisKas)->pluck('kas_id')->all();

        $saldoSekarang = (float) DB::table('tblKAS')->whereIn('kas_id', $kasIds)->sum('saldo');
        $mutasiSesudah = (float) DB::table('tblMUTASI_KAS')
            ->whereIn('kas_id', $kasIds)
            ->whereDate('tgl_mutasi', '>', $tanggal)
            ->sum(DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END"));

        return $saldoSekarang - $mutasiSesudah;
    }

    /**
     * Total sisa pokok pinjaman yang sudah cair pada tanggal tersebut
     * (pembayaran sah = tidak dikoreksi, sama dengan Rekap Pinjaman & Kolektibilitas).
     */
    private function sisaPiutangPada(Carbon $tanggal): float
    {
        $pinjaman = DB::table('tblPINJAMAN as pin')
            ->join('tblPENCAIRAN as cair', 'pin.pinjaman_id', '=', 'cair.pinjaman_id')
            ->whereIn('pin.status_pinjaman', ['cair', 'lunas'])
            ->whereDate('cair.tgl_cair', '<=', $tanggal)
            ->pluck('pin.nominal_pinjaman', 'pin.pinjaman_id');

        $pokokTerbayar = DB::table('tblANGSURAN as ang')
            ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereIn('ja.pinjaman_id', $pinjaman->keys()->all())
            ->whereNull('kor.koreksi_id')
            ->whereDate('ang.tgl_bayar', '<=', $tanggal)
            ->groupBy('ja.pinjaman_id')
            ->select('ja.pinjaman_id', DB::raw('SUM(ang.bayar_pokok) as total'))
            ->pluck('total', 'ja.pinjaman_id');

        return (float) $pinjaman->map(
            fn ($nominal, $id) => PinjamanCalculatorService::hitungSisaPokok($nominal, (float) ($pokokTerbayar[$id] ?? 0))
        )->sum();
    }
}
