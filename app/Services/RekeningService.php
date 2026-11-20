<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use App\Services\PeriodeLockService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        if (Nasabah::where('no_hp', $noHp)->exists() || User::where('no_hp', $noHp)->exists()) {
            throw ValidationException::withMessages([
                'no_hp' => 'Nomor HP/WhatsApp sudah terdaftar sebagai nasabah/pengguna.',
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

                    // Buat akun login nasabah (role_id = 6) sesuai input form
                    $this->buatAkunUserNasabah(
                        $nasabah,
                        $data['no_induk'] ?? null,
                        $data['email'] ?? null,
                        $data['password'] ?? null
                    );

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
        $minSetoran = (float) config('sipintar.setoran.min_awal', 20000);
        if ($setoranAwal < $minSetoran) {
            throw ValidationException::withMessages([
                'setoran_awal' => 'Setoran awal minimal Rp ' . number_format($minSetoran, 0, ',', '.') . '.',
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
            ], 'transaksi_id');

            app(TellerTransactionService::class)->catatMutasiKasTunai(
                periodeId: $periode->periode_id,
                userId: $siswaId,
                transaksiId: $transaksiId,
                nominal: $setoranAwal,
                arah: 'masuk',
                uraian: 'Setoran awal pembukaan rekening #' . $rekening->no_rek
            );

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
     * Tutup rekening nasabah oleh Teller secara atomik.
     */
    public function tutupRekening(int $rekeningId, int $tellerId, ?string $alasan = null): array
    {
        return DB::transaction(function () use ($rekeningId, $tellerId, $alasan) {
            $rekening = DB::table('tblREKENING')
                ->where('rekening_id', $rekeningId)
                ->lockForUpdate()
                ->first();

            if (!$rekening) {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening tidak ditemukan.',
                ]);
            }

            if ($rekening->status_rekening === 'tutup') {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening sudah berstatus tutup.',
                ]);
            }

            $hasPendingDraft = DB::table('tblDRAFT')
                ->where('rekening_id', $rekeningId)
                ->where('status_verifikasi', 'pending')
                ->exists();

            if ($hasPendingDraft) {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening masih memiliki draft transaksi pending yang belum diverifikasi.',
                ]);
            }

            $sisaSaldo = (float) $rekening->saldo;
            $transaksiId = null;

            if ($sisaSaldo > 0) {
                $periode = PeriodeLockService::assertPeriodeTerbuka(now());
                $jenisPenarikan = DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', 'penarikan')->first();
                if (!$jenisPenarikan) {
                    throw ValidationException::withMessages([
                        'rekening' => 'Jenis transaksi penarikan belum tersedia.',
                    ]);
                }

                $transaksiId = DB::table('tblTRANSAKSI')->insertGetId([
                    'rekening_id'        => $rekeningId,
                    'jenis_transaksi_id' => $jenisPenarikan->jenis_transaksi_id,
                    'periode_id'         => $periode->periode_id,
                    'draft_id'           => null,
                    'user_id'            => $tellerId,
                    'debit'              => $sisaSaldo,
                    'kredit'             => 0,
                    'saldo_akhir'        => 0,
                    'tgl_transaksi'      => now(),
                ], 'transaksi_id');

                app(TellerTransactionService::class)->catatMutasiKasTunai(
                    periodeId: $periode->periode_id,
                    userId: $tellerId,
                    transaksiId: $transaksiId,
                    nominal: $sisaSaldo,
                    arah: 'keluar',
                    uraian: 'Penarikan penutupan rekening #' . $rekening->no_rek
                );

                DB::table('tblBUKTI')->insert([
                    'transaksi_id' => $transaksiId,
                    'user_id'      => $tellerId,
                    'no_bukti'     => 'CLOSE-' . now()->format('Ymd') . '-' . str_pad((string) $transaksiId, 8, '0', STR_PAD_LEFT),
                    'tgl_cetak'    => now(),
                ]);
            }

            DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update([
                'status_rekening' => 'tutup',
                'saldo'           => 0,
            ]);

            AuditLogService::log(
                aksi: 'TUTUP_REKENING',
                entitas: 'tblREKENING',
                entitasId: $rekeningId,
                dataLama: [
                    'status_rekening' => $rekening->status_rekening,
                    'saldo' => $sisaSaldo,
                ],
                dataBaru: [
                    'status_rekening' => 'tutup',
                    'saldo' => 0,
                    'transaksi_penutupan_id' => $transaksiId,
                    'alasan' => $alasan,
                ]
            );

            return [
                'rekening' => Rekening::with('nasabah')->findOrFail($rekeningId),
                'transaksi_id' => $transaksiId,
                'saldo_dicairkan' => $sisaSaldo,
            ];
        });
    }

    /**
     * Buat akun login portal nasabah (role_id = 6).
     */
    private function buatAkunUserNasabah(Nasabah $nasabah, ?string $noInduk = null, ?string $email = null, ?string $plainPassword = null): User
    {
        $noIndukClean = $noInduk !== null && trim($noInduk) !== '' ? trim($noInduk) : null;
        $emailClean = $email !== null && trim($email) !== '' ? trim($email) : null;
        $password = ($plainPassword !== null && $plainPassword !== '')
            ? $plainPassword
            : (string) config('sipintar.nasabah.default_password', 'sipintar123');

        return User::create([
            'role_id'     => 6,
            'nasabah_id'  => $nasabah->nasabah_id,
            'no_hp'       => $nasabah->no_hp,
            'no_induk'    => $noIndukClean,
            'email'       => $emailClean,
            'nama_user'   => $nasabah->nama_nasabah,
            'password'    => Hash::make($password),
            'status_akun' => 'aktif',
        ]);
    }
}
