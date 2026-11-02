<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTabunganController extends Controller
{
    /**
     * Kategori laporan → nilai kolom tblNASABAH.jenis_nasabah.
     */
    private const KATEGORI = [
        'siswa' => ['jenis' => 'non_pegawai', 'label' => 'Siswa'],
        'guru'  => ['jenis' => 'pegawai',     'label' => 'Guru / Karyawan'],
    ];

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

        // Paginasi hanya memotong tampilan; ringkasan & baris JUMLAH tetap dari seluruh hasil filter
        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $rekap['baris'] = new LengthAwarePaginator(
            $rekap['baris']->forPage($halaman, self::PER_HALAMAN)->values(),
            $rekap['baris']->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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
     * Bangun matriks rekening × tanggal berisi mutasi bersih (setoran - penarikan).
     */
    private function susunRekap(Request $request): array
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

        $awalBulan  = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth();

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

        return [
            'bulan'         => $bulan,
            'tahun'         => $tahun,
            'kategori'      => $kategori,
            'cari'          => $cari,
            'labelKategori' => self::KATEGORI[$kategori]['label'],
            'awalBulan'     => $awalBulan,
            'tanggalList'   => $tanggalList,
            'baris'         => $baris,
            'totalHarian'   => $totalHarian,
            'ringkasan'     => $ringkasan,
        ];
    }
}
