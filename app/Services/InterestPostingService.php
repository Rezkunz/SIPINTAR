<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InterestPostingService
{
    /**
     * Dapatkan tarif bunga per bulan berdasarkan jenis nasabah.
     */
    public function resolveTarif(string $jenisNasabah): float
    {
        if ($jenisNasabah === 'pegawai') {
            return (float) config('sipintar.bunga_tabungan.pegawai', 0.002);
        }

        return (float) config('sipintar.bunga_tabungan.siswa', 0.001);
    }

    /**
     * Ambil rekening aktif dengan saldo > 0 dan nasabah aktif.
     */
    public function getEligibleAccounts(): Collection
    {
        return DB::table('tblREKENING as r')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('r.status_rekening', 'aktif')
            ->where('n.status_nasabah', 'aktif')
            ->where('r.saldo', '>', 0)
            ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nasabah_id', 'n.nama_nasabah', 'n.jenis_nasabah')
            ->orderBy('r.rekening_id')
            ->get();
    }

    /**
     * Periksa apakah bunga pada rentang tanggal tertentu sudah pernah dibukukan.
     */
    public function isInterestAlreadyPosted(int $rekeningId, int $jenisBungaId, Carbon $startDate, Carbon $endDate): bool
    {
        return DB::table('tblTRANSAKSI')
            ->where('rekening_id', $rekeningId)
            ->where('jenis_transaksi_id', $jenisBungaId)
            ->whereBetween('tgl_transaksi', [$startDate, $endDate])
            ->exists();
    }

    /**
     * Hitung nominal bunga dari saldo dan tarif, dibulatkan ke rupiah terdekat.
     */
    public function calculateInterest(float $saldo, float $tarif): float
    {
        return (float) round($saldo * $tarif);
    }

    /**
     * Tentukan user_id sistem penanggung jawab pencatatan transaksi otomatis.
     */
    public function resolveSystemUserId(int $periodeId): int
    {
        $configuredId = config('sipintar.system_user_id');
        if ($configuredId && DB::table('tblUSER')->where('user_id', $configuredId)->exists()) {
            return (int) $configuredId;
        }

        $periodeUser = DB::table('tblPERIODE')->where('periode_id', $periodeId)->value('user_id');
        if ($periodeUser && DB::table('tblUSER')->where('user_id', $periodeUser)->exists()) {
            return (int) $periodeUser;
        }

        $manager = DB::table('tblUSER as u')
            ->join('tblROLE as r', 'u.role_id', '=', 'r.role_id')
            ->whereRaw('LOWER(r.nama_role) = ?', ['manager'])
            ->where('u.status_akun', 'aktif')
            ->orderBy('u.user_id')
            ->select('u.user_id')
            ->first();

        if ($manager) {
            return (int) $manager->user_id;
        }

        $activeUser = DB::table('tblUSER')
            ->where('status_akun', 'aktif')
            ->orderBy('user_id')
            ->value('user_id');

        if ($activeUser) {
            return (int) $activeUser;
        }

        throw new RuntimeException('User penanggung jawab sistem untuk pencatatan transaksi bunga tidak ditemukan.');
    }

    /**
     * Proses bunga satu rekening secara atomik dengan lockForUpdate.
     *
     * @return array{status: string, nominal: float, saldo_akhir?: float, pesan?: string}
     */
    public function processAccountInterest(
        int $rekeningId,
        int $jenisBungaId,
        int $periodeId,
        int $systemUserId,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $transactionDate,
        string $periodeLabel,
        bool $isDryRun = false
    ): array {
        return DB::transaction(function () use (
            $rekeningId,
            $jenisBungaId,
            $periodeId,
            $systemUserId,
            $startDate,
            $endDate,
            $transactionDate,
            $periodeLabel,
            $isDryRun
        ) {
            $rekening = DB::table('tblREKENING as r')
                ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
                ->where('r.rekening_id', $rekeningId)
                ->where('r.status_rekening', 'aktif')
                ->where('n.status_nasabah', 'aktif')
                ->lockForUpdate()
                ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nama_nasabah', 'n.jenis_nasabah')
                ->first();

            if (!$rekening || (float) $rekening->saldo <= 0) {
                return [
                    'status' => 'skipped_inactive_or_empty',
                    'nominal' => 0.0,
                    'pesan' => 'Rekening tidak aktif atau saldo nol.',
                ];
            }

            $alreadyPosted = $this->isInterestAlreadyPosted($rekening->rekening_id, $jenisBungaId, $startDate, $endDate);
            if ($alreadyPosted) {
                return [
                    'status' => 'skipped_already_posted',
                    'nominal' => 0.0,
                    'pesan' => "Bunga rekening {$rekening->no_rek} sudah tercatat pada periode {$periodeLabel}.",
                ];
            }

            $tarif = $this->resolveTarif($rekening->jenis_nasabah);
            $nominalBunga = $this->calculateInterest((float) $rekening->saldo, $tarif);

            if ($nominalBunga <= 0) {
                return [
                    'status' => 'skipped_zero_interest',
                    'nominal' => 0.0,
                    'pesan' => "Bunga rekening {$rekening->no_rek} dibulatkan ke Rp 0.",
                ];
            }

            $saldoAwal = (float) $rekening->saldo;
            $saldoAkhir = $saldoAwal + $nominalBunga;

            if ($isDryRun) {
                return [
                    'status' => 'simulated',
                    'nominal' => $nominalBunga,
                    'saldo_akhir' => $saldoAkhir,
                    'pesan' => "Simulasi: Bunga Rp {$nominalBunga} untuk rekening {$rekening->no_rek}.",
                ];
            }

            $transaksiId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $rekening->rekening_id,
                'jenis_transaksi_id' => $jenisBungaId,
                'periode_id' => $periodeId,
                'draft_id' => null,
                'user_id' => $systemUserId,
                'debit' => 0,
                'kredit' => $nominalBunga,
                'saldo_akhir' => $saldoAkhir,
                'tgl_transaksi' => $transactionDate,
            ], 'transaksi_id');

            DB::table('tblREKENING')
                ->where('rekening_id', $rekening->rekening_id)
                ->update(['saldo' => $saldoAkhir]);

            AuditLogService::log(
                aksi: 'HITUNG_BUNGA_TABUNGAN',
                entitas: 'tblTRANSAKSI',
                entitasId: $transaksiId,
                dataBaru: [
                    'rekening_id' => $rekening->rekening_id,
                    'no_rek' => $rekening->no_rek,
                    'jenis_nasabah' => $rekening->jenis_nasabah,
                    'tarif' => $tarif,
                    'nominal_bunga' => $nominalBunga,
                    'saldo_sebelum' => $saldoAwal,
                    'saldo_akhir' => $saldoAkhir,
                    'periode' => $periodeLabel,
                ]
            );

            Log::info("ProcessBungaTabungan: Rekening {$rekening->no_rek}, periode {$periodeLabel}, bunga Rp {$nominalBunga}");

            return [
                'status' => 'success',
                'transaksi_id' => $transaksiId,
                'nominal' => $nominalBunga,
                'saldo_akhir' => $saldoAkhir,
                'pesan' => "Bunga Rp {$nominalBunga} berhasil dikreditkan.",
            ];
        });
    }
}
