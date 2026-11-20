<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D03 (Positive) - Total Kas Bank Mini Dashboard sama dengan Kas Tunai (Cash) dan Kas Bank Bendahara
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Temuan   : 2 - Angka dashboard perlu sama dengan data kas.
 * Expected : Kas Tunai & Kas Bank sama dengan menu Kas Tunai (Cash) / Kas Bank Bendahara; Total Kas = Kas Tunai + Kas Bank.
 */
class TC_I2_D03Test extends Iterasi2DashboardTestCase
{
    public function test_total_kas_dashboard_sama_dengan_kas_tunai_dan_bank(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);           // kas tunai 50.000.000 + 275.000
        $kasTunai = $this->saldoKas('kas');                       // 50.275.000
        $kasBank  = $this->saldoKas('bank');                      // 100.000.000

        $this->browse(function (Browser $browser) use ($kasTunai, $kasBank) {
            // 1-2. BBM: menu Kas Tunai (Cash) bulan berjalan dan Kas Bank Bendahara
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/kas/harian"]')
                ->waitForLocation('/kas/harian')
                ->visit('/kas/harian?filter_type=bulanan&bulan=' . date('n') . '&tahun=' . date('Y'));
            $this->assertContains('Rp' . $this->rp($kasTunai), $this->nilaiMetrik($browser), 'Saldo kas tunai BBM');
            $browser->visit('/kas/bank');
            $this->assertContains('Rp' . $this->rp($kasBank), $this->nilaiMetrik($browser), 'Saldo kas bank BBM');

            // 3-4. Manajer: Dashboard kartu Total Kas Bank Mini
            $this->loginSebagai($browser, self::MANAJER)
                ->waitFor('@dashboard-kas')
                ->screenshot('TC-I2-D03');
            $this->assertSame((string) (int) $kasTunai, $this->angka($browser, '@dashboard-kas-tunai'));
            $this->assertSame((string) (int) $kasBank, $this->angka($browser, '@dashboard-kas-bank'));
            $this->assertSame((string) (int) ($kasTunai + $kasBank), $this->angka($browser, '@dashboard-kas'));
        });
    }
}
