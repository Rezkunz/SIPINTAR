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

            $periode = $this->periodeTerbuka('draft');
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

            $this->recordCashMutation($kas, $periode->periode_id, $tellerId, $transaksiId, $nominal, $jenis->nama_jenis);
            $this->updateCashBalance($kas, $nominal, $jenis->nama_jenis);

            DB::table('tblDRAFT')->where('draft_id', $draft->draft_id)->update([
                'status_verifikasi' => 'disetujui',
                'teller_id' => $tellerId,
            ]);

            $receiptId = $this->createReceipt($transaksiId, $tellerId);

            return ['transaksi_id' => $transaksiId, 'bukti_id' => $receiptId];
        });
    }

    public function rejectDraft(int $draftId, int $tellerId, string $alasan): void
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw ValidationException::withMessages([
                'alasan_penolakan' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($draftId, $tellerId, $alasan) {
            $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->lockForUpdate()->first();

            if (!$draft || $draft->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'draft' => 'Draft tidak ditemukan atau sudah diproses.',
                ]);
            }

            DB::table('tblDRAFT')->where('draft_id', $draftId)->update([
                'status_verifikasi' => 'ditolak',
                'teller_id' => $tellerId,
                'alasan_penolakan' => $alasan,
            ]);
        });
    }

    public function storeDirect(int $tellerId, string $noRek, float $nominal, string $jenis): array
    {
        return DB::transaction(function () use ($tellerId, $noRek, $nominal, $jenis) {
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

            $periode = $this->periodeTerbuka('nominal');
            $kas = $this->lockKas();
            $saldoAkhir = $this->calculateBalance($jenis, (float) $rekening->saldo, $nominal, (float) $kas->saldo);

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

            $this->recordCashMutation($kas, $periode->periode_id, $tellerId, $transaksiId, $nominal, $jenis);
            $this->updateCashBalance($kas, $nominal, $jenis);

            $receiptId = $this->createReceipt($transaksiId, $tellerId);

            return ['transaksi_id' => $transaksiId, 'bukti_id' => $receiptId];
        });
    }

    /**
     * Periode bulan berjalan harus terbuka; bila sudah ditutup Manajer, tampilkan pesan pada form.
     */
    private function periodeTerbuka(string $field): object
    {
        try {
            return PeriodeLockService::assertPeriodeTerbuka(now());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                $field => 'Transaksi ditolak: periode ' . now()->month . '/' . now()->year . ' sudah ditutup Manajer dan terkunci.',
            ]);
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

    private function calculateBalance(string $jenis, float $saldo, float $nominal, float $saldoKas): float
    {
        if ($nominal < (float) config('sipintar.draft.min_nominal', 1000)) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal transaksi berada di bawah batas minimum.',
            ]);
        }

        if ($jenis === 'penarikan') {
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

    private function recordCashMutation(object $kas, int $periodeId, int $tellerId, int $transaksiId, float $nominal, string $jenis): void
    {
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kas->kas_id,
            'periode_id' => $periodeId,
            'user_id' => $tellerId,
            'transaksi_id' => $transaksiId,
            'angsuran_id' => null,
            'pencairan_id' => null,
            'tgl_mutasi' => now()->toDateString(),
            'arah' => $jenis === 'setoran' ? 'masuk' : 'keluar',
            'nominal' => $nominal,
            'uraian' => ucfirst($jenis) . ' Teller #' . $transaksiId,
        ]);
    }

    private function updateCashBalance(object $kas, float $nominal, string $jenis): void
    {
        $query = DB::table('tblKAS')->where('kas_id', $kas->kas_id);
        if ($jenis === 'setoran') {
            $query->increment('saldo', $nominal);
        } else {
            $query->decrement('saldo', $nominal);
        }
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