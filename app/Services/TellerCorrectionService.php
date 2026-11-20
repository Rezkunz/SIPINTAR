<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TellerCorrectionService
{
    public function correct(int $transactionId, int $tellerId, string $reason, int $correctedTypeId, float $correctedNominal, ?string $expectedNoRek = null): int
    {
        return DB::transaction(function () use ($transactionId, $tellerId, $reason, $correctedTypeId, $correctedNominal, $expectedNoRek) {
            $original = DB::table('tblTRANSAKSI')
                ->where('transaksi_id', $transactionId)
                ->lockForUpdate()
                ->first();

            if (!$original) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi tidak ditemukan.']);
            }

            $alreadyCorrected = DB::table('tblAUDIT_LOG')
                ->where('aksi', 'KOREKSI_TRANSAKSI')
                ->where('entitas', 'tblTRANSAKSI')
                ->where('entitas_id', $transactionId)
                ->exists();

            $isReversal = DB::table('tblAUDIT_LOG')
                ->where('aksi', 'KOREKSI_TRANSAKSI')
                ->where('entitas', 'tblTRANSAKSI')
                ->whereRaw(
                    DB::getDriverName() === 'pgsql' ? 'CAST(data_baru AS TEXT) LIKE ?' : 'data_baru LIKE ?',
                    ['%"reversal_id":' . $transactionId . '%']
                )
                ->exists();

            $isCorrectedChild = DB::table('tblAUDIT_LOG')
                ->where('aksi', 'KOREKSI_TRANSAKSI')
                ->where('entitas', 'tblTRANSAKSI')
                ->whereRaw(
                    DB::getDriverName() === 'pgsql' ? 'CAST(data_baru AS TEXT) LIKE ?' : 'data_baru LIKE ?',
                    ['%"corrected_id":' . $transactionId . '%']
                )
                ->exists();

            if ($alreadyCorrected) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi ini sudah pernah dikoreksi.']);
            }

            if ($isReversal || $isCorrectedChild) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi hasil koreksi tidak dapat dikoreksi kembali.']);
            }

            if ($correctedNominal <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Nominal koreksi harus lebih besar dari 0.']);
            }

            $correctedType = DB::table('tblJENIS_TRANSAKSI')
                ->where('jenis_transaksi_id', $correctedTypeId)
                ->whereIn('nama_jenis', ['setoran', 'penarikan', 'jasa_tabungan'])
                ->first();

            if (!$correctedType) {
                throw ValidationException::withMessages(['jenis' => 'Jenis transaksi koreksi tidak valid.']);
            }

            $rekening = DB::table('tblREKENING')->where('rekening_id', $original->rekening_id)->lockForUpdate()->first();
            if (!$rekening || $rekening->status_rekening !== 'aktif') {
                throw ValidationException::withMessages(['transaksi' => 'Rekening tidak aktif.']);
            }
            if ($expectedNoRek !== null && $expectedNoRek !== '' && $rekening->no_rek !== $expectedNoRek) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi tidak termasuk pada rekening yang dipilih.']);
            }

            $debit = (float) $original->debit;
            $kredit = (float) $original->kredit;
            if ($debit <= 0 && $kredit <= 0) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi tidak memiliki nominal yang dapat dikoreksi.']);
            }

            $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            $originalIsCash = $original->jenis_transaksi_id !== $this->interestTransactionTypeId();
            $correctedIsCash = $correctedType->nama_jenis !== 'jasa_tabungan';
            $kas = null;

            if ($originalIsCash || $correctedIsCash) {
                $kas = DB::table('tblKAS')->where('jenis_kas', 'kas')->lockForUpdate()->first();
                if (!$kas) {
                    throw ValidationException::withMessages(['transaksi' => 'Kas tunai belum tersedia.']);
                }
            }

            $saldoSetelahReversal = (float) $rekening->saldo + $debit - $kredit;
            if ($saldoSetelahReversal < 0) {
                throw ValidationException::withMessages(['transaksi' => 'Saldo rekening tidak cukup untuk membalik transaksi lama.']);
            }

            $correctedDebit = $correctedType->nama_jenis === 'penarikan' ? $correctedNominal : 0;
            $correctedKredit = in_array($correctedType->nama_jenis, ['setoran', 'jasa_tabungan'], true) ? $correctedNominal : 0;
            $saldoAkhir = $saldoSetelahReversal + $correctedKredit - $correctedDebit;

            if ($saldoAkhir < 0) {
                throw ValidationException::withMessages(['nominal' => 'Saldo rekening setelah koreksi tidak boleh negatif.']);
            }

            if ($correctedType->nama_jenis === 'penarikan' && $saldoAkhir < 10000) {
                throw ValidationException::withMessages(['nominal' => 'Saldo setelah koreksi harus menyisakan minimal Rp 10.000.']);
            }

            $cashDelta = 0;
            if ($originalIsCash) {
                $cashDelta += $debit > 0 ? $debit : -$kredit;
            }
            if ($correctedIsCash) {
                $cashDelta += $correctedType->nama_jenis === 'setoran' ? $correctedNominal : -$correctedNominal;
            }

            if ($kas && (float) $kas->saldo + $cashDelta < 0) {
                throw ValidationException::withMessages(['transaksi' => 'Saldo kas tidak cukup untuk koreksi ini.']);
            }

            $waktuKoreksi = now();
            $labelPembatalan = $debit > 0 ? 'Koreksi - Pembatalan Penarikan' : 'Koreksi - Pembatalan Setoran';
            $labelPengganti = 'Koreksi - ' . ucfirst(str_replace('_', ' ', $correctedType->nama_jenis));

            // 1. Transaksi PEMBATALAN (reversal) dengan saldo berurutan (transaksi asli tidak diubah)
            $reversalId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $original->rekening_id,
                'jenis_transaksi_id' => $original->jenis_transaksi_id,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $tellerId,
                'debit' => $kredit,
                'kredit' => $debit,
                'saldo_akhir' => $saldoSetelahReversal,
                'tgl_transaksi' => $waktuKoreksi,
            ], 'transaksi_id');

            if ($originalIsCash) {
                $nominal = $debit > 0 ? $debit : $kredit;
                $isReversalIn = $debit > 0;
                $this->recordCashMutation($kas->kas_id, $periode->periode_id, $tellerId, $reversalId, $nominal, $isReversalIn ? 'masuk' : 'keluar', $labelPembatalan . ' #' . $transactionId, $waktuKoreksi->toDateString());
            }

            // 2. Transaksi PENGGANTI dengan saldo lanjutan dari hasil pembatalan
            $correctedId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $original->rekening_id,
                'jenis_transaksi_id' => $correctedTypeId,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $tellerId,
                'debit' => $correctedDebit,
                'kredit' => $correctedKredit,
                'saldo_akhir' => $saldoAkhir,
                'tgl_transaksi' => $waktuKoreksi,
            ], 'transaksi_id');

            DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->update(['saldo' => $saldoAkhir]);

            if ($correctedIsCash) {
                $this->recordCashMutation(
                    $kas->kas_id,
                    $periode->periode_id,
                    $tellerId,
                    $correctedId,
                    $correctedNominal,
                    $correctedType->nama_jenis === 'setoran' ? 'masuk' : 'keluar',
                    'Transaksi hasil koreksi #' . $transactionId,
                    $waktuKoreksi->toDateString()
                );
            }

            if ($kas && $cashDelta > 0) {
                DB::table('tblKAS')->where('kas_id', $kas->kas_id)->increment('saldo', $cashDelta);
            } elseif ($kas && $cashDelta < 0) {
                DB::table('tblKAS')->where('kas_id', $kas->kas_id)->decrement('saldo', abs($cashDelta));
            }

            DB::table('tblBUKTI')->insert([
                'transaksi_id' => $correctedId,
                'user_id' => $tellerId,
                'no_bukti' => 'CORR-' . $waktuKoreksi->format('Ymd') . '-' . str_pad((string) $correctedId, 8, '0', STR_PAD_LEFT),
                'tgl_cetak' => $waktuKoreksi,
            ]);

            AuditLogService::log(
                aksi: 'KOREKSI_TRANSAKSI',
                entitas: 'tblTRANSAKSI',
                entitasId: $transactionId,
                dataLama: ['debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => $original->saldo_akhir],
                dataBaru: [
                    'reversal_id' => $reversalId,
                    'corrected_id' => $correctedId,
                    'reversal_label' => $labelPembatalan,
                    'corrected_label' => $labelPengganti,
                    'jenis_transaksi_id' => $correctedTypeId,
                    'nominal' => $correctedNominal,
                    'alasan' => $reason,
                    'saldo_akhir' => $saldoAkhir,
                ],
                createdAt: $waktuKoreksi
            );

            return $correctedId;
        });
    }

    private function recordCashMutation(int $kasId, int $periodeId, int $tellerId, int $transaksiId, float $nominal, string $arah, string $uraian, ?string $tglMutasi = null): void
    {
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kasId,
            'periode_id' => $periodeId,
            'user_id' => $tellerId,
            'transaksi_id' => $transaksiId,
            'angsuran_id' => null,
            'pencairan_id' => null,
            'tgl_mutasi' => $tglMutasi ?? now()->toDateString(),
            'arah' => $arah,
            'nominal' => $nominal,
            'uraian' => $uraian,
        ]);
    }

    private function interestTransactionTypeId(): ?int
    {
        return DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', 'jasa_tabungan')->value('jenis_transaksi_id');
    }
}