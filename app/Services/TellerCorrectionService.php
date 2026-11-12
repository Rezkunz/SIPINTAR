<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TellerCorrectionService
{
    public function correct(int $transactionId, int $tellerId, string $reason, int $correctedTypeId, float $correctedNominal): int
    {
        return DB::transaction(function () use ($transactionId, $tellerId, $reason, $correctedTypeId, $correctedNominal) {
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
                ->where('data_baru', 'like', '%"reversal_id":' . $transactionId . '%')
                ->exists();

            if ($alreadyCorrected || $isReversal) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi ini sudah pernah dikoreksi.']);
            }

            // Transaksi pada periode yang sudah ditutup Manajer tidak boleh dikoreksi
            $periodeAsli = DB::table('tblPERIODE')->where('periode_id', $original->periode_id)->first();
            if ($periodeAsli && $periodeAsli->status_periode === 'tutup') {
                throw ValidationException::withMessages([
                    'transaksi' => self::pesanPeriodeTerkunci((int) $periodeAsli->bulan, (int) $periodeAsli->tahun),
                ]);
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

            $debit = (float) $original->debit;
            $kredit = (float) $original->kredit;
            if ($debit <= 0 && $kredit <= 0) {
                throw ValidationException::withMessages(['transaksi' => 'Transaksi tidak memiliki nominal yang dapat dikoreksi.']);
            }

            try {
                $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'transaksi' => self::pesanPeriodeTerkunci(now()->month, now()->year),
                ]);
            }
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

            $reversalId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $original->rekening_id,
                'jenis_transaksi_id' => $original->jenis_transaksi_id,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $tellerId,
                'debit' => $kredit,
                'kredit' => $debit,
                'saldo_akhir' => $saldoAkhir,
                'tgl_transaksi' => now(),
            ]);

            DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->update(['saldo' => $saldoAkhir]);

            if ($originalIsCash) {
                $nominal = $debit > 0 ? $debit : $kredit;
                $isReversalIn = $debit > 0;
                $this->recordCashMutation($kas->kas_id, $periode->periode_id, $tellerId, $reversalId, $nominal, $isReversalIn ? 'masuk' : 'keluar', 'Pembalik transaksi #' . $transactionId);
            }

            $correctedId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $original->rekening_id,
                'jenis_transaksi_id' => $correctedTypeId,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $tellerId,
                'debit' => $correctedDebit,
                'kredit' => $correctedKredit,
                'saldo_akhir' => $saldoAkhir,
                'tgl_transaksi' => now(),
            ]);

            if ($correctedIsCash) {
                $this->recordCashMutation(
                    $kas->kas_id,
                    $periode->periode_id,
                    $tellerId,
                    $correctedId,
                    $correctedNominal,
                    $correctedType->nama_jenis === 'setoran' ? 'masuk' : 'keluar',
                    'Transaksi hasil koreksi #' . $transactionId
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
                'no_bukti' => 'CORR-' . now()->format('Ymd') . '-' . str_pad((string) $correctedId, 8, '0', STR_PAD_LEFT),
                'tgl_cetak' => now(),
            ]);

            AuditLogService::log(
                aksi: 'KOREKSI_TRANSAKSI',
                entitas: 'tblTRANSAKSI',
                entitasId: $transactionId,
                dataLama: ['debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => $original->saldo_akhir],
                dataBaru: [
                    'reversal_id' => $reversalId,
                    'corrected_id' => $correctedId,
                    'jenis_transaksi_id' => $correctedTypeId,
                    'nominal' => $correctedNominal,
                    'alasan' => $reason,
                    'saldo_akhir' => $saldoAkhir,
                ]
            );

            return $correctedId;
        });
    }

    public static function pesanPeriodeTerkunci(int $bulan, int $tahun): string
    {
        return "Koreksi ditolak: periode {$bulan}/{$tahun} sudah ditutup Manajer dan terkunci.";
    }

    private function recordCashMutation(int $kasId, int $periodeId, int $tellerId, int $transaksiId, float $nominal, string $arah, string $uraian): void
    {
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kasId,
            'periode_id' => $periodeId,
            'user_id' => $tellerId,
            'transaksi_id' => $transaksiId,
            'angsuran_id' => null,
            'pencairan_id' => null,
            'tgl_mutasi' => now()->toDateString(),
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