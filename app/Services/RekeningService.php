<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use App\Services\PeriodeLockService;
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

                    // Buat rekening baru menunggu setoran awal Siswa Praktik
                    $rekening = Rekening::create([
                        'no_rek'          => $noRek,
                        'nasabah_id'      => $nasabah->nasabah_id,
                        'tgl_buka'        => now()->toDateString(),
                        'saldo'           => 0.00,
                        'status_rekening' => 'pending',
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

    public function aktifkanDenganSetoran(int $rekeningId, int $siswaId, float $setoranAwal): Rekening
    {
        if ($setoranAwal < 10000) {
            throw ValidationException::withMessages([
                'setoran_awal' => 'Setoran awal minimal Rp 10.000.',
            ]);
        }

        return DB::transaction(function () use ($rekeningId, $siswaId, $setoranAwal) {
            $rekening = DB::table('tblREKENING')
                ->where('rekening_id', $rekeningId)
                ->lockForUpdate()
                ->first();

            if (!$rekening || $rekening->status_rekening !== 'pending') {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening tidak ditemukan atau sudah diproses.',
                ]);
            }

            $kas = DB::table('tblKAS')->where('jenis_kas', 'kas')->lockForUpdate()->first();
            if (!$kas) {
                throw ValidationException::withMessages([
                    'setoran_awal' => 'Kas tunai belum tersedia.',
                ]);
            }

            $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            $jenisSetoran = DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', 'setoran')->first();
            if (!$jenisSetoran) {
                throw ValidationException::withMessages([
                    'rekening' => 'Jenis transaksi setoran belum tersedia.',
                ]);
            }

            DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update([
                'status_rekening' => 'aktif',
                'saldo' => $setoranAwal,
                'tgl_buka' => now()->toDateString(),
            ]);

            $transaksiId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $rekeningId,
                'jenis_transaksi_id' => $jenisSetoran->jenis_transaksi_id,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $siswaId,
                'debit' => 0,
                'kredit' => $setoranAwal,
                'saldo_akhir' => $setoranAwal,
                'tgl_transaksi' => now(),
            ]);

            DB::table('tblMUTASI_KAS')->insert([
                'kas_id' => $kas->kas_id,
                'periode_id' => $periode->periode_id,
                'user_id' => $siswaId,
                'transaksi_id' => $transaksiId,
                'angsuran_id' => null,
                'pencairan_id' => null,
                'tgl_mutasi' => now()->toDateString(),
                'arah' => 'masuk',
                'nominal' => $setoranAwal,
                'uraian' => 'Setoran awal pembukaan rekening #' . $rekening->no_rek,
            ]);

            DB::table('tblKAS')->where('kas_id', $kas->kas_id)->increment('saldo', $setoranAwal);

            DB::table('tblBUKTI')->insert([
                'transaksi_id' => $transaksiId,
                'user_id' => $siswaId,
                'no_bukti' => 'OPEN-' . now()->format('Ymd') . '-' . str_pad((string) $transaksiId, 8, '0', STR_PAD_LEFT),
                'tgl_cetak' => now(),
            ]);

            return Rekening::with('nasabah')->findOrFail($rekeningId);
        });
    }

    /**
     * Teller membuka rekening langsung di loket: nasabah baru + rekening + setoran awal dalam satu transaksi.
     */
    public function bukaRekeningLoket(array $data, int $tellerId): Rekening
    {
        try {
            return DB::transaction(function () use ($data, $tellerId) {
                $rekening = $this->bukaRekening($data);

                return $this->aktifkanDenganSetoran($rekening->rekening_id, $tellerId, (float) ($data['setoran_awal'] ?? 0));
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['setoran_awal' => $e->getMessage()]);
        }
    }

    /**
     * Ubah data identitas nasabah (nama, kelas/unit, nomor HP). Nomor HP akun login ikut disinkronkan.
     */
    public function ubahNasabah(int $nasabahId, array $data): Nasabah
    {
        return DB::transaction(function () use ($nasabahId, $data) {
            $nasabah = Nasabah::lockForUpdate()->findOrFail($nasabahId);
            $noHp = trim($data['no_hp']);

            $akun = User::where('nasabah_id', $nasabahId)->first();
            $dipakaiAkunLain = User::where('no_hp', $noHp)
                ->when($akun, fn ($q) => $q->where('user_id', '!=', $akun->user_id))
                ->exists();
            if ($dipakaiAkunLain) {
                throw ValidationException::withMessages([
                    'no_hp' => 'Nomor HP/WhatsApp ini sudah digunakan pada akun pengguna lain.',
                ]);
            }

            $lama = $nasabah->only(['nama_nasabah', 'kelas_unit', 'no_hp']);

            $nasabah->update([
                'nama_nasabah' => trim($data['nama_nasabah']),
                'kelas_unit'   => !empty($data['kelas_unit']) ? trim($data['kelas_unit']) : null,
                'no_hp'        => $noHp,
            ]);

            if ($akun && $akun->no_hp !== $noHp) {
                $akun->update(['no_hp' => $noHp]);
            }

            AuditLogService::log(
                aksi: 'UBAH_NASABAH',
                entitas: 'tblNASABAH',
                entitasId: $nasabahId,
                dataLama: $lama,
                dataBaru: $nasabah->only(['nama_nasabah', 'kelas_unit', 'no_hp'])
            );

            return $nasabah;
        });
    }

    /**
     * Tutup rekening aktif. Hanya boleh bila saldo tepat Rp 0 dan tidak ada draft yang menunggu verifikasi.
     * Riwayat transaksi tidak dihapus.
     */
    public function tutupRekening(int $rekeningId): void
    {
        DB::transaction(function () use ($rekeningId) {
            $rekening = DB::table('tblREKENING')->where('rekening_id', $rekeningId)->lockForUpdate()->first();

            if (!$rekening || $rekening->status_rekening !== 'aktif') {
                throw ValidationException::withMessages([
                    'rekening' => 'Hanya rekening berstatus aktif yang dapat ditutup.',
                ]);
            }

            if ((float) $rekening->saldo != 0.0) {
                throw ValidationException::withMessages([
                    'rekening' => self::pesanSaldoHarusNol((float) $rekening->saldo),
                ]);
            }

            if (DB::table('tblDRAFT')->where('rekening_id', $rekeningId)->where('status_verifikasi', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening tidak dapat ditutup: masih ada draft transaksi yang menunggu verifikasi.',
                ]);
            }

            DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update(['status_rekening' => 'tutup']);

            AuditLogService::log(
                aksi: 'TUTUP_REKENING',
                entitas: 'tblREKENING',
                entitasId: $rekeningId,
                dataLama: ['status_rekening' => 'aktif', 'saldo' => (float) $rekening->saldo],
                dataBaru: ['status_rekening' => 'tutup']
            );
        });
    }

    public static function pesanSaldoHarusNol(float $saldo): string
    {
        return 'Rekening tidak dapat ditutup: saldo harus Rp 0 (saldo saat ini Rp ' . number_format($saldo, 0, ',', '.') . ').';
    }
}
