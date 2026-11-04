<?php

namespace App\Services;

use App\Models\Draft;
use App\Models\JenisTransaksi;
use App\Models\Rekening;
use Illuminate\Validation\ValidationException;

class DraftService
{
    /**
     * Buat draft transaksi baru (setoran / penarikan) untuk siswa praktik.
     * Tidak mengubah saldo rekening apa pun. Status awal selalu 'pending'.
     *
     * @param int $siswaId
     * @param array $data
     * @return Draft
     * @throws ValidationException
     */
    public function createDraft(int $siswaId, array $data): Draft
    {
        // 1. Temukan dan validasi rekening aktif
        $noRek = trim($data['no_rek'] ?? '');
        $rekening = Rekening::with('nasabah')->where('no_rek', $noRek)->first();

        if (!$rekening) {
            throw ValidationException::withMessages([
                'no_rek' => 'Nomor rekening tidak ditemukan dalam sistem.',
            ]);
        }

        if ($rekening->status_rekening !== 'aktif' || ($rekening->nasabah && $rekening->nasabah->status_nasabah !== 'aktif')) {
            throw ValidationException::withMessages([
                'no_rek' => 'Rekening ini sedang tidak aktif, diblokir, atau ditutup sehingga tidak dapat menerima draft transaksi.',
            ]);
        }

        // 2. Temukan jenis transaksi ('setoran' atau 'penarikan')
        $jenisSlug = strtolower(trim($data['jenis'] ?? 'setoran'));
        $jenisModel = JenisTransaksi::where('nama_jenis', $jenisSlug)->first();

        if (!$jenisModel) {
            throw ValidationException::withMessages([
                'jenis' => 'Jenis transaksi tidak valid.',
            ]);
        }

        // 3. Validasi nominal min/max dari konfigurasi
        $min = (float) config('sipintar.draft.min_nominal', 1000);
        $max = (float) config('sipintar.draft.max_nominal', 5000000);
        $nominal = (float) ($data['nominal'] ?? 0);

        if ($nominal < $min || $nominal > $max) {
            throw ValidationException::withMessages([
                'nominal' => "Nominal harus bernilai antara Rp " . number_format($min, 0, ',', '.') . " dan Rp " . number_format($max, 0, ',', '.') . ".",
            ]);
        }

        // 4. Buat draft transaksi (saldo rekening TIDAK diubah)
        $draft = Draft::create([
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => $jenisModel->jenis_transaksi_id,
            'siswa_id'           => $siswaId,
            'teller_id'          => null,
            'nominal'            => $nominal,
            'tgl_input'          => now(),
            'status_verifikasi'  => 'pending',
        ]);

        // Catatan: Jika modul Notification diimplementasikan di kemudian hari,
        // event/dispatch notifikasi dapat dikaitkan di sini.

        return $draft->load(['rekening.nasabah', 'jenisTransaksi']);
    }
}
