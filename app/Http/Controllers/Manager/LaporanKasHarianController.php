<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\EksporDokumen;
use App\Services\EksporLaporanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKasHarianController extends Controller
{
    use EksporDokumen;

    /**
     * Arus kas tunai tabungan: setoran (1) menambah kas, penarikan (2) mengurangi kas.
     * Jasa tabungan tidak dihitung karena hanya dibukukan ke saldo, tanpa uang fisik.
     */
    private const JENIS_TRANSAKSI_KAS = [1, 2];

    private const PER_HALAMAN_RINCIAN = 10;

    /**
     * Buku kas harian tabungan: ringkasan per tanggal + rincian transaksi tanggal terpilih.
     */
    public function index(Request $request)
    {
        $buku = $this->susunBukuKas($request);

        $tanggal = $request->filled('tanggal')
            ? Carbon::parse($request->validate(['tanggal' => 'date_format:Y-m-d'])['tanggal'])
            : null;

        // Rincian hanya untuk tanggal di dalam bulan yang sedang ditampilkan
        $buku['tanggalTerpilih'] = $tanggal && $tanggal->isSameMonth($buku['awalBulan']) ? $tanggal : null;

        $buku['rincian'] = $buku['tanggalTerpilih']
            ? $this->queryRincian($buku['tanggalTerpilih'], $buku['tanggalTerpilih'])
                ->paginate(self::PER_HALAMAN_RINCIAN)
                ->withQueryString()
                ->fragment('rincian')
            : null;

        return view('manager.laporan.kas_harian', $buku);
    }

    /**
     * Unduh buku kas harian beserta rincian transaksi sebulan dalam format CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $buku    = $this->susunBukuKas($request);
        $rincian = $this->queryRincian($buku['awalBulan'], $buku['akhirBulan'])->get();

        $namaBulan = strtoupper($buku['awalBulan']->translatedFormat('F'));
        $filename  = "BUKU_KAS_HARIAN_{$namaBulan}_{$buku['tahun']}.csv";

        return response()->streamDownload(function () use ($buku, $rincian) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($output, ['BUKU KAS HARIAN'], ';');
            fputcsv($output, ['BANK MINI WIYATA CIPTA NIAGA'], ';');
            fputcsv($output, ['BULAN ' . strtoupper($buku['awalBulan']->translatedFormat('F Y'))], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['NO', 'TANGGAL', 'KAS AWAL', 'TAB. SISWA PENERIMAAN', 'TAB. SISWA PENGELUARAN', 'TAB. GURU PENERIMAAN', 'TAB. GURU PENGELUARAN', 'JUMLAH PENERIMAAN', 'JUMLAH PENGELUARAN', 'KAS AKHIR'], ';');
            foreach ($buku['hari'] as $i => $h) {
                fputcsv($output, [
                    $i + 1, $h->tanggal->format('d/m/Y'), $h->kas_awal,
                    $h->siswa_masuk, $h->siswa_keluar, $h->guru_masuk, $h->guru_keluar,
                    $h->total_masuk, $h->total_keluar, $h->kas_akhir,
                ], ';');
            }
            $t = $buku['total'];
            fputcsv($output, ['', 'JUMLAH', '', $t['siswa_masuk'], $t['siswa_keluar'], $t['guru_masuk'], $t['guru_keluar'], $t['total_masuk'], $t['total_keluar'], $buku['kasAkhirBulan']], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['RINCIAN TRANSAKSI'], ';');
            fputcsv($output, ['NO', 'TANGGAL', 'NO REKENING', 'NAMA', 'TAB. PELAJAR DEBET', 'TAB. PELAJAR KREDIT', 'TAB. GURU DEBET', 'TAB. GURU KREDIT'], ';');
            // Nominal hanya diisi pada kolom kategori nasabahnya; nol ditampilkan kosong
            $kolom = fn ($tampil, $nilai) => ($tampil && (float) $nilai != 0) ? (float) $nilai : '';
            foreach ($rincian as $i => $r) {
                $isGuru = $r->jenis_nasabah === 'pegawai';
                fputcsv($output, [
                    $i + 1, Carbon::parse($r->tgl_transaksi)->format('d/m/Y H:i'), $r->no_rek, $r->nama_nasabah,
                    $kolom(!$isGuru, $r->debit), $kolom(!$isGuru, $r->kredit),
                    $kolom($isGuru, $r->debit), $kolom($isGuru, $r->kredit),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Dokumen PDF/Excel buku kas harian: ringkasan per tanggal + rincian transaksi sebulan.
     */
    protected function dokumenEkspor(Request $request): array
    {
        $buku    = $this->susunBukuKas($request);
        $rincian = $this->queryRincian($buku['awalBulan'], $buku['akhirBulan'])->get();
        $t       = $buku['total'];
        $kosongBilaNol = fn ($n) => (float) $n != 0 ? (float) $n : null;

        $ringkasan = $buku['hari']->values()->map(fn ($h, $i) => EksporLaporanService::baris([
            $i + 1, $h->tanggal->format('d/m/Y'), $h->kas_awal,
            $kosongBilaNol($h->siswa_masuk), $kosongBilaNol($h->siswa_keluar),
            $kosongBilaNol($h->guru_masuk), $kosongBilaNol($h->guru_keluar),
            $h->total_masuk, $h->total_keluar, $h->kas_akhir,
        ]))->all();

        $detail = $rincian->values()->map(function ($r, $i) use ($kosongBilaNol) {
            $isGuru = $r->jenis_nasabah === 'pegawai';

            return EksporLaporanService::baris([
                $i + 1, Carbon::parse($r->tgl_transaksi)->format('d/m/Y H:i'), $r->no_rek, $r->nama_nasabah,
                $isGuru ? null : $kosongBilaNol($r->debit), $isGuru ? null : $kosongBilaNol($r->kredit),
                $isGuru ? $kosongBilaNol($r->debit) : null, $isGuru ? $kosongBilaNol($r->kredit) : null,
            ]);
        })->all();

        return [
            'judul'       => 'Buku Kas Harian Tabungan',
            'subjudul'    => 'Bulan ' . $buku['awalBulan']->translatedFormat('F Y'),
            'nama_file'   => 'BUKU_KAS_HARIAN_' . strtoupper($buku['awalBulan']->translatedFormat('F')) . "_{$buku['tahun']}",
            'orientasi'   => 'landscape',
            'ukuran_font' => 8,
            'bagian'      => [
                [
                    'grup_kolom' => [
                        ['label' => ''], ['label' => ''], ['label' => ''],
                        ['label' => 'Tab. Siswa', 'span' => 2], ['label' => 'Tab. Guru / Karyawan', 'span' => 2],
                        ['label' => 'Jumlah', 'span' => 2], ['label' => ''],
                    ],
                    'kolom' => [
                        ['label' => 'No', 'align' => 'center'], ['label' => 'Tanggal'], ['label' => 'Kas Awal', 'angka' => true],
                        ['label' => 'Penerimaan', 'angka' => true], ['label' => 'Pengeluaran', 'angka' => true],
                        ['label' => 'Penerimaan', 'angka' => true], ['label' => 'Pengeluaran', 'angka' => true],
                        ['label' => 'Penerimaan', 'angka' => true], ['label' => 'Pengeluaran', 'angka' => true],
                        ['label' => 'Kas Akhir', 'angka' => true],
                    ],
                    'baris' => $ringkasan,
                    'kaki'  => [EksporLaporanService::baris([
                        '', 'JUMLAH', $buku['kasAwalBulan'], $t['siswa_masuk'], $t['siswa_keluar'],
                        $t['guru_masuk'], $t['guru_keluar'], $t['total_masuk'], $t['total_keluar'], $buku['kasAkhirBulan'],
                    ], 'total')],
                    'catatan' => 'Kas awal bulan = akumulasi setoran dikurangi penarikan tabungan sebelum tanggal 1; jasa tabungan tidak dihitung karena bukan uang kas.',
                ],
                [
                    'judul'      => 'Rincian Transaksi',
                    'grup_kolom' => [
                        ['label' => ''], ['label' => ''], ['label' => ''], ['label' => ''],
                        ['label' => 'Tabungan Pelajar', 'span' => 2], ['label' => 'Tab. Guru / Karyawan', 'span' => 2],
                    ],
                    'kolom' => [
                        ['label' => 'No', 'align' => 'center'], ['label' => 'Waktu'], ['label' => 'No Rekening'], ['label' => 'Nama'],
                        ['label' => 'Debet', 'angka' => true], ['label' => 'Kredit', 'angka' => true],
                        ['label' => 'Debet', 'angka' => true], ['label' => 'Kredit', 'angka' => true],
                    ],
                    'baris'   => $detail,
                    'catatan' => 'Debet = penarikan (kas keluar), Kredit = setoran (kas masuk).',
                ],
            ],
        ];
    }

    /**
     * Hitung kas awal bulan lalu susun ringkasan per tanggal yang ada transaksinya.
     */
    private function susunBukuKas(Request $request): array
    {
        $validated = $request->validate([
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|between:2000,2100',
        ]);

        $bulan = (int) ($validated['bulan'] ?? now()->month);
        $tahun = (int) ($validated['tahun'] ?? now()->year);

        $awalBulan  = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        // Kas awal bulan = akumulasi setoran - penarikan sebelum tanggal 1
        $kasAwalBulan = (float) DB::table('tblTRANSAKSI')
            ->whereIn('jenis_transaksi_id', self::JENIS_TRANSAKSI_KAS)
            ->where('tgl_transaksi', '<', $awalBulan)
            ->sum(DB::raw('kredit - debit'));

        $tblTx = DB::getDriverName() === 'pgsql' ? '"tblTRANSAKSI"' : 'tblTRANSAKSI';
        $tglCol = "{$tblTx}.tgl_transaksi";

        $perHari = DB::table('tblTRANSAKSI')
            ->join('tblREKENING', 'tblTRANSAKSI.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->whereIn('tblTRANSAKSI.jenis_transaksi_id', self::JENIS_TRANSAKSI_KAS)
            ->whereBetween('tblTRANSAKSI.tgl_transaksi', [$awalBulan, $akhirBulan])
            ->groupBy(DB::raw("DATE({$tglCol})"), 'tblNASABAH.jenis_nasabah')
            ->select(
                DB::raw("DATE({$tglCol}) as tanggal"),
                'tblNASABAH.jenis_nasabah',
                DB::raw("SUM({$tblTx}.kredit) as masuk"),
                DB::raw("SUM({$tblTx}.debit) as keluar"),
                DB::raw('COUNT(*) as jumlah_transaksi')
            )
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal');

        $kasBerjalan = $kasAwalBulan;
        $hari = $perHari->map(function ($baris, $tanggal) use (&$kasBerjalan) {
            $siswa = $baris->firstWhere('jenis_nasabah', 'non_pegawai');
            $guru  = $baris->firstWhere('jenis_nasabah', 'pegawai');

            $h = (object) [
                'tanggal'          => Carbon::parse($tanggal),
                'kas_awal'         => $kasBerjalan,
                'siswa_masuk'      => (float) ($siswa->masuk ?? 0),
                'siswa_keluar'     => (float) ($siswa->keluar ?? 0),
                'guru_masuk'       => (float) ($guru->masuk ?? 0),
                'guru_keluar'      => (float) ($guru->keluar ?? 0),
                'jumlah_transaksi' => (int) $baris->sum('jumlah_transaksi'),
            ];
            $h->total_masuk  = $h->siswa_masuk + $h->guru_masuk;
            $h->total_keluar = $h->siswa_keluar + $h->guru_keluar;
            $h->kas_akhir    = $h->kas_awal + $h->total_masuk - $h->total_keluar;

            $kasBerjalan = $h->kas_akhir;

            return $h;
        })->values();

        $total = [
            'siswa_masuk'  => (float) $hari->sum('siswa_masuk'),
            'siswa_keluar' => (float) $hari->sum('siswa_keluar'),
            'guru_masuk'   => (float) $hari->sum('guru_masuk'),
            'guru_keluar'  => (float) $hari->sum('guru_keluar'),
            'total_masuk'  => (float) $hari->sum('total_masuk'),
            'total_keluar' => (float) $hari->sum('total_keluar'),
            'transaksi'    => (int) $hari->sum('jumlah_transaksi'),
        ];

        return [
            'bulan'         => $bulan,
            'tahun'         => $tahun,
            'awalBulan'     => $awalBulan,
            'akhirBulan'    => $akhirBulan,
            'kasAwalBulan'  => $kasAwalBulan,
            'kasAkhirBulan' => $kasBerjalan,
            'hari'          => $hari,
            'total'         => $total,
        ];
    }

    /**
     * Daftar transaksi kas tabungan dalam rentang tanggal, urut kronologis.
     */
    private function queryRincian(Carbon $dari, Carbon $sampai)
    {
        return DB::table('tblTRANSAKSI')
            ->join('tblREKENING', 'tblTRANSAKSI.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->whereIn('tblTRANSAKSI.jenis_transaksi_id', self::JENIS_TRANSAKSI_KAS)
            ->whereBetween('tblTRANSAKSI.tgl_transaksi', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->orderBy('tblTRANSAKSI.tgl_transaksi')
            ->orderBy('tblTRANSAKSI.transaksi_id')
            ->select(
                'tblTRANSAKSI.transaksi_id',
                'tblTRANSAKSI.tgl_transaksi',
                'tblTRANSAKSI.debit',
                'tblTRANSAKSI.kredit',
                'tblREKENING.no_rek',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.jenis_nasabah'
            );
    }
}
