<?php

namespace App\Services;

use App\Models\Draft;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TellerTransactionService
{
    private const MINIMUM_REMAINING_BALANCE = 10000;

    public function approveDraft(int $draftId, int $tellerId): array
    {
        return DB::transaction(function () use ($draftId, $tellerId) {
            $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->lockForUpdate()->first();

            if (!$draft || $draft->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'draft' => 'Draft tidak ditemukan atau sudah diproses.',
                ]);
            }

            if (DB::table('tblTRANSAKSI')->where('draft_id', $draft->draft_id)->exists()) {
                throw ValidationException::withMessages([
                    'draft' => 'Draft ini sudah pernah dicatat transaksinya.',
                ]);
            }

            $rekening = DB::table('tblREKENING')
                ->where('rekening_id', $draft->rekening_id)
                ->lockForUpdate()
                ->first();

            if (!$rekening || $rekening->status_rekening !== 'aktif') {
                throw ValidationException::withMessages([
                    'draft' => 'Rekening harus berstatus aktif untuk diproses.',
                ]);
            }

            $jenis = DB::table('tblJENIS_TRANSAKSI')
                ->where('jenis_transaksi_id', $draft->jenis_transaksi_id)
                ->first();

            if (!$jenis || !in_array($jenis->nama_jenis, ['setoran', 'penarikan'], true)) {
                throw ValidationException::withMessages([
                    'draft' => 'Jenis transaksi draft tidak valid.',
                ]);
            }

            $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            $nominal = (float) $draft->nominal;
            $kas = $this->lockKas();
            $saldoAkhir = $this->calculateBalance($jenis->nama_jenis, (float) $rekening->saldo, $nominal, (float) $kas->saldo);

            $transaksiId = $this->recordTransaction(
                rekeningId: $rekening->rekening_id,
                jenisTransaksiId: $jenis->jenis_transaksi_id,
                periodeId: $periode->periode_id,
                draftId: $draft->draft_id,
                tellerId: $tellerId,
                nominal: $nominal,
                saldoAkhir: $saldoAkhir,
                jenis: $jenis->nama_jenis
            );

            DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->update([
                'saldo' => $saldoAkhir,
            ]);

            $this->catatMutasiKasTunai(
                periodeId: $periode->periode_id,
                userId: $tellerId,
                transaksiId: $transaksiId,
                nominal: $nominal,
                arah: $jenis->nama_jenis === 'setoran' ? 'masuk' : 'keluar',
                uraian: ucfirst($jenis->nama_jenis) . ' Teller #' . $transaksiId
            );

            DB::table('tblDRAFT')->where('draft_id', $draft->draft_id)->update([
                'status_verifikasi' => 'disetujui',
                'teller_id' => $tellerId,
            ]);

            $receiptId = $this->createReceipt($transaksiId, $tellerId);

            return ['transaksi_id' => $transaksiId, 'bukti_id' => $receiptId];
        });
    }

    public function rejectDraft(int $draftId, int $tellerId): void
    {
        DB::transaction(function () use ($draftId, $tellerId) {
            $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->lockForUpdate()->first();

            if (!$draft || $draft->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'draft' => 'Draft tidak ditemukan atau sudah diproses.',
                ]);
            }

            if (DB::table('tblTRANSAKSI')->where('draft_id', $draft->draft_id)->exists()) {
                throw ValidationException::withMessages([
                    'draft' => 'Draft ini sudah pernah dicatat transaksinya.',
                ]);
            }

            DB::table('tblDRAFT')->where('draft_id', $draftId)->update([
                'status_verifikasi' => 'ditolak',
                'teller_id' => $tellerId,
            ]);
        });
    }

    public function storeDirect(int $tellerId, string $noRek, float $nominal, string $jenis, bool $enforceMinSetoran = true): array
    {
        return DB::transaction(function () use ($tellerId, $noRek, $nominal, $jenis, $enforceMinSetoran) {
            $rekening = DB::table('tblREKENING')
                ->where('no_rek', $noRek)
                ->lockForUpdate()
                ->first();

            if (!$rekening || $rekening->status_rekening !== 'aktif') {
                throw ValidationException::withMessages([
                    'no_rek' => 'Rekening tidak ditemukan atau belum berstatus aktif.',
                ]);
            }

            $jenisModel = DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', $jenis)->first();
            if (!$jenisModel) {
                throw ValidationException::withMessages([
                    'jenis' => 'Jenis transaksi tidak tersedia.',
                ]);
            }

            $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            $kas = $this->lockKas();
            $saldoAkhir = $this->calculateBalance($jenis, (float) $rekening->saldo, $nominal, (float) $kas->saldo, $enforceMinSetoran);

            $transaksiId = $this->recordTransaction(
                rekeningId: $rekening->rekening_id,
                jenisTransaksiId: $jenisModel->jenis_transaksi_id,
                periodeId: $periode->periode_id,
                draftId: null,
                tellerId: $tellerId,
                nominal: $nominal,
                saldoAkhir: $saldoAkhir,
                jenis: $jenis
            );

            DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->update([
                'saldo' => $saldoAkhir,
            ]);

            $this->catatMutasiKasTunai(
                periodeId: $periode->periode_id,
                userId: $tellerId,
                transaksiId: $transaksiId,
                nominal: $nominal,
                arah: $jenis === 'setoran' ? 'masuk' : 'keluar',
                uraian: ucfirst($jenis) . ' Teller #' . $transaksiId
            );

            if ($jenis === 'setoran' && !$enforceMinSetoran) {
                AuditLogService::log(
                    aksi: 'BYPASS_MINIMAL_SETORAN',
                    entitas: 'tblTRANSAKSI',
                    entitasId: $transaksiId,
                    dataBaru: [
                        'no_rek' => $noRek,
                        'nominal' => $nominal,
                        'min_default' => (float) config('sipintar.setoran.min_berikutnya', 10000),
                    ]
                );
            }

            $receiptId = $this->createReceipt($transaksiId, $tellerId);

            return ['transaksi_id' => $transaksiId, 'bukti_id' => $receiptId];
        });
    }

    /**
     * Catat mutasi Kas Tunai dan perbarui saldo tblKAS secara atomik & idempoten.
     */
    public function catatMutasiKasTunai(
        int $periodeId,
        int $userId,
        int $transaksiId,
        float $nominal,
        string $arah,
        string $uraian
    ): void {
        if ($nominal <= 0) {
            return;
        }

        // Cegah pencatatan ganda untuk transaksi yang sama (idempotensi)
        if (DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksiId)->exists()) {
            return;
        }

        $kas = $this->lockKas();

        if ($arah === 'keluar' && (float) $kas->saldo < $nominal) {
            throw ValidationException::withMessages([
                'nominal' => 'Saldo kas tunai tidak mencukupi.',
            ]);
        }

        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kas->kas_id,
            'periode_id' => $periodeId,
            'user_id' => $userId,
            'transaksi_id' => $transaksiId,
            'angsuran_id' => null,
            'pencairan_id' => null,
            'tgl_mutasi' => now()->toDateString(),
            'arah' => $arah,
            'nominal' => $nominal,
            'uraian' => $uraian,
        ]);

        $query = DB::table('tblKAS')->where('kas_id', $kas->kas_id);
        if ($arah === 'masuk') {
            $query->increment('saldo', $nominal);
        } else {
            $query->decrement('saldo', $nominal);
        }
    }

    private function lockKas(): object
    {
        $kas = DB::table('tblKAS')->where('jenis_kas', 'kas')->lockForUpdate()->first();

        if (!$kas) {
            throw ValidationException::withMessages([
                'nominal' => 'Kas tunai Teller belum tersedia.',
            ]);
        }

        return $kas;
    }

    private function calculateBalance(string $jenis, float $saldo, float $nominal, float $saldoKas, bool $enforceMinSetoran = true): float
    {
        if ($jenis === 'setoran') {
            $minSetoran = $enforceMinSetoran
                ? (float) config('sipintar.setoran.min_berikutnya', 10000)
                : (float) config('sipintar.setoran.min_tanpa_batas', 1);

            if ($nominal < $minSetoran) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal setoran minimal Rp ' . number_format($minSetoran, 0, ',', '.') . '.',
                ]);
            }

            return $saldo + $nominal;
        }

        if ($jenis === 'penarikan') {
            $minPenarikan = (float) config('sipintar.penarikan.min_nominal', 10000);
            if ($nominal < $minPenarikan) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal penarikan minimal Rp ' . number_format($minPenarikan, 0, ',', '.') . '.',
                ]);
            }

            if ($saldo - $nominal < self::MINIMUM_REMAINING_BALANCE) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo rekening harus menyisakan minimal Rp 10.000.',
                ]);
            }

            if ($saldoKas < $nominal) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo kas tunai tidak mencukupi.',
                ]);
            }

            return $saldo - $nominal;
        }

        if ($nominal < (float) config('sipintar.draft.min_nominal', 10000)) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal transaksi berada di bawah batas minimum.',
            ]);
        }

        return $saldo + $nominal;
    }

    private function recordTransaction(
        int $rekeningId,
        int $jenisTransaksiId,
        int $periodeId,
        ?int $draftId,
        int $tellerId,
        float $nominal,
        float $saldoAkhir,
        string $jenis
    ): int {
        return DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekeningId,
            'jenis_transaksi_id' => $jenisTransaksiId,
            'periode_id' => $periodeId,
            'draft_id' => $draftId,
            'user_id' => $tellerId,
            'debit' => $jenis === 'penarikan' ? $nominal : 0,
            'kredit' => $jenis === 'setoran' ? $nominal : 0,
            'saldo_akhir' => $saldoAkhir,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');
    }

    private function createReceipt(int $transaksiId, int $tellerId): int
    {
        return DB::table('tblBUKTI')->insertGetId([
            'transaksi_id' => $transaksiId,
            'user_id' => $tellerId,
            'no_bukti' => 'TLL-' . now()->format('Ymd') . '-' . str_pad((string) $transaksiId, 8, '0', STR_PAD_LEFT),
            'tgl_cetak' => now(),
        ],  'bukti_id'); 
    }
}