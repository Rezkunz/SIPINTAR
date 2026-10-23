<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekeningService
{
    /**
     * Buka rekening nasabah baru secara transaksional dengan nomor rekening unik
     * berformat [Prefix]-[Tahun]-[Urutan 5 Digit].
     *
     * @param array $data
     * @return Rekening
     * @throws ValidationException
     */
    public function bukaRekening(array $data): Rekening
    {
        // 1. Validasi pencegahan duplikat nasabah (Nomor HP & Nomor Induk)
        $noHp = trim($data['no_hp'] ?? '');
        if (Nasabah::where('no_hp', $noHp)->exists()) {
            throw ValidationException::withMessages([
                'no_hp' => 'Nomor HP/WhatsApp sudah terdaftar sebagai nasabah.',
            ]);
        }

        $noInduk = trim($data['no_induk'] ?? '');
        if ($noInduk !== '' && User::where('no_induk', $noInduk)->exists()) {
            throw ValidationException::withMessages([
                'no_induk' => 'Nomor Induk (NISN/NIP) sudah terdaftar dalam sistem.',
            ]);
        }

        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return DB::transaction(function () use ($data, $noHp) {
                    // Buat data nasabah baru (langsung aktif, tanpa akun login)
                    $nasabah = Nasabah::create([
                        'nama_nasabah'   => trim($data['nama_nasabah']),
                        'jenis_nasabah'  => ($data['jenis_nasabah'] ?? 'siswa') === 'pegawai' ? 'pegawai' : 'non_pegawai',
                        'kelas_unit'     => !empty($data['kelas_unit']) ? trim($data['kelas_unit']) : null,
                        'no_hp'          => $noHp,
                        'status_nasabah' => 'aktif',
                    ]);

                    // Generate nomor rekening aman dari race condition
                    $year = date('Y');
                    $prefix = config('sipintar.rekening.prefix', '1001') . '-' . $year . '-';

                    $latest = Rekening::where('no_rek', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->orderByDesc('no_rek')
                        ->first();

                    $nextNumber = 1;
                    if ($latest && preg_match('/-(\d{5})$/', $latest->no_rek, $matches)) {
                        $nextNumber = ((int) $matches[1]) + 1;
                    }

                    $noRek = $prefix . sprintf('%05d', $nextNumber);

                    // Buat rekening baru (saldo awal 0, aktif)
                    $rekening = Rekening::create([
                        'no_rek'          => $noRek,
                        'nasabah_id'      => $nasabah->nasabah_id,
                        'tgl_buka'        => now()->toDateString(),
                        'saldo'           => 0.00,
                        'status_rekening' => 'aktif',
                    ]);

                    return $rekening->load('nasabah');
                });
            } catch (QueryException $e) {
                // Jika terjadi collision nomor rekening (unique constraint), ulangi percobaan
                if ($attempt >= $maxAttempts || $e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal menerbitkan nomor rekening unik setelah beberapa percobaan.');
    }
}
