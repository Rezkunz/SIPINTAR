<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\EksporDokumen;
use App\Services\EksporLaporanService;
use App\Services\PinjamanCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPinjamanController extends Controller
{
    use EksporDokumen;

    /**
     * Kolektibilitas berdasarkan hari keterlambatan (standar OJK), urut dari terbaik.
     * 'maks' = batas atas hari terlambat untuk kategori tersebut.
     */
    public const KOLEKTIBILITAS = [
        'lancar'        => ['label' => 'Lancar',                 'maks' => 0],
        'dpk'           => ['label' => 'Dalam Perhatian Khusus', 'maks' => 90],
        'kurang_lancar' => ['label' => 'Kurang Lancar',          'maks' => 120],
        'diragukan'     => ['label' => 'Diragukan',              'maks' => 180],
        'macet'         => ['label' => 'Macet',                  'maks' => PHP_INT_MAX],
    ];

    /**
     * Kategori yang dihitung sebagai pinjaman bermasalah (Non Performing Loan).
     */
    private const KATEGORI_NPL = ['kurang_lancar', 'diragukan', 'macet'];

    private const PER_HALAMAN = 10;

    /**
     * Rekap portofolio pinjaman & kolektibilitas per akhir bulan terpilih.
     */
    public function index(Request $request)
    {
        $rekap = $this->susunRekap($request);

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $rekap['baris'] = new LengthAwarePaginator(
            $rekap['baris']->forPage($halaman, self::PER_HALAMAN)->values(),
            $rekap['baris']->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('manager.laporan.pinjaman', $rekap);
    }

    /**
     * Unduh rekap pinjaman & kolektibilitas dalam format CSV (kompatibel Excel).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $rekap = $this->susunRekap($request);

        $filename = 'REKAP_PINJAMAN_KOLEKTIBILITAS_' . $rekap['tanggalPosisi']->format('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($rekap) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['REKAP PINJAMAN & KOLEKTIBILITAS'], ';');
            fputcsv($output, ['POSISI PER ' . strtoupper($rekap['tanggalPosisi']->translatedFormat('d F Y'))], ';');
            fputcsv($output, [], ';');

            fputcsv($output, [
                'NO', 'NO PINJAMAN', 'NAMA PEMINJAM', 'UNIT', 'METODE BAYAR', 'TGL CAIR', 'PLAFON', 'TENOR (BLN)',
                'ANGSURAN DIBAYAR', 'POKOK TERBAYAR', 'JASA TERBAYAR', 'SISA POKOK', 'TUNGGAKAN (BLN)', 'NOMINAL TUNGGAKAN',
                'HARI TERLAMBAT', 'KOLEKTIBILITAS',
            ], ';');

            foreach ($rekap['baris'] as $i => $b) {
                fputcsv($output, [
                    $i + 1, $b->no_pinjaman, $b->nama_nasabah, $b->kelas_unit, $b->label_metode,
                    $b->tgl_cair ? Carbon::parse($b->tgl_cair)->format('d/m/Y') : '',
                    (float) $b->nominal_pinjaman, $b->tenor, "{$b->angsuran_dibayar}/{$b->total_angsuran}",
                    $b->pokok_terbayar, $b->jasa_terbayar, $b->sisa_pokok, $b->bulan_tunggakan, $b->nominal_tunggakan,
                    $b->hari_terlambat, self::KOLEKTIBILITAS[$b->kolektibilitas]['label'],
                ], ';');
            }

            $r = $rekap['ringkasan'];
            fputcsv($output, ['', '', 'JUMLAH', '', '', '', $r['total_plafon'], '', '', $r['total_pokok_terbayar'], $r['total_jasa_terbayar'], $r['total_sisa_pokok'], '', $r['total_tunggakan']], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['KOLEKTIBILITAS', 'JUMLAH PINJAMAN', 'SISA POKOK', 'PERSENTASE'], ';');
            foreach ($rekap['sebaran'] as $s) {
                fputcsv($output, [$s['label'], $s['jumlah'], $s['sisa_pokok'], $s['persen'] . '%'], ';');
            }
            fputcsv($output, ['RASIO NPL', '', '', $r['rasio_npl'] . '%'], ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Dokumen PDF/Excel rekap pinjaman & kolektibilitas beserta sebaran dan rasio NPL.
     */
    protected function dokumenEkspor(Request $request): array
    {
        $rekap = $this->susunRekap($request);
        $r     = $rekap['ringkasan'];
        $filter = array_filter([
            $rekap['filterKolek'] ? 'kolektibilitas: ' . self::KOLEKTIBILITAS[$rekap['filterKolek']]['label'] : null,
            $rekap['cari'] !== '' ? "pencarian: {$rekap['cari']}" : null,
        ]);

        $baris = $rekap['baris']->values()->map(fn ($b, $i) => EksporLaporanService::baris([
            $i + 1, $b->no_pinjaman, $b->nama_nasabah, $b->kelas_unit, $b->label_metode,
            Carbon::parse($b->tgl_cair)->format('d/m/Y'), (float) $b->nominal_pinjaman,
            "{$b->angsuran_dibayar}/{$b->total_angsuran}", $b->pokok_terbayar, $b->sisa_pokok,
            $b->bulan_tunggakan ?: null, $b->nominal_tunggakan ?: null, $b->hari_terlambat ?: null,
            self::KOLEKTIBILITAS[$b->kolektibilitas]['label'],
        ]))->all();

        $sebaran = collect($rekap['sebaran'])->values()->map(fn ($s) => EksporLaporanService::baris([
            $s['label'], $s['jumlah'], $s['sisa_pokok'], number_format($s['persen'], 2, ',', '.') . '%',
        ]))->all();

        return [
            'judul'       => 'Rekap Pinjaman & Kolektibilitas',
            'subjudul'    => 'Posisi per ' . $rekap['tanggalPosisi']->translatedFormat('d F Y') . ($filter ? ' (' . implode(', ', $filter) . ')' : ''),
            'nama_file'   => 'REKAP_PINJAMAN_KOLEKTIBILITAS_' . $rekap['tanggalPosisi']->format('Y_m_d'),
            'orientasi'   => 'landscape',
            'ukuran_font' => 7.5,
            'bagian'      => [
                [
                    'kolom' => [
                        ['label' => 'No', 'align' => 'center'], ['label' => 'No Pinjaman'], ['label' => 'Peminjam'], ['label' => 'Unit'],
                        ['label' => 'Metode'], ['label' => 'Tgl Cair', 'align' => 'center'], ['label' => 'Plafon', 'angka' => true],
                        ['label' => 'Angsuran', 'align' => 'center'], ['label' => 'Pokok Terbayar', 'angka' => true],
                        ['label' => 'Sisa Pokok', 'angka' => true], ['label' => 'Tunggakan (bln)', 'angka' => true],
                        ['label' => 'Nominal Tunggakan', 'angka' => true], ['label' => 'Hari Terlambat', 'angka' => true],
                        ['label' => 'Kolektibilitas'],
                    ],
                    'baris' => $baris,
                    'kaki'  => $filter ? [] : [EksporLaporanService::baris([
                        '', '', 'JUMLAH', '', '', '', $r['total_plafon'], '', $r['total_pokok_terbayar'],
                        $r['total_sisa_pokok'], null, $r['total_tunggakan'], null, '',
                    ], 'total')],
                ],
                [
                    'judul' => 'Sebaran Kolektibilitas (seluruh pinjaman berjalan)',
                    'kolom' => [
                        ['label' => 'Kolektibilitas'], ['label' => 'Jumlah Pinjaman', 'angka' => true],
                        ['label' => 'Sisa Pokok', 'angka' => true], ['label' => 'Persentase', 'align' => 'right'],
                    ],
                    'baris' => $sebaran,
                    'kaki'  => [EksporLaporanService::baris([
                        'Rasio NPL (Kurang Lancar + Diragukan + Macet)', null, null, number_format($r['rasio_npl'], 2, ',', '.') . '%',
                    ], 'total')],
                    'catatan' => 'Kolektibilitas standar OJK berdasarkan hari keterlambatan sejak jatuh tempo tertua yang belum dibayar: Lancar 0, DPK 1-90, Kurang Lancar 91-120, Diragukan 121-180, Macet > 180 hari.',
                ],
            ],
        ];
    }

    /**
     * Susun posisi setiap pinjaman pada tanggal posisi: terbayar, sisa pokok, tunggakan, kolektibilitas.
     *
     * Status lunas angsuran dihitung dari pembayaran sah (tidak dikoreksi) s.d. tanggal posisi,
     * bukan dari status_bayar saat ini, agar laporan bulan lampau tetap mencerminkan kondisi saat itu.
     */
    private function susunRekap(Request $request): array
    {
        $validated = $request->validate([
            'bulan'          => 'nullable|integer|between:1,12',
            'tahun'          => 'nullable|integer|between:2000,2100',
            'cari'           => 'nullable|string|max:100',
            'kolektibilitas' => 'nullable|in:' . implode(',', array_keys(self::KOLEKTIBILITAS)),
        ]);

        $bulan  = (int) ($validated['bulan'] ?? now()->month);
        $tahun  = (int) ($validated['tahun'] ?? now()->year);
        $cari   = trim($validated['cari'] ?? '');
        $filter = $validated['kolektibilitas'] ?? null;

        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfDay();
        // Posisi = akhir bulan; bulan berjalan/mendatang dihitung per hari ini agar
        // angsuran yang belum jatuh tempo tidak terhitung sebagai tunggakan
        $akhirBulan    = $awalBulan->copy()->endOfMonth()->startOfDay();
        $tanggalPosisi = $akhirBulan->gt(today()) ? today() : $akhirBulan;

        $pinjamanList = DB::table('tblPINJAMAN as pin')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblPENCAIRAN as cair', 'pin.pinjaman_id', '=', 'cair.pinjaman_id')
            ->whereIn('pin.status_pinjaman', ['cair', 'lunas'])
            ->whereDate('cair.tgl_cair', '<=', $tanggalPosisi)
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($w) use ($cari) {
                    $nomor = ltrim(preg_replace('/\D/', '', $cari), '0');
                    $w->where('n.nama_nasabah', 'like', "%{$cari}%")
                      ->orWhere('n.kelas_unit', 'like', "%{$cari}%");
                    if ($nomor !== '') {
                        $w->orWhere('pin.pinjaman_id', (int) $nomor);
                    }
                });
            })
            ->select(
                'pin.pinjaman_id', 'pin.nominal_pinjaman', 'pin.tenor', 'pin.metode_bayar', 'pin.status_pinjaman',
                'n.nama_nasabah', 'n.kelas_unit', 'cair.tgl_cair'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        $pinjamanIds = $pinjamanList->pluck('pinjaman_id')->all();

        $jadwalPerPinjaman = DB::table('tblJADWAL_ANGSURAN')
            ->whereIn('pinjaman_id', $pinjamanIds)
            ->orderBy('angsuran_ke')
            ->get()
            ->groupBy('pinjaman_id');

        // Pembayaran sah s.d. tanggal posisi, dikelompokkan per jadwal
        $bayarPerJadwal = DB::table('tblANGSURAN as ang')
            ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereIn('ja.pinjaman_id', $pinjamanIds)
            ->whereNull('kor.koreksi_id')
            ->whereDate('ang.tgl_bayar', '<=', $tanggalPosisi)
            ->groupBy('ang.jadwal_angsuran_id')
            ->select(
                'ang.jadwal_angsuran_id',
                DB::raw('SUM(ang.bayar_pokok) as pokok'),
                DB::raw('SUM(ang.bayar_bunga) as bunga')
            )
            ->get()
            ->keyBy('jadwal_angsuran_id');

        $baris = $pinjamanList
            ->map(function ($p) use ($jadwalPerPinjaman, $bayarPerJadwal, $tanggalPosisi) {
                $jadwal = $jadwalPerPinjaman->get($p->pinjaman_id, collect());

                $dibayar   = $jadwal->filter(fn ($j) => $bayarPerJadwal->has($j->jadwal_angsuran_id));
                $tertunggak = $jadwal->filter(fn ($j) => !$bayarPerJadwal->has($j->jadwal_angsuran_id)
                    && Carbon::parse($j->jatuh_tempo)->lt($tanggalPosisi));

                $p->no_pinjaman       = '#PINJ-' . str_pad($p->pinjaman_id, 4, '0', STR_PAD_LEFT);
                $p->label_metode      = $p->metode_bayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunai';
                $p->total_angsuran    = $jadwal->count();
                $p->angsuran_dibayar  = $dibayar->count();
                $p->pokok_terbayar    = (float) $dibayar->sum(fn ($j) => $bayarPerJadwal[$j->jadwal_angsuran_id]->pokok);
                $p->jasa_terbayar     = (float) $dibayar->sum(fn ($j) => $bayarPerJadwal[$j->jadwal_angsuran_id]->bunga);
                $p->sisa_pokok        = PinjamanCalculatorService::hitungSisaPokok($p->nominal_pinjaman, $p->pokok_terbayar);
                $p->bulan_tunggakan   = $tertunggak->count();
                $p->nominal_tunggakan = (float) $tertunggak->sum(fn ($j) => (float) $j->pokok + (float) $j->bunga);

                $jatuhTempoTertua    = $tertunggak->min('jatuh_tempo');
                $p->jatuh_tempo_tertunggak = $jatuhTempoTertua;
                $p->hari_terlambat   = $jatuhTempoTertua ? (int) Carbon::parse($jatuhTempoTertua)->diffInDays($tanggalPosisi) : 0;
                $p->kolektibilitas   = $this->tentukanKolektibilitas($p->hari_terlambat);

                return $p;
            })
            // Portofolio = pinjaman yang masih punya sisa pokok pada tanggal posisi
            ->filter(fn ($p) => $p->sisa_pokok > 0)
            ->values();

        $ringkasan = $this->hitungRingkasan($baris);
        $sebaran   = $this->hitungSebaran($baris, $ringkasan['total_sisa_pokok']);

        if ($filter) {
            $baris = $baris->where('kolektibilitas', $filter)->values();
        }

        return [
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'cari'           => $cari,
            'filterKolek'    => $filter,
            'awalBulan'      => $awalBulan,
            'tanggalPosisi'  => $tanggalPosisi,
            'baris'          => $baris,
            'ringkasan'      => $ringkasan,
            'sebaran'        => $sebaran,
        ];
    }

    private function tentukanKolektibilitas(int $hariTerlambat): string
    {
        foreach (self::KOLEKTIBILITAS as $kode => $k) {
            if ($hariTerlambat <= $k['maks']) {
                return $kode;
            }
        }

        return 'macet';
    }

    /**
     * Ringkasan portofolio (sebelum filter kolektibilitas agar angka utama tetap menyeluruh).
     */
    private function hitungRingkasan(Collection $baris): array
    {
        $totalSisa = (float) $baris->sum('sisa_pokok');
        $sisaNpl   = (float) $baris->whereIn('kolektibilitas', self::KATEGORI_NPL)->sum('sisa_pokok');

        return [
            'jumlah_pinjaman'      => $baris->count(),
            'total_plafon'         => (float) $baris->sum('nominal_pinjaman'),
            'total_pokok_terbayar' => (float) $baris->sum('pokok_terbayar'),
            'total_jasa_terbayar'  => (float) $baris->sum('jasa_terbayar'),
            'total_sisa_pokok'     => $totalSisa,
            'total_tunggakan'      => (float) $baris->sum('nominal_tunggakan'),
            'jumlah_menunggak'     => $baris->where('bulan_tunggakan', '>', 0)->count(),
            'rasio_npl'            => $totalSisa > 0 ? round($sisaNpl / $totalSisa * 100, 2) : 0,
        ];
    }

    /**
     * Jumlah pinjaman & sisa pokok per kategori kolektibilitas.
     */
    private function hitungSebaran(Collection $baris, float $totalSisa): array
    {
        $sebaran = [];
        foreach (self::KOLEKTIBILITAS as $kode => $k) {
            $kelompok = $baris->where('kolektibilitas', $kode);
            $sisa     = (float) $kelompok->sum('sisa_pokok');

            $sebaran[$kode] = [
                'label'      => $k['label'],
                'jumlah'     => $kelompok->count(),
                'sisa_pokok' => $sisa,
                'persen'     => $totalSisa > 0 ? round($sisa / $totalSisa * 100, 2) : 0,
            ];
        }

        return $sebaran;
    }
}
