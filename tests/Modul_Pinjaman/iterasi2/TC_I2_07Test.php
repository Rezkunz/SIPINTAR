<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-07 (Positive) - Cetak laporan Kas Tunai & Kas Bank sesuai periode
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : "Di print laporannya" (kas tunai & mutasi bank).
 * Expected : Laporan cetak mengikuti periode filter di layar; total masuk/keluar dan rincian mutasi sesuai data.
 */
class TC_I2_07Test extends Iterasi2DuskTestCase
{
    public function test_cetak_laporan_kas_tunai_dan_bank(): void
    {
        $this->mutasiKas('kas', 'masuk', 250000, 'Penerimaan iuran koperasi (uji cetak)');
        $this->mutasiKas('bank', 'keluar', 1000000, 'Biaya administrasi bank (uji cetak)');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $label = $bulan[(int) date('n')] . ' ' . date('Y');
        $query = '?filter_type=bulanan&bulan=' . date('n') . '&tahun=' . date('Y');

        $this->browse(function (Browser $browser) use ($label, $query) {
            // Kas Tunai: filter per bulan lalu Cetak Laporan
            $this->loginSebagai($browser, self::BBM)->visit('/kas/harian' . $query);
            $href = $browser->attribute('#btnCetakKas', 'href');
            $this->assertStringContainsString('filter_type=bulanan', $href);

            $browser->visit($href)
                ->assertSee('LAPORAN MUTASI KAS TUNAI')
                ->assertSeeIn('#periodeCetak', $label)
                ->assertSeeIn('#totalMasukCetak', 'Rp 250.000')
                ->assertSeeIn('#tabelKasCetak', 'Penerimaan iuran koperasi (uji cetak)')
                ->assertDontSeeIn('#tabelKasCetak', 'Biaya administrasi bank')
                ->screenshot('TC-I2-07-kas-tunai');

            // Kas Bank
            $browser->visit('/kas/bank' . $query);
            $browser->visit($browser->attribute('#btnCetakKas', 'href'))
                ->assertSee('LAPORAN MUTASI KAS BANK')
                ->assertSeeIn('#periodeCetak', $label)
                ->assertSeeIn('#totalKeluarCetak', 'Rp 1.000.000')
                ->assertSeeIn('#tabelKasCetak', 'Biaya administrasi bank (uji cetak)')
                ->screenshot('TC-I2-07');
        });
    }
}
