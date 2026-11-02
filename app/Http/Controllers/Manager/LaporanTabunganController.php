<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\EksporDokumen;
use App\Services\EksporLaporanService;
use App\Services\JasaTabunganService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTabunganController extends Controller
{
    use EksporDokumen;

    /**
     * Kategori laporan & tarif jasa bulanan (%) — sumber tunggal di JasaTabunganService.
     */
    private const KATEGORI = JasaTabunganService::KATEGORI;

    /**
     * Hanya setoran (1) & penarikan (2); jasa tabungan direkap di laporan bulanan.
     */
    private const JENIS_TRANSAKSI_REKAP = [1, 2];

    private const PER_HALAMAN = 10;

    /**
     * Rekap harian transaksi tabungan per nasabah dalam satu bulan.
     */
    public function index(Request $request)
    {
        $rekap = $this->susunRekap($request);
        $rekap['baris'] = $this->paginasi($rekap['baris'], $request);

        return view('manager.laporan.tabungan', $rekap);
    }

    /**
     * Unduh rekap harian dalam format CSV (kompatibel Excel).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $rekap = $this->susunRekap($request);

        $namaBulan = strtoupper($rekap['awalBulan']->translatedFormat('F'));
        $filename  = "REKAP_HARIAN_" . strtoupper($rekap['kategori']) . "_{$namaBulan}_{$rekap['tahun']}.csv";

        return response()->streamDownload(function () use ($rekap) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['REKAP HARIAN ' . strtoupper($rekap['labelKategori'])], ';');
            fputcsv($output, ['BULAN ' . strtoupper($rekap['awalBulan']->translatedFormat('F Y'))], ';');
            fputcsv($output, [], ';');

            $header = ['NO', 'NO REKENING', 'NAMA NASABAH'];
            foreach ($rekap['tanggalList'] as $tgl) {
                $header[] = $tgl->format('d/m/Y');
            }
            $header[] = 'JUMLAH';
            fputcsv($output, $header, ';');

            foreach ($rekap['baris'] as $i => $b) {
                $row = [$i + 1, $b->no_rek, $b->nama_nasabah];
                foreach ($rekap['tanggalList'] as $tgl) {
                    $row[] = $b->harian[$tgl->toDateString()] ?? 0;
                }
                $row[] = $b->total;
                fputcsv($output, $row, ';');
            }

            $footer = ['', '', 'JUMLAH'];
            foreach ($rekap['tanggalList'] as $tgl) {
                $footer[] = $rekap['totalHarian'][$tgl->toDateString()] ?? 0;
            }
            $footer[] = $rekap['ringkasan']['mutasi_bersih'];
            fputcsv($output, $footer, ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Rekap saldo pokok & jasa tabungan per rekening pada akhir bulan.
     */
    public function bulanan(Request $request)
    {
        $rekap = $this->susunRekapBulanan($request);
        $rekap['baris'] = $this->paginasi($rekap['baris'], $request);

        return view('manager.laporan.tabungan_bulanan', $rekap);
    }

    /**
     * Unduh rekap tabungan & jasa bulanan dalam format CSV (kompatibel Excel).
     */
    public function exportBulananCsv(Request $request): StreamedResponse
    {
        $rekap = $this->susunRekapBulanan($request);

        $namaBulan = strtoupper($rekap['awalBulan']->translatedFormat('F'));
        $filename  = "REKAP_TABUNGAN_JASA_" . strtoupper($rekap['kategori']) . "_{$namaBulan}_{$rekap['tahun']}.csv";

        return response()->streamDownload(function () use ($rekap) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['REKAP TABUNGAN & JASA ' . strtoupper($rekap['labelKategori'])], ';');
            fputcsv($output, ['PER ' . strtoupper($rekap['akhirBulan']->translatedFormat('d F Y'))], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['NO', 'NO REKENING', 'NAMA NASABAH', 'JUMLAH POKOK', 'JASA ' . $rekap['labelPersenJasa'], 'TOTAL'], ';');

            foreach ($rekap['baris'] as $i => $b) {
                fputcsv($output, [$i + 1, $b->no_rek, $b->nama_nasabah, $b->pokok, $b->jasa, $b->total], ';');
            }

            fputcsv($output, [
                '', '', 'JUMLAH',
                $rekap['ringkasan']['total_pokok'],
                $rekap['ringkasan']['total_jasa'],
                $rekap['ringkasan']['total_akhir'],
            ], ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Dokumen PDF/Excel rekap harian (dipakai trait EksporDokumen).
     */
    protected function dokumenEkspor(Request $request): array
    {
        $rekap = $this->susunRekap($request);
        $namaBulan = strtoupper($rekap['awalBulan']->translatedFormat('F'));

        $kolom = [['label' => 'No', 'align' => 'center'], ['label' => 'No Rekening'], ['label' => 'Nama Nasabah']];
        foreach ($rekap['tanggalList'] as $tgl) {
            $kolom[] = ['label' => $tgl->format('d/m'), 'angka' => true];
        }
        $kolom[] = ['label' => 'Jumlah', 'angka' => true];

        $baris = $rekap['baris']->values()->map(function ($b, $i) use ($rekap) {
            $sel = [$i + 1, $b->no_rek, $b->nama_nasabah];
            foreach ($rekap['tanggalList'] as $tgl) {
                $sel[] = $b->harian[$tgl->toDateString()] ?? null;
            }
            $sel[] = $b->total;

            return EksporLaporanService::baris($sel);
        })->all();

        $kaki = ['', '', 'JUMLAH'];
        foreach ($rekap['tanggalList'] as $tgl) {
            $kaki[] = $rekap['totalHarian'][$tgl->toDateString()] ?? 0;
        }
        $kaki[] = $rekap['ringkasan']['mutasi_bersih'];

        return [
            'judul'       => 'Rekap Harian Tabungan ' . $rekap['labelKategori'],
            'subjudul'    => 'Bulan ' . $rekap['awalBulan']->translatedFormat('F Y') . ($rekap['cari'] !== '' ? " (pencarian: {$rekap['cari']})" : ''),
            'nama_file'   => 'REKAP_HARIAN_' . strtoupper($rekap['kategori']) . "_{$namaBulan}_{$rekap['tahun']}",
            'orientasi'   => 'landscape',
            'ukuran_font' => 6.5,
            'bagian'      => [[
                'kolom'   => $kolom,
                'baris'   => $baris,
                'kaki'    => [EksporLaporanService::baris($kaki, 'total')],
                'catatan' => 'Mutasi bersih = setoran - penarikan; nilai negatif berarti penarikan lebih besar dari setoran.',
            ]],
        ];
    }

    public function exportBulananPdf(Request $request)
    {
        return EksporLaporanService::pdf($this->dokumenBulanan($request));
    }

    public function exportBulananXlsx(Request $request)
    {
        return EksporLaporanService::xlsx($this->dokumenBulanan($request));
    }

    /**
     * Dokumen PDF/Excel rekap tabungan & jasa bulanan.
     */
    private function dokumenBulanan(Request $request): array
    {
        $rekap = $this->susunRekapBulanan($request);
        $namaBulan = strtoupper($rekap['awalBulan']->translatedFormat('F'));
        $r = $rekap['ringkasan'];

        return [
            'judul'     => 'Rekap Tabungan & Jasa ' . $rekap['labelKategori'],
            'subjudul'  => 'Per ' . $rekap['akhirBulan']->translatedFormat('d F Y') . ($rekap['cari'] !== '' ? " (pencarian: {$rekap['cari']})" : ''),
            'nama_file' => 'REKAP_TABUNGAN_JASA_' . strtoupper($rekap['kategori']) . "_{$namaBulan}_{$rekap['tahun']}",
            'bagian'    => [[
                'kolom' => [
                    ['label' => 'No', 'align' => 'center'], ['label' => 'No Rekening'], ['label' => 'Nama Nasabah'],
                    ['label' => 'Jumlah Pokok', 'angka' => true], ['label' => 'Jasa ' . $rekap['labelPersenJasa'], 'angka' => true], ['label' => 'Total', 'angka' => true],
                ],
                'baris' => $rekap['baris']->values()->map(fn ($b, $i) => EksporLaporanService::baris([
                    $i + 1, $b->no_rek, $b->nama_nasabah, $b->pokok, $b->jasa, $b->total,
                ]))->all(),
                'kaki'  => [EksporLaporanService::baris(['', '', 'JUMLAH', $r['total_pokok'], $r['total_jasa'], $r['total_akhir']], 'total')],
                'catatan' => "Jasa {$rekap['labelPersenJasa']} per bulan dari saldo pokok, dibulatkan ke rupiah terdekat.",
            ]],
        ];
    }

    /**
     * Bangun matriks rekening × tanggal berisi mutasi bersih (setoran - penarikan).
     */
    private function susunRekap(Request $request): array
    {
        $filter = $this->bacaFilter($request);
        ['kategori' => $kategori, 'filterCari' => $filterCari, 'awalBulan' => $awalBulan, 'akhirBulan' => $akhirBulan] = $filter;

        // Mutasi bersih per rekening per tanggal (kredit = setoran, debit = penarikan)
        $mutasi = DB::table('tblTRANSAKSI')
            ->join('tblREKENING', 'tblTRANSAKSI.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblNASABAH.jenis_nasabah', self::KATEGORI[$kategori]['jenis'])
            ->whereIn('tblTRANSAKSI.jenis_transaksi_id', self::JENIS_TRANSAKSI_REKAP)
            ->whereBetween('tblTRANSAKSI.tgl_transaksi', [$awalBulan, $akhirBulan])
            ->tap($filterCari)
            ->groupBy('tblTRANSAKSI.rekening_id', DB::raw('DATE(tblTRANSAKSI.tgl_transaksi)'))
            ->select(
                'tblTRANSAKSI.rekening_id',
                DB::raw('DATE(tblTRANSAKSI.tgl_transaksi) as tanggal'),
                DB::raw('SUM(tblTRANSAKSI.kredit) as total_setoran'),
                DB::raw('SUM(tblTRANSAKSI.debit) as total_penarikan')
            )
            ->get();

        // Semua rekening aktif ditampilkan, ditambah rekening non-aktif yang bertransaksi bulan ini
        $rekeningBertransaksi = $mutasi->pluck('rekening_id')->unique()->all();

        $rekeningList = DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->where('tblNASABAH.jenis_nasabah', self::KATEGORI[$kategori]['jenis'])
            ->where(function ($q) use ($rekeningBertransaksi) {
                $q->where('tblREKENING.status_rekening', 'aktif')
                  ->orWhereIn('tblREKENING.rekening_id', $rekeningBertransaksi);
            })
            ->tap($filterCari)
            ->orderBy('tblREKENING.no_rek')
            ->select('tblREKENING.rekening_id', 'tblREKENING.no_rek', 'tblNASABAH.nama_nasabah', 'tblNASABAH.kelas_unit')
            ->get();

        $mutasiPerRekening = $mutasi->groupBy('rekening_id');

        $totalHarian = [];
        $baris = $rekeningList->map(function ($rek) use ($mutasiPerRekening, &$totalHarian) {
            $harian = [];
            foreach ($mutasiPerRekening->get($rek->rekening_id, collect()) as $m) {
                $bersih = (float) $m->total_setoran - (float) $m->total_penarikan;
                $harian[$m->tanggal] = $bersih;
                $totalHarian[$m->tanggal] = ($totalHarian[$m->tanggal] ?? 0) + $bersih;
            }
            $rek->harian = $harian;
            $rek->total  = array_sum($harian);

            return $rek;
        });

        // Kolom tanggal: hari kerja (Senin–Jumat) + akhir pekan yang ada transaksinya
        $tanggalAdaMutasi = $mutasi->pluck('tanggal')->unique()->flip();
        $tanggalList = collect(CarbonPeriod::create($awalBulan, $akhirBulan))
            ->filter(fn (Carbon $tgl) => $tgl->isWeekday() || $tanggalAdaMutasi->has($tgl->toDateString()))
            ->values();

        $ringkasan = [
            'total_setoran'    => (float) $mutasi->sum('total_setoran'),
            'total_penarikan'  => (float) $mutasi->sum('total_penarikan'),
            'mutasi_bersih'    => (float) $mutasi->sum('total_setoran') - (float) $mutasi->sum('total_penarikan'),
            'rekening_aktif'   => count($rekeningBertransaksi),
            'jumlah_rekening'  => $rekeningList->count(),
        ];

        return array_merge($this->dataFilter($filter), [
            'tanggalList' => $tanggalList,
            'baris'       => $baris,
            'totalHarian' => $totalHarian,
            'ringkasan'   => $ringkasan,
        ]);
    }

    /**
     * Saldo pokok akhir bulan per rekening + jasa (persen × pokok), lihat JasaTabunganService.
     */
    private function susunRekapBulanan(Request $request): array
    {
        $filter = $this->bacaFilter($request);
        ['kategori' => $kategori, 'filterCari' => $filterCari, 'awalBulan' => $awalBulan, 'akhirBulan' => $akhirBulan] = $filter;
        $persenJasa = self::KATEGORI[$kategori]['persen_jasa'];

        $baris = JasaTabunganService::rekapBulanan($kategori, $awalBulan, $akhirBulan, $filterCari);

        $ringkasan = [
            'total_pokok'     => (float) $baris->sum('pokok'),
            'total_jasa'      => (float) $baris->sum('jasa'),
            'total_akhir'     => (float) $baris->sum('total'),
            'jumlah_rekening' => $baris->count(),
            'rekening_bersaldo' => $baris->where('pokok', '>', 0)->count(),
        ];

        return array_merge($this->dataFilter($filter), [
            'akhirBulan'      => $akhirBulan,
            'persenJasa'      => $persenJasa,
            'labelPersenJasa' => str_replace('.', ',', (string) $persenJasa) . '%',
            'baris'           => $baris,
            'ringkasan'       => $ringkasan,
        ]);
    }

    /**
     * Validasi & normalisasi filter bersama (kategori, periode, pencarian).
     */
    private function bacaFilter(Request $request): array
    {
        $validated = $request->validate([
            'bulan'    => 'nullable|integer|between:1,12',
            'tahun'    => 'nullable|integer|between:2000,2100',
            'kategori' => 'nullable|in:siswa,guru',
            'cari'     => 'nullable|string|max:100',
        ]);

        $bulan    = (int) ($validated['bulan'] ?? now()->month);
        $tahun    = (int) ($validated['tahun'] ?? now()->year);
        $kategori = $validated['kategori'] ?? 'siswa';
        $cari     = trim($validated['cari'] ?? '');

        $filterCari = function ($q) use ($cari) {
            if ($cari !== '') {
                $q->where(function ($w) use ($cari) {
                    $w->where('tblNASABAH.nama_nasabah', 'like', "%{$cari}%")
                      ->orWhere('tblREKENING.no_rek', 'like', "%{$cari}%");
                });
            }
        };

        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfDay();

        return [
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'kategori'   => $kategori,
            'cari'       => $cari,
            'filterCari' => $filterCari,
            'awalBulan'  => $awalBulan,
            'akhirBulan' => $awalBulan->copy()->endOfMonth(),
        ];
    }

    /**
     * Variabel filter yang dikirim ke view.
     */
    private function dataFilter(array $filter): array
    {
        return [
            'bulan'         => $filter['bulan'],
            'tahun'         => $filter['tahun'],
            'kategori'      => $filter['kategori'],
            'cari'          => $filter['cari'],
            'labelKategori' => self::KATEGORI[$filter['kategori']]['label'],
            'awalBulan'     => $filter['awalBulan'],
        ];
    }

    /**
     * Paginasi hanya memotong tampilan; ringkasan & baris JUMLAH tetap dari seluruh hasil filter.
     */
    private function paginasi(Collection $baris, Request $request): LengthAwarePaginator
    {
        $halaman = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $baris->forPage($halaman, self::PER_HALAMAN)->values(),
            $baris->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
