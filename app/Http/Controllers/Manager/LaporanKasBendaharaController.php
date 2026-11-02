<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\EksporDokumen;
use App\Services\EksporLaporanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKasBendaharaController extends Controller
{
    use EksporDokumen;

    /**
     * Pilihan sumber kas → nilai tblKAS.jenis_kas (null = gabungan tunai & bank).
     */
    private const SUMBER_KAS = [
        'semua' => ['jenis' => null,   'label' => 'Tunai + Bank'],
        'tunai' => ['jenis' => 'kas',  'label' => 'Kas Tunai'],
        'bank'  => ['jenis' => 'bank', 'label' => 'Kas Bank'],
    ];

    private const PER_HALAMAN = 10;

    /**
     * Buku kas harian bendahara bank mini (kas pinjaman) beserta rekap penerimaan angsuran.
     */
    public function index(Request $request)
    {
        $buku = $this->susunBukuKas($request);

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $buku['baris'] = new LengthAwarePaginator(
            $buku['baris']->forPage($halaman, self::PER_HALAMAN)->values(),
            $buku['baris']->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('manager.laporan.kas_bendahara', $buku);
    }

    /**
     * Unduh buku kas bendahara & rekap angsuran dalam format CSV (kompatibel Excel).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $buku = $this->susunBukuKas($request);

        $namaBulan = strtoupper($buku['awalBulan']->translatedFormat('F'));
        $filename  = "KAS_BENDAHARA_" . strtoupper($buku['sumber']) . "_{$namaBulan}_{$buku['tahun']}.csv";

        return response()->streamDownload(function () use ($buku) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['KAS HARIAN BENDAHARA BANK MINI'], ';');
            fputcsv($output, ['BULAN ' . strtoupper($buku['awalBulan']->translatedFormat('F Y')) . ' (' . strtoupper($buku['labelSumber']) . ')'], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['TANGGAL', 'KAS', 'URAIAN', 'PEMASUKAN LAIN-LAIN', 'PEMASUKAN POKOK', 'PEMASUKAN JASA', 'PENGELUARAN', 'SALDO'], ';');
            fputcsv($output, ['', '', 'Saldo awal', '', '', '', '', $buku['saldoAwal']], ';');

            foreach ($buku['baris'] as $b) {
                fputcsv($output, [
                    $b->tanggal->format('d/m/Y'), $b->label_kas, $b->uraian,
                    $b->lain_lain ?: '', $b->pokok ?: '', $b->jasa ?: '', $b->pengeluaran ?: '', $b->saldo,
                ], ';');
            }

            $t = $buku['total'];
            fputcsv($output, ['', '', 'JUMLAH', $t['lain_lain'], $t['pokok'], $t['jasa'], $t['pengeluaran'], $buku['saldoAkhir']], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['REKAP PENERIMAAN ANGSURAN'], ';');
            fputcsv($output, ['SALURAN', 'JUMLAH TRANSAKSI', 'POKOK', 'JASA', 'TOTAL'], ';');
            foreach ($buku['rekapAngsuran']['saluran'] as $s) {
                fputcsv($output, [$s['label'], $s['jumlah'], $s['pokok'], $s['jasa'], $s['pokok'] + $s['jasa']], ';');
            }
            $ra = $buku['rekapAngsuran']['bersih'];
            fputcsv($output, ['PENERIMAAN BERSIH', '', $ra['pokok'], $ra['jasa'], $ra['pokok'] + $ra['jasa']], ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Dokumen PDF/Excel kas harian bendahara (format KAS BENDAHARA) & rekap penerimaan angsuran.
     */
    protected function dokumenEkspor(Request $request): array
    {
        $buku = $this->susunBukuKas($request);
        $t    = $buku['total'];
        $kosongBilaNol = fn ($n) => (float) $n != 0 ? (float) $n : null;

        $baris = [EksporLaporanService::baris(['01/' . $buku['awalBulan']->format('m/Y'), '', 'Saldo awal', null, null, null, null, $buku['saldoAwal']], 'grup')];
        foreach ($buku['baris'] as $b) {
            $baris[] = EksporLaporanService::baris([
                $b->tanggal->format('d/m/Y'), $b->label_kas, $b->uraian,
                $kosongBilaNol($b->lain_lain), $kosongBilaNol($b->pokok), $kosongBilaNol($b->jasa), $kosongBilaNol($b->pengeluaran), $b->saldo,
            ]);
        }

        $saluran = collect($buku['rekapAngsuran']['saluran'])->map(function ($s, $kode) {
            $tanda = $kode === 'koreksi' ? -1 : 1;

            return EksporLaporanService::baris([$s['label'], $s['jumlah'], $tanda * $s['pokok'], $tanda * $s['jasa'], $tanda * ($s['pokok'] + $s['jasa'])]);
        })->values()->all();
        $bersih = $buku['rekapAngsuran']['bersih'];

        return [
            'judul'       => 'Kas Harian Bendahara Bank Mini',
            'subjudul'    => 'Bulan ' . $buku['awalBulan']->translatedFormat('F Y') . ' (' . $buku['labelSumber'] . ')',
            'nama_file'   => 'KAS_BENDAHARA_' . strtoupper($buku['sumber']) . '_' . strtoupper($buku['awalBulan']->translatedFormat('F')) . "_{$buku['tahun']}",
            'orientasi'   => 'landscape',
            'ukuran_font' => 8,
            'bagian'      => [
                [
                    'grup_kolom' => [
                        ['label' => ''], ['label' => ''], ['label' => ''],
                        ['label' => 'Pemasukan', 'span' => 3], ['label' => ''], ['label' => ''],
                    ],
                    'kolom' => [
                        ['label' => 'Tanggal'], ['label' => 'Kas'], ['label' => 'Uraian'],
                        ['label' => 'Lain-lain', 'angka' => true], ['label' => 'Pokok', 'angka' => true], ['label' => 'Jasa', 'angka' => true],
                        ['label' => 'Pengeluaran', 'angka' => true], ['label' => 'Saldo', 'angka' => true],
                    ],
                    'baris' => $baris,
                    'kaki'  => [EksporLaporanService::baris(['', '', 'JUMLAH', $t['lain_lain'], $t['pokok'], $t['jasa'], $t['pengeluaran'], $buku['saldoAkhir']], 'total')],
                ],
                [
                    'judul' => 'Rekap Penerimaan Angsuran',
                    'kolom' => [
                        ['label' => 'Saluran Pembayaran'], ['label' => 'Jumlah Transaksi', 'angka' => true],
                        ['label' => 'Pokok', 'angka' => true], ['label' => 'Jasa', 'angka' => true], ['label' => 'Total', 'angka' => true],
                    ],
                    'baris' => $saluran,
                    'kaki'  => [EksporLaporanService::baris(['Penerimaan Bersih', null, $bersih['pokok'], $bersih['jasa'], $bersih['pokok'] + $bersih['jasa']], 'total')],
                    'catatan' => 'Koreksi angsuran (pembalik) mengurangi penerimaan.',
                ],
            ],
        ];
    }

    /**
     * Susun mutasi kas sebulan dengan pemisahan pemasukan pokok/jasa/lain-lain dan saldo berjalan.
     */
    private function susunBukuKas(Request $request): array
    {
        $validated = $request->validate([
            'bulan'  => 'nullable|integer|between:1,12',
            'tahun'  => 'nullable|integer|between:2000,2100',
            'sumber' => 'nullable|in:' . implode(',', array_keys(self::SUMBER_KAS)),
        ]);

        $bulan  = (int) ($validated['bulan'] ?? now()->month);
        $tahun  = (int) ($validated['tahun'] ?? now()->year);
        $sumber = $validated['sumber'] ?? 'semua';

        $awalBulan  = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $kasIds = DB::table('tblKAS')
            ->when(self::SUMBER_KAS[$sumber]['jenis'], fn ($q, $jenis) => $q->where('jenis_kas', $jenis))
            ->pluck('kas_id')
            ->all();

        // Saldo awal dihitung mundur dari saldo kas saat ini. Dana tabungan nasabah yang ikut tersimpan
        // di kas (mutasi bertaut tblTRANSAKSI) bukan bagian Buku Kas Bendahara (acuan data_contoh/KAS
        // BENDAHARA), jadi dikeluarkan dari saldo maupun baris; dana itu dilaporkan di Laporan Tabungan
        $netto = DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END");
        $danaTabungan = (float) DB::table('tblMUTASI_KAS')
            ->whereIn('kas_id', $kasIds)
            ->whereNotNull('transaksi_id')
            ->sum($netto);
        $saldoSekarang  = (float) DB::table('tblKAS')->whereIn('kas_id', $kasIds)->sum('saldo') - $danaTabungan;
        $mutasiSesudah  = (float) DB::table('tblMUTASI_KAS')
            ->whereIn('kas_id', $kasIds)
            ->whereNull('transaksi_id')
            ->whereDate('tgl_mutasi', '>=', $awalBulan)
            ->sum($netto);
        $saldoAwal = $saldoSekarang - $mutasiSesudah;

        $mutasi = DB::table('tblMUTASI_KAS as mk')
            ->join('tblKAS as k', 'mk.kas_id', '=', 'k.kas_id')
            ->leftJoin('tblANGSURAN as ang', 'mk.angsuran_id', '=', 'ang.angsuran_id')
            ->whereIn('mk.kas_id', $kasIds)
            ->whereNull('mk.transaksi_id')
            ->whereBetween('mk.tgl_mutasi', [$awalBulan->toDateString(), $akhirBulan->toDateString()])
            ->orderBy('mk.tgl_mutasi')
            ->orderBy('mk.mutasi_kas_id')
            ->select(
                'mk.mutasi_kas_id', 'mk.tgl_mutasi', 'mk.arah', 'mk.nominal', 'mk.uraian',
                'mk.angsuran_id', 'mk.pencairan_id', 'k.jenis_kas',
                'ang.bayar_pokok', 'ang.bayar_bunga'
            )
            ->get();

        $pokokJasaPaket = $this->pokokJasaPaketPotongan($mutasi);

        $saldo = $saldoAwal;
        $rekap = [
            'loket'   => ['label' => 'Tunai di Loket',  'jumlah' => 0, 'pokok' => 0.0, 'jasa' => 0.0],
            'potong'  => ['label' => 'Potong Gaji',     'jumlah' => 0, 'pokok' => 0.0, 'jasa' => 0.0],
            'koreksi' => ['label' => 'Koreksi Angsuran (pembalik)', 'jumlah' => 0, 'pokok' => 0.0, 'jasa' => 0.0],
        ];

        $baris = $mutasi->map(function ($m) use (&$saldo, &$rekap, $pokokJasaPaket) {
            $nominal = (float) $m->nominal;
            $b = (object) [
                'tanggal'     => Carbon::parse($m->tgl_mutasi),
                'label_kas'   => $m->jenis_kas === 'bank' ? 'Bank' : 'Tunai',
                'uraian'      => $m->uraian,
                'jenis'       => 'manual',
                'lain_lain'   => 0.0,
                'pokok'       => 0.0,
                'jasa'        => 0.0,
                'pengeluaran' => 0.0,
            ];

            if ($m->arah === 'masuk') {
                if ($m->angsuran_id) {
                    $b->jenis = 'angsuran';
                    $b->pokok = (float) $m->bayar_pokok;
                    $b->jasa  = (float) $m->bayar_bunga;
                    $this->tambahRekap($rekap['loket'], $b->pokok, $b->jasa);
                } elseif (isset($pokokJasaPaket[$m->mutasi_kas_id])) {
                    $b->jenis = 'potongan';
                    $b->pokok = $pokokJasaPaket[$m->mutasi_kas_id]['pokok'];
                    $b->jasa  = $pokokJasaPaket[$m->mutasi_kas_id]['jasa'];
                    $this->tambahRekap($rekap['potong'], $b->pokok, $b->jasa);
                }
                // Selisih (atau seluruh nominal untuk kas manual) dicatat sebagai lain-lain
                $b->lain_lain = round($nominal - $b->pokok - $b->jasa, 2);
                $saldo += $nominal;
            } else {
                $b->pengeluaran = $nominal;
                if ($m->pencairan_id) {
                    $b->jenis = 'pencairan';
                } elseif ($m->angsuran_id) {
                    $b->jenis = 'koreksi';
                    $this->tambahRekap($rekap['koreksi'], (float) $m->bayar_pokok, (float) $m->bayar_bunga);
                }
                $saldo -= $nominal;
            }

            $b->saldo = $saldo;

            return $b;
        });

        $total = [
            'lain_lain'   => (float) $baris->sum('lain_lain'),
            'pokok'       => (float) $baris->sum('pokok'),
            'jasa'        => (float) $baris->sum('jasa'),
            'pengeluaran' => (float) $baris->sum('pengeluaran'),
            'pencairan'   => (float) $baris->where('jenis', 'pencairan')->sum('pengeluaran'),
            'jumlah_pencairan' => $baris->where('jenis', 'pencairan')->count(),
        ];
        $total['pemasukan'] = $total['lain_lain'] + $total['pokok'] + $total['jasa'];

        $rekapAngsuran = [
            'saluran' => $rekap,
            'bersih'  => [
                'pokok' => $rekap['loket']['pokok'] + $rekap['potong']['pokok'] - $rekap['koreksi']['pokok'],
                'jasa'  => $rekap['loket']['jasa'] + $rekap['potong']['jasa'] - $rekap['koreksi']['jasa'],
            ],
        ];

        return [
            'bulan'         => $bulan,
            'tahun'         => $tahun,
            'sumber'        => $sumber,
            'labelSumber'   => self::SUMBER_KAS[$sumber]['label'],
            'daftarSumber'  => self::SUMBER_KAS,
            'awalBulan'     => $awalBulan,
            'saldoAwal'     => $saldoAwal,
            'saldoAkhir'    => $saldo,
            'baris'         => $baris,
            'total'         => $total,
            'rekapAngsuran' => $rekapAngsuran,
        ];
    }

    /**
     * Mutasi penerimaan paket potong gaji hanya mencatat total; pokok & jasa diambil dari
     * angsuran tiap pegawai pada paket tersebut (#RP-{id} di uraian mutasi).
     */
    private function pokokJasaPaketPotongan($mutasi): array
    {
        $paketPerMutasi = $mutasi
            ->filter(fn ($m) => $m->arah === 'masuk' && !$m->angsuran_id
                && preg_match('/potongan gaji paket #RP-(\d+)/i', (string) $m->uraian))
            ->mapWithKeys(function ($m) {
                preg_match('/#RP-(\d+)/i', $m->uraian, $cocok);

                return [$m->mutasi_kas_id => (int) $cocok[1]];
            });

        if ($paketPerMutasi->isEmpty()) {
            return [];
        }

        // Koreksi yang terjadi kemudian dicatat sebagai mutasi keluar tersendiri, jadi di sini
        // seluruh angsuran paket dihitung sesuai kondisi saat dana diterima
        $perPaket = DB::table('tblANGSURAN as ang')
            ->join('tblDETAIL_POTONGAN as dp', 'ang.detail_potongan_id', '=', 'dp.detail_potongan_id')
            ->whereIn('dp.rencana_potongan_id', $paketPerMutasi->values()->unique()->all())
            ->groupBy('dp.rencana_potongan_id')
            ->select(
                'dp.rencana_potongan_id',
                DB::raw('SUM(ang.bayar_pokok) as pokok'),
                DB::raw('SUM(ang.bayar_bunga) as jasa')
            )
            ->get()
            ->keyBy('rencana_potongan_id');

        return $paketPerMutasi
            ->filter(fn ($rencanaId) => $perPaket->has($rencanaId))
            ->map(fn ($rencanaId) => [
                'pokok' => (float) $perPaket[$rencanaId]->pokok,
                'jasa'  => (float) $perPaket[$rencanaId]->jasa,
            ])
            ->all();
    }

    private function tambahRekap(array &$saluran, float $pokok, float $jasa): void
    {
        $saluran['jumlah']++;
        $saluran['pokok'] += $pokok;
        $saluran['jasa']  += $jasa;
    }
}
