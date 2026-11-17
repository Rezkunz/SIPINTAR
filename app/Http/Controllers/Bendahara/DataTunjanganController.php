<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Concerns\TanganiKesalahanInput;
use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\DataTunjanganService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * FR-16 Menyerahkan data tunjangan pegawai (Bendahara Sekolah).
 */
class DataTunjanganController extends Controller
{
    use TanganiKesalahanInput;

    public function index(Request $request)
    {
        [$tahun, $bulan] = $this->periode($request);
        $bulanLalu = Carbon::create($tahun, $bulan, 1)->subMonthNoOverflow();

        $pegawai = $this->daftarPegawai();
        $ids = $pegawai->pluck('nasabah_id')->all();

        $dataBulanIni = DB::table('tblDATA_TUNJANGAN')->whereIn('nasabah_id', $ids)
            ->where('tahun', $tahun)->where('bulan', $bulan)->get()->keyBy('nasabah_id');
        $dataBulanLalu = DB::table('tblDATA_TUNJANGAN')->whereIn('nasabah_id', $ids)
            ->where('tahun', $bulanLalu->year)->where('bulan', $bulanLalu->month)->get()->keyBy('nasabah_id');

        // Angsuran potong gaji yang jatuh tempo bulan ini, sebagai pembanding tunjangan bersih
        $angsuranBulanIni = DB::table('tblJADWAL_ANGSURAN as ja')
            ->join('tblPINJAMAN as p', 'ja.pinjaman_id', '=', 'p.pinjaman_id')
            ->where('p.status_pinjaman', 'cair')->where('p.metode_bayar', 'potong_gaji')
            ->whereYear('ja.jatuh_tempo', $tahun)->whereMonth('ja.jatuh_tempo', $bulan)
            ->groupBy('p.nasabah_id')
            ->select('p.nasabah_id', DB::raw('SUM(ja.pokok + ja.bunga) as total'))
            ->pluck('total', 'p.nasabah_id');

        $jumlahTerisi = $dataBulanIni->count();
        $labelPeriode = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');

        return view('bendahara.tunjangan.index', compact(
            'tahun', 'bulan', 'labelPeriode', 'pegawai', 'dataBulanIni', 'dataBulanLalu', 'angsuranBulanIni', 'jumlahTerisi'
        ));
    }

    public function simpan(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun'                 => 'required|integer|min:2020|max:2100',
            'bulan'                 => 'required|integer|min:1|max:12',
            'data'                  => 'required|array',
            'data.*.gaji_pokok'     => 'nullable|numeric|min:0|max:999999999',
            'data.*.tunjangan'      => 'nullable|numeric|min:0|max:999999999',
            'data.*.potongan_lain'  => 'nullable|numeric|min:0|max:999999999',
            'data.*.keterangan'     => 'nullable|string|max:255',
        ], [
            'data.*.gaji_pokok.min'    => 'Gaji pokok tidak boleh negatif.',
            'data.*.tunjangan.min'     => 'Tunjangan tidak boleh negatif.',
            'data.*.potongan_lain.min' => 'Potongan lain tidak boleh negatif.',
        ]);

        try {
            $tahun = (int) $request->tahun;
            $bulan = (int) $request->bulan;
            $pegawaiSah = $this->daftarPegawai()->pluck('nasabah_id')->all();

            $tersimpan = 0;
            DB::transaction(function () use ($request, $tahun, $bulan, $pegawaiSah, &$tersimpan) {
                foreach ($request->data as $nasabahId => $nilai) {
                    $diisi = collect(['gaji_pokok', 'tunjangan', 'potongan_lain'])
                        ->contains(fn ($k) => isset($nilai[$k]) && $nilai[$k] !== '');
                    if (! $diisi || ! in_array((int) $nasabahId, $pegawaiSah, true)) {
                        continue;
                    }
                    DataTunjanganService::simpan((int) $nasabahId, $tahun, $bulan, $nilai, (int) auth()->id());
                    $tersimpan++;
                }

                AuditLogService::log(
                    aksi: 'SIMPAN_DATA_TUNJANGAN',
                    entitas: 'tblDATA_TUNJANGAN',
                    entitasId: null,
                    dataBaru: ['tahun' => $tahun, 'bulan' => $bulan, 'jumlah_pegawai' => $tersimpan]
                );
            });

            $label = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');

            return redirect()->route('bendahara.tunjangan.index', ['tahun' => $tahun, 'bulan' => $bulan])
                ->with($tersimpan > 0 ? 'success' : 'error', $tersimpan > 0
                    ? "Data tunjangan {$tersimpan} pegawai untuk {$label} berhasil disimpan dan dapat dilihat Bendahara Bank Mini saat penilaian kelayakan."
                    : 'Belum ada data yang diisi. Isi minimal gaji pokok atau tunjangan satu pegawai.');
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menyimpan data tunjangan');
        }
    }

    /** Salin data bulan lalu ke bulan terpilih untuk pegawai yang belum diisi. */
    public function salinBulanLalu(Request $request): RedirectResponse
    {
        $request->validate(['tahun' => 'required|integer', 'bulan' => 'required|integer|min:1|max:12']);

        try {
            $tahun = (int) $request->tahun;
            $bulan = (int) $request->bulan;
            $lalu = Carbon::create($tahun, $bulan, 1)->subMonthNoOverflow();

            $sudahAda = DB::table('tblDATA_TUNJANGAN')->where('tahun', $tahun)->where('bulan', $bulan)->pluck('nasabah_id')->all();
            $sumber = DB::table('tblDATA_TUNJANGAN')->where('tahun', $lalu->year)->where('bulan', $lalu->month)
                ->whereNotIn('nasabah_id', $sudahAda)->whereIn('nasabah_id', $this->daftarPegawai()->pluck('nasabah_id'))->get();

            DB::transaction(function () use ($sumber, $tahun, $bulan) {
                foreach ($sumber as $d) {
                    DataTunjanganService::simpan((int) $d->nasabah_id, $tahun, $bulan, (array) $d, (int) auth()->id());
                }
            });

            return redirect()->route('bendahara.tunjangan.index', ['tahun' => $tahun, 'bulan' => $bulan])
                ->with($sumber->count() ? 'success' : 'error', $sumber->count()
                    ? "{$sumber->count()} data disalin dari {$lalu->translatedFormat('F Y')}. Periksa kembali bila ada perubahan tunjangan atau potongan."
                    : "Tidak ada data {$lalu->translatedFormat('F Y')} yang bisa disalin.");
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menyalin data tunjangan bulan lalu');
        }
    }

    /** Template CSV berisi daftar pegawai (terisi data bulan terpilih bila sudah ada). */
    public function templateCsv(Request $request): StreamedResponse
    {
        [$tahun, $bulan] = $this->periode($request);
        $pegawai = $this->daftarPegawai();
        $data = DB::table('tblDATA_TUNJANGAN')->where('tahun', $tahun)->where('bulan', $bulan)->get()->keyBy('nasabah_id');

        return response()->streamDownload(function () use ($pegawai, $data) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['nasabah_id', 'nama_pegawai', 'unit_kerja', 'gaji_pokok', 'tunjangan', 'potongan_lain', 'keterangan'], ';');
            foreach ($pegawai as $p) {
                $d = $data->get($p->nasabah_id);
                fputcsv($out, [
                    $p->nasabah_id, $p->nama_nasabah, $p->kelas_unit,
                    $d ? (int) $d->gaji_pokok : '', $d ? (int) $d->tunjangan : '', $d ? (int) $d->potongan_lain : '', $d->keterangan ?? '',
                ], ';');
            }
            fclose($out);
        }, sprintf('DATA_TUNJANGAN_%04d_%02d.csv', $tahun, $bulan), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Impor CSV (kolom: nasabah_id;nama_pegawai;unit_kerja;gaji_pokok;tunjangan;potongan_lain;keterangan). */
    public function imporCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun' => 'required|integer|min:2020|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'file'  => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'file.required' => 'Pilih file CSV data tunjangan.',
            'file.mimes'    => 'File harus berformat CSV (gunakan template yang disediakan).',
        ]);

        try {
            $tahun = (int) $request->tahun;
            $bulan = (int) $request->bulan;
            $pegawaiSah = $this->daftarPegawai()->pluck('nasabah_id')->all();

            $baris = file($request->file('file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $baris[0] = preg_replace('/^\xEF\xBB\xBF/', '', $baris[0] ?? '');
            $pemisah = substr_count($baris[0] ?? '', ';') >= substr_count($baris[0] ?? '', ',') ? ';' : ',';
            array_shift($baris); // header

            $angka = fn ($v) => ($v = preg_replace('/[^0-9]/', '', (string) $v)) === '' ? null : (float) $v;

            $tersimpan = 0;
            $galat = [];
            DB::transaction(function () use ($baris, $pemisah, $angka, $tahun, $bulan, $pegawaiSah, &$tersimpan, &$galat) {
                foreach ($baris as $i => $isi) {
                    $kolom = str_getcsv($isi, $pemisah);
                    $nasabahId = (int) ($kolom[0] ?? 0);
                    $nilai = [
                        'gaji_pokok'    => $angka($kolom[3] ?? ''),
                        'tunjangan'     => $angka($kolom[4] ?? ''),
                        'potongan_lain' => $angka($kolom[5] ?? '') ?? 0,
                        'keterangan'    => $kolom[6] ?? null,
                    ];

                    if (! in_array($nasabahId, $pegawaiSah, true)) {
                        $galat[] = 'baris ' . ($i + 2) . ': ID pegawai tidak dikenal';
                        continue;
                    }
                    if ($nilai['gaji_pokok'] === null && $nilai['tunjangan'] === null) {
                        continue; // baris kosong, lewati
                    }
                    DataTunjanganService::simpan($nasabahId, $tahun, $bulan, $nilai, (int) auth()->id());
                    $tersimpan++;
                }

                AuditLogService::log(
                    aksi: 'IMPOR_DATA_TUNJANGAN',
                    entitas: 'tblDATA_TUNJANGAN',
                    entitasId: null,
                    dataBaru: ['tahun' => $tahun, 'bulan' => $bulan, 'jumlah_pegawai' => $tersimpan, 'baris_ditolak' => count($galat)]
                );
            });

            $pesan = "Impor selesai: {$tersimpan} data pegawai tersimpan.";
            if ($galat) {
                $pesan .= ' ' . count($galat) . ' baris dilewati (' . implode('; ', array_slice($galat, 0, 3)) . ').';
            }

            return redirect()->route('bendahara.tunjangan.index', ['tahun' => $tahun, 'bulan' => $bulan])
                ->with($tersimpan > 0 ? 'success' : 'error', $pesan);
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'mengimpor data tunjangan dari CSV');
        }
    }

    private function periode(Request $request): array
    {
        $tahun = (int) $request->query('tahun', now()->year);
        $bulan = (int) $request->query('bulan', now()->month);

        return [
            ($tahun >= 2020 && $tahun <= 2100) ? $tahun : now()->year,
            ($bulan >= 1 && $bulan <= 12) ? $bulan : now()->month,
        ];
    }

    private function daftarPegawai()
    {
        return DB::table('tblNASABAH')
            ->where('jenis_nasabah', 'pegawai')
            ->where('status_nasabah', 'aktif')
            ->orderBy('nama_nasabah')
            ->get(['nasabah_id', 'nama_nasabah', 'kelas_unit', 'gaji_pokok', 'tunjangan']);
    }
}
