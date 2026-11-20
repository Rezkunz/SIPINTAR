<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-01 (Positive) - Dashboard ringkasan keuangan
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Angka ringkasan sesuai total data transaksi.
 */
class TC_14_01Test extends DashboardDuskTestCase
{
    public function test_dashboard_ringkasan_keuangan(): void
    {
        // Data transaksi tabungan, pinjaman, dan kas
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 150000);
        $this->buatPinjamanCair(5000000, 10);

        // 3. Penjumlahan manual dari data mentah
        $totalSimpanan = (float) DB::table('tblTRANSAKSI')->sum(DB::raw('kredit - debit'));       // 425.000
        $totalPiutang  = (float) DB::table('tblPINJAMAN')->where('status_pinjaman', 'cair')->sum('nominal_pinjaman'); // 5.000.000
        // Total kas = kas tunai (saldo awal 50 jt) + kas bank (saldo awal 100 jt) + neto mutasi, sama dengan Neraca
        $netoMutasi    = fn (int $kasId) => (float) DB::table('tblMUTASI_KAS')->where('kas_id', $kasId)
            ->sum(DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END"));
        $totalKas      = 50000000 + $netoMutasi(1) + 100000000 + $netoMutasi(2);                  // 150.425.000

        $this->browse(function (Browser $browser) use ($totalSimpanan, $totalPiutang, $totalKas) {
            // 1-2. Login Manajer, buka Dashboard
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/dashboard"]')
                ->waitForLocation('/dashboard')
                ->screenshot('TC-14-01');

            $metrik = $this->nilaiMetrik($browser);
            $this->assertContains('Rp' . $this->rp($totalSimpanan), $metrik, 'Total simpanan');
            $this->assertContains('Rp' . $this->rp($totalPiutang), $metrik, 'Total pinjaman/piutang');
            $this->assertContains('Rp' . $this->rp($totalKas), $metrik, 'Total kas (tunai + bank)');
        });
    }
}
