<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\PeriodeLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PotonganSekolahController extends Controller
{
    /**
     * Tampilkan daftar rencana potongan tunjangan gaji (Read-Only).
     */
    public function index()
    {
        $rencanaList = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->join('tblUSER as u', 'rp.user_id', '=', 'u.user_id')
            ->select('rp.*', 'p.bulan', 'p.tahun', 'u.nama_user as nama_pembuat')
            ->orderByDesc('rp.rencana_potongan_id')
            ->paginate(10);

        // Ambil rincian detail per paket untuk Floating Modal Banner (selaras dengan BBM)
        $rencanaIds = $rencanaList->pluck('rencana_potongan_id');
        $rincianPerPaket = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->whereIn('dp.rencana_potongan_id', $rencanaIds)
            ->select(
                'dp.*',
                'ja.angsuran_ke',
                'ja.jatuh_tempo',
                'ja.pokok',
                'ja.bunga',
                'ja.status_bayar as status_angsuran',
                'pin.tenor',
                'n.nama_nasabah',
                'n.kelas_unit',
                'n.gaji_pokok',
                'n.tunjangan'
            )
            ->orderBy('n.nama_nasabah')
            ->get()
            ->groupBy('rencana_potongan_id');

        return view('bendahara.potongan.index', compact('rencanaList', 'rincianPerPaket'));
    }

    /**
     * Unduh daftar potongan pegawai dalam format CSV (kompatibel Excel & Bank Mitra Payroll).
     * Sesuai Use Case: Memantau & Mengunduh Daftar Potongan.
     */
    public function downloadCsv($rencanaId): StreamedResponse
    {
        $rencana = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->where('rp.rencana_potongan_id', $rencanaId)
            ->select('rp.*', 'p.bulan', 'p.tahun')
            ->first();

        if (!$rencana) {
            abort(404, 'Data rencana potongan tidak ditemukan.');
        }

        $detailList = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('dp.rencana_potongan_id', $rencanaId)
            ->select(
                'n.nasabah_id',
                'n.nama_nasabah',
                'n.kelas_unit',
                'n.gaji_pokok',
                'n.tunjangan',
                'ja.angsuran_ke',
                'ja.pokok',
                'ja.bunga',
                'ja.jatuh_tempo',
                'dp.nominal_rencana'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $labelBulan = $namaBulan[(int)$rencana->bulan] ?? "Bulan-{$rencana->bulan}";
        $filename = "DAFTAR_POTONGAN_GAJI_{$labelBulan}_{$rencana->tahun}.csv";

        return response()->streamDownload(function () use ($detailList) {
            $output = fopen('php://output', 'w');
            // Menulis UTF-8 BOM agar Excel membacanya rapi tanpa karakter rusak
            fputs($output, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($output, [
                'No',
                'ID Nasabah',
                'Nama Pegawai',
                'Unit Kerja',
                'Gaji Pokok (Rp)',
                'Tunjangan (Rp)',
                'Angsuran Ke',
                'Potongan Pokok (Rp)',
                'Jasa Bunga 2% (Rp)',
                'Total Potongan (Rp)',
                'Jatuh Tempo'
            ]);

            $no = 1;
            foreach ($detailList as $row) {
                fputcsv($output, [
                    $no++,
                    'NSB-' . str_pad($row->nasabah_id, 4, '0', STR_PAD_LEFT),
                    $row->nama_nasabah,
                    $row->kelas_unit ?? '-',
                    number_format($row->gaji_pokok ?? 0, 0, '', ''),
                    number_format($row->tunjangan ?? 0, 0, '', ''),
                    $row->angsuran_ke,
                    number_format($row->pokok, 0, '', ''),
                    number_format($row->bunga, 0, '', ''),
                    number_format($row->nominal_rencana, 0, '', ''),
                    $row->jatuh_tempo,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Tampilkan formulir pencatatan hasil potongan gaji per pegawai.
     * Mengarahkan ke Banner Pop-Up di halaman utama agar seragam dengan Bank Mini.
     */
    public function formHasil($rencanaId)
    {
        return redirect()->route('bendahara.potongan.index', ['open' => $rencanaId]);
    }

    /**
     * Simpan realisasi hasil pemotongan tunjangan per pegawai oleh Bendahara Sekolah.
     * Status paket berubah menjadi 'menunggu_verifikasi' agar uang fisik/transfer diverifikasi oleh Bank Mini.
     */
    public function simpanHasil(Request $request, $rencanaId)
    {
        $request->validate([
            'hasil'             => 'required|array',
            'tgl_potongan'      => 'required|date',
            'metode_penyerahan' => 'required|in:tunai,transfer',
            'catatan_sekolah'   => 'nullable|string|max:500',
            'keterangan'        => 'nullable|array',
            'bukti_transfer'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], [
            'hasil.required'             => 'Daftar hasil pemotongan pegawai wajib ditentukan.',
            'tgl_potongan.required'      => 'Tanggal eksekusi pemotongan gaji wajib diisi.',
            'metode_penyerahan.required' => 'Pilih metode penyerahan dana ke Bank Mini (Tunai atau Transfer).',
            'bukti_transfer.mimes'       => 'Bukti transfer harus berupa format gambar (JPG, PNG) atau PDF.',
            'bukti_transfer.max'         => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        $rencana = DB::table('tblRENCANA_POTONGAN')->where('rencana_potongan_id', $rencanaId)->first();
        if (!$rencana) {
            return back()->with('error', 'Data rencana potongan tidak ditemukan.');
        }

        try {
            PeriodeLockService::assertPeriodeTerbuka($request->tgl_potongan);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Simpan file bukti transfer jika diunggah
        $pathBukti = null;
        if ($request->hasFile('bukti_transfer') && $request->file('bukti_transfer')->isValid()) {
            $pathBukti = $request->file('bukti_transfer')->store('bukti_transfer', 'public');
        }

        DB::transaction(function () use ($request, $rencanaId, $pathBukti) {
            $totalRealisasi = 0;

            foreach ($request->hasil as $detailId => $statusHasil) {
                if (!in_array($statusHasil, ['berhasil', 'gagal'])) {
                    continue;
                }

                $detail = DB::table('tblDETAIL_POTONGAN')
                    ->where('detail_potongan_id', $detailId)
                    ->where('rencana_potongan_id', $rencanaId)
                    ->first();

                if (!$detail) {
                    continue;
                }

                $nominalRealisasi = ($statusHasil === 'berhasil') ? $detail->nominal_rencana : 0;
                $keteranganItem = $request->keterangan[$detailId] ?? null;

                DB::table('tblDETAIL_POTONGAN')
                    ->where('detail_potongan_id', $detailId)
                    ->update([
                        'hasil_potongan'    => $statusHasil,
                        'nominal_realisasi' => $nominalRealisasi,
                        'keterangan'        => $keteranganItem,
                    ]);

                if ($statusHasil === 'berhasil') {
                    $totalRealisasi += $nominalRealisasi;
                }
            }

            // Update status paket rencana potongan menjadi 'menunggu_verifikasi'
            $updatePayload = [
                'tgl_potongan'      => $request->tgl_potongan,
                'total_realisasi'   => $totalRealisasi,
                'metode_penyerahan' => $request->metode_penyerahan,
                'catatan_sekolah'   => $request->catatan_sekolah,
                'status_potongan'   => 'menunggu_verifikasi',
            ];

            if ($pathBukti) {
                $updatePayload['bukti_transfer'] = $pathBukti;
            }

            DB::table('tblRENCANA_POTONGAN')
                ->where('rencana_potongan_id', $rencanaId)
                ->update($updatePayload);

            AuditLogService::log(
                aksi: 'CATAT_HASIL_POTONGAN_SEKOLAH',
                entitas: 'tblRENCANA_POTONGAN',
                entitasId: $rencanaId,
                dataBaru: [
                    'tgl_potongan'      => $request->tgl_potongan,
                    'total_realisasi'   => $totalRealisasi,
                    'metode_penyerahan' => $request->metode_penyerahan,
                    'catatan_sekolah'   => $request->catatan_sekolah,
                    'bukti_transfer'    => $pathBukti,
                    'status'            => 'menunggu_verifikasi',
                ]
            );
        });

        return redirect()->route('bendahara.potongan.index')
            ->with('success', 'Hasil pemotongan gaji berhasil disimpan! Dana telah diserahkan dan kini menunggu konfirmasi penerimaan dari Bendahara Bank Mini.');
    }

    /**
     * Tampilkan lembar Berita Acara Serah Terima (BAST) untuk arsip dan cetak fisik.
     */
    public function cetakBast($rencanaId)
    {
        $rencana = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->join('tblUSER as u', 'rp.user_id', '=', 'u.user_id')
            ->where('rp.rencana_potongan_id', $rencanaId)
            ->select('rp.*', 'p.bulan', 'p.tahun', 'u.nama_user as nama_pembuat_bbm')
            ->first();

        if (!$rencana) {
            abort(404, 'Rencana potongan tidak ditemukan.');
        }

        $detailList = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('dp.rencana_potongan_id', $rencanaId)
            ->select(
                'dp.*',
                'ja.angsuran_ke',
                'ja.pokok',
                'ja.bunga',
                'n.nama_nasabah',
                'n.kelas_unit'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        return view('bendahara.potongan.bast', compact('rencana', 'detailList'));
    }
}
