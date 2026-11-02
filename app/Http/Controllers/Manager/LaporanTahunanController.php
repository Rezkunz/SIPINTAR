<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\LabaRugiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTahunanController extends Controller
{
    private const PER_HALAMAN_KAS_MANUAL = 10;

    /**
     * Laporan keuangan tahunan (laba/rugi) beserta dasar perhitungan per bulan.
     */
    public function index(Request $request)
    {
        $laporan = $this->susunLaporan($request);

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $laporan['kasManual'] = new LengthAwarePaginator(
            $laporan['kasManual']->forPage($halaman, self::PER_HALAMAN_KAS_MANUAL)->values(),
            $laporan['kasManual']->count(),
            self::PER_HALAMAN_KAS_MANUAL,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query(), 'fragment' => 'kas-manual']
        );

        return view('manager.laporan.tahunan', $laporan);
    }

    /**
     * Unduh laporan laba/rugi tahunan & dasar perhitungannya dalam format CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $laporan = $this->susunLaporan($request);

        $filename = "LAPORAN_KEUANGAN_TAHUNAN_{$laporan['tahun']}.csv";

        return response()->streamDownload(function () use ($laporan) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            $t = $laporan['total'];

            fputcsv($output, ['LAPORAN LABA RUGI (INCOME STATEMENT)'], ';');
            fputcsv($output, ['BANK MINI WIYATA CIPTA NIAGA - SMK NEGERI 1 LEMAHABANG'], ';');
            fputcsv($output, ['PER ' . strtoupper($laporan['tanggalPosisi']->translatedFormat('d F Y'))], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['PENDAPATAN'], ';');
            fputcsv($output, ['Pendapatan Jasa Pinjaman', $t['jasa_pinjaman']], ';');
            fputcsv($output, ['Pendapatan Lain-lain (kas manual)', $t['pendapatan_lain']], ';');
            fputcsv($output, ['Total Pendapatan', $t['pendapatan']], ';');
            fputcsv($output, ['BEBAN'], ';');
            fputcsv($output, ['Beban Jasa Tabungan Guru/Karyawan', $t['jasa_guru']], ';');
            fputcsv($output, ['Beban Jasa Tabungan Siswa', $t['jasa_siswa']], ';');
            fputcsv($output, ['Beban Operasional (kas manual)', $t['beban_operasional']], ';');
            fputcsv($output, ['Total Beban', $t['beban']], ';');
            fputcsv($output, [$t['laba'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH', $t['laba']], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['DASAR PERHITUNGAN'], ';');
            fputcsv($output, ['NO', 'BULAN', 'JASA PINJAMAN', 'PENDAPATAN LAIN', 'JASA TAB. SISWA', 'JASA TAB. GURU', 'BEBAN OPERASIONAL', 'LABA/RUGI'], ';');
            foreach ($laporan['bulanan'] as $i => $b) {
                fputcsv($output, [
                    $i + 1, strtoupper($b->nama_bulan), $b->jasa_pinjaman, $b->pendapatan_lain,
                    $b->jasa_siswa, $b->jasa_guru, $b->beban_operasional, $b->laba,
                ], ';');
            }
            fputcsv($output, ['', 'JUMLAH', $t['jasa_pinjaman'], $t['pendapatan_lain'], $t['jasa_siswa'], $t['jasa_guru'], $t['beban_operasional'], $t['laba']], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['RINCIAN KAS MANUAL'], ';');
            fputcsv($output, ['TANGGAL', 'KAS', 'URAIAN', 'PENDAPATAN LAIN', 'BEBAN OPERASIONAL'], ';');
            foreach ($laporan['kasManual'] as $k) {
                fputcsv($output, [
                    Carbon::parse($k->tgl_mutasi)->format('d/m/Y'), $k->jenis_kas === 'bank' ? 'Bank' : 'Tunai', $k->uraian,
                    $k->arah === 'masuk' ? (float) $k->nominal : '', $k->arah === 'keluar' ? (float) $k->nominal : '',
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Laba rugi per bulan dari LabaRugiService; tahun berjalan dihitung s.d. bulan ini.
     */
    private function susunLaporan(Request $request): array
    {
        $validated = $request->validate([
            'tahun' => 'nullable|integer|between:2000,2100',
        ]);

        $tahun = (int) ($validated['tahun'] ?? now()->year);

        $bulanTerakhir = $tahun < now()->year ? 12 : ($tahun > now()->year ? 0 : now()->month);
        $tanggalPosisi = $bulanTerakhir === 12 || $bulanTerakhir === 0
            ? Carbon::create($tahun, 12, 31)
            : now()->startOfDay();

        return array_merge([
            'tahun'         => $tahun,
            'bulanTerakhir' => $bulanTerakhir,
            'tanggalPosisi' => $tanggalPosisi,
        ], LabaRugiService::hitung($tahun, $bulanTerakhir));
    }
}
