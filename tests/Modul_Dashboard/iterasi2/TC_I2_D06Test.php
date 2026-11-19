<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D06 (Positive) - Laba rugi tahunan konsisten dengan Neraca dan Neraca seimbang
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Temuan   : 2 - Laporan yang dihasilkan otomatis perlu divalidasi.
 * Expected : Laba bersih = laba tahun berjalan di Neraca; Total Aktiva = Total Kewajiban & Ekuitas.
 */
class TC_I2_D06Test extends Iterasi2DashboardTestCase
{
    public function test_laba_rugi_tahunan_konsisten_dengan_neraca(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 150000);
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            // 1-2. Laporan Tahunan tahun berjalan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/laporan/tahunan"]')
                ->waitForLocation('/laporan/tahunan')
                ->visit('/laporan/tahunan?tahun=' . date('Y'));
            $pendapatan = (int) preg_replace('/\D/', '', $this->metrik($browser, 'Total Pendapatan'));
            $beban      = (int) preg_replace('/\D/', '', $this->metrik($browser, 'Total Beban'));
            $laba       = $this->metrik($browser, 'Laba Bersih') ?? $this->metrik($browser, 'Rugi Bersih');
            $this->assertSame(abs($pendapatan - $beban), (int) preg_replace('/\D/', '', $laba), 'Laba = pendapatan - beban');

            // 3-4. Neraca & Gabungan per akhir bulan berjalan
            $browser->visit('/laporan/neraca?bulan=' . date('n') . '&tahun=' . date('Y'))->screenshot('TC-I2-D06');
            $pos = $this->posNeraca($browser);
            $this->assertSame($pos['Total Aktiva'], $pos['Total Kewajiban & Ekuitas'], 'Neraca seimbang');
            $this->assertSame($this->rp(abs($pendapatan - $beban)), $pos['Laba (Rugi) Tahun Berjalan'], 'Laba berjalan = Laporan Tahunan');
        });
    }
}
