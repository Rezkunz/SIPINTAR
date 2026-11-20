<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D07 (Positive) - Laporan pinjaman dapat dicetak (unduh PDF)
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Temuan   : 3 - Laporan dan data pinjaman belum dapat dicetak.
 * Expected : PDF Rekap Pinjaman & Kolektibilitas dan Buku Kas Bendahara terunduh sesuai periode filter.
 */
class TC_I2_D07Test extends Iterasi2DashboardTestCase
{
    public function test_laporan_pinjaman_dapat_dicetak_pdf(): void
    {
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);
        $periode = '?bulan=' . date('n') . '&tahun=' . date('Y');

        $this->browse(function (Browser $browser) use ($periode) {
            // 1-2. Rekap Pinjaman & Kolektibilitas -> unduh PDF
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/laporan/pinjaman"]')
                ->waitForLocation('/laporan/pinjaman')
                ->visit('/laporan/pinjaman' . $periode)
                ->screenshot('TC-I2-D07');
            [$status, $tipe, $pdf] = $this->unduh($browser, 'a.lap-unduh-btn.pdf');
            $this->assertSame(200, $status);
            $this->assertStringContainsString('pdf', $tipe);
            $this->assertStringStartsWith('%PDF', $pdf);

            // 3-4. Buku Kas Bendahara & Angsuran -> unduh PDF
            $browser->visit('/laporan/pinjaman/kas-bendahara' . $periode);
            [$status, $tipe, $pdf] = $this->unduh($browser, 'a.lap-unduh-btn.pdf');
            $this->assertSame(200, $status);
            $this->assertStringContainsString('pdf', $tipe);
            $this->assertStringStartsWith('%PDF', $pdf);
        });
    }
}
