<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D02 (Positive) - Sisa Piutang Dashboard sama dengan Laporan Pinjaman dan Kartu Piutang
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Temuan   : 2 - Angka dashboard perlu sama dengan kartu piutang dan laporan.
 * Expected : Ketiga nilai sisa piutang sama (pinjaman awal - pokok dibayar).
 */
class TC_I2_D02Test extends Iterasi2DashboardTestCase
{
    public function test_sisa_piutang_dashboard_sama_dengan_laporan_dan_kartu_piutang(): void
    {
        // Pinjaman 5.000.000 / 10 bulan, 2 angsuran (pokok 2 x 500.000) sudah dibayar -> sisa 4.000.000
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            // 1-2. BBM: Kartu Piutang
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/kartu"]')
                ->waitForLocation('/bbm/kartu')
                ->assertSee('4.000.000');

            // 3. Manajer: Dashboard
            $this->loginSebagai($browser, self::MANAJER)->waitFor('@dashboard-piutang');
            $this->assertSame('4000000', $this->angka($browser, '@dashboard-piutang'));

            // 4. Laporan Pinjaman - Rekap Pinjaman & Kolektibilitas
            $browser->click('a[href$="/laporan/pinjaman"]')
                ->waitForLocation('/laporan/pinjaman')
                ->screenshot('TC-I2-D02');
            $this->assertSame('Rp4.000.000', $this->metrik($browser, 'Total Sisa Pokok (Outstanding)'));
        });
    }
}
