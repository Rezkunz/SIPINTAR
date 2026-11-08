<?php

namespace App\Console\Commands;

use App\Services\InterestPostingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBungaTabungan extends Command
{
    /**
     * Nama dan signature command.
     */
    protected $signature = 'sipintar:process-bunga-tabungan
                            {--dry-run : Menjalankan simulasi perhitungan tanpa menulis ke database}
                            {--periode= : Periode transaksi bunga format YYYY-MM}';

    /**
     * Deskripsi command.
     */
    protected $description = 'Memproses kredit bunga tabungan bulanan secara otomatis untuk seluruh rekening aktif';

    /**
     * Eksekusi command.
     */
    public function handle(InterestPostingService $service): int
    {
        $periodeOpt = $this->option('periode');
        $isDryRun = (bool) $this->option('dry-run');

        if ($periodeOpt) {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodeOpt)) {
                $this->error("Format periode '{$periodeOpt}' tidak valid. Gunakan format YYYY-MM (contoh: 2026-11).");
                return Command::FAILURE;
            }
            $targetDate = Carbon::createFromFormat('Y-m', $periodeOpt)->endOfMonth();
        } else {
            $targetDate = now();
        }

        $tahun = (int) $targetDate->year;
        $bulan = (int) $targetDate->month;
        $periodeLabel = sprintf('%04d-%02d', $tahun, $bulan);
        $startDate = $targetDate->copy()->startOfMonth();
        $endDate = $targetDate->copy()->endOfMonth();
        $transactionDate = $periodeOpt ? $targetDate->copy()->setTime(23, 0, 0) : now();

        $this->info("Memulai pemrosesan bunga tabungan periode {$periodeLabel}" . ($isDryRun ? ' [DRY-RUN]' : '') . '...');

        // 1. Cek ketersediaan master jenis transaksi 'jasa_tabungan'
        $jenisBunga = DB::table('tblJENIS_TRANSAKSI')
            ->where('nama_jenis', 'jasa_tabungan')
            ->first();

        if (!$jenisBunga) {
            $this->error("Master jenis transaksi 'jasa_tabungan' belum tersedia di tblJENIS_TRANSAKSI.");
            return Command::FAILURE;
        }

        // 2. Cek keberadaan dan status periode di tblPERIODE
        $periode = DB::table('tblPERIODE')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        if (!$periode) {
            $this->error("Periode {$bulan}/{$tahun} tidak ditemukan di tblPERIODE.");
            return Command::FAILURE;
        }

        if ($periode->status_periode !== 'buka') {
            $this->error("Periode {$bulan}/{$tahun} berstatus '{$periode->status_periode}' (tidak terbuka untuk transaksi).");
            return Command::FAILURE;
        }

        // 3. Tentukan user penanggung jawab transaksi sistem
        try {
            $systemUserId = $service->resolveSystemUserId($periode->periode_id);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // 4. Ambil rekening aktif bersaldo > 0 dengan nasabah aktif
        $accounts = $service->getEligibleAccounts();
        $totalAccounts = $accounts->count();

        $this->line("Ditemukan {$totalAccounts} rekening aktif dengan saldo > 0.");

        $processedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $totalNominal = 0.0;

        foreach ($accounts as $account) {
            try {
                $result = $service->processAccountInterest(
                    rekeningId: $account->rekening_id,
                    jenisBungaId: $jenisBunga->jenis_transaksi_id,
                    periodeId: $periode->periode_id,
                    systemUserId: $systemUserId,
                    startDate: $startDate,
                    endDate: $endDate,
                    transactionDate: $transactionDate,
                    periodeLabel: $periodeLabel,
                    isDryRun: $isDryRun
                );

                if (in_array($result['status'], ['success', 'simulated'], true)) {
                    $processedCount++;
                    $totalNominal += $result['nominal'];
                    $this->line(" - [PROSES] Rek {$account->no_rek} ({$account->nama_nasabah}): Bunga Rp " . number_format($result['nominal'], 0, ',', '.'));
                } else {
                    $skippedCount++;
                    $this->line(" - [LEWATI] Rek {$account->no_rek}: {$result['pesan']}");
                }
            } catch (Throwable $e) {
                $failedCount++;
                Log::error("ProcessBungaTabungan error pada rekening {$account->no_rek}: " . $e->getMessage(), [
                    'exception' => $e,
                    'rekening_id' => $account->rekening_id,
                ]);
                $this->error(" - [GAGAL]  Rek {$account->no_rek}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Periode', $periodeLabel],
                ['Mode', $isDryRun ? 'DRY-RUN (Simulasi)' : 'LIVE (Tulis Database)'],
                ['Total Diperiksa', $totalAccounts],
                ['Berhasil Diproses', $processedCount],
                ['Dilewati', $skippedCount],
                ['Gagal', $failedCount],
                ['Total Bunga', 'Rp ' . number_format($totalNominal, 0, ',', '.')],
            ]
        );

        $this->info("Pemrosesan bunga tabungan selesai.");

        return Command::SUCCESS;
    }
}
