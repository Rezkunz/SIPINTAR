<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D08 (Positive) - Laporan dapat ditampilkan per bulan dan per tahun
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Temuan   : 4 - Laporan transaksi diminta tersedia per bulan dan per tahun.
 * Expected : Isi laporan berganti sesuai bulan/tahun terpilih; periode tanpa transaksi tampil "Belum ada ..." tanpa error.
 */
class TC_I2_D08Test extends Iterasi2DashboardTestCase
{
    public function test_laporan_per_bulan_dan_per_tahun(): void
    {
        $bulanLalu = now()->subMonthNoOverflow()->startOfMonth();
        $this->transaksiTabungan(self::SISWA, 100000, 0, $bulanLalu->copy()->setTime(8, 0)->toDateTimeString());
        $this->transaksiTabungan(self::SISWA, 275000);

        $this->browse(function (Browser $browser) use ($bulanLalu) {
            // 1-2. Rekap Harian bulan lalu
            $this->loginSebagai($browser, self::MANAJER)
                ->visit('/laporan/tabungan?kategori=siswa&bulan=' . $bulanLalu->month . '&tahun=' . $bulanLalu->year);
            $this->assertSame('Rp100.000', $this->metrik($browser, 'Total Setoran'));

            // 3. Panah bulan berikutnya -> bulan berjalan
            $browser->click('a.lap-periode-nav[title^="Bulan berikutnya"]')->pause(800);
            $this->assertSame('Rp275.000', $this->metrik($browser, 'Total Setoran'));

            // 4. Bulan tanpa transaksi (bulan depan) pada Buku Kas Harian
            $depan = now()->addMonthNoOverflow();
            $browser->visit('/laporan/tabungan/kas-harian?bulan=' . $depan->month . '&tahun=' . $depan->year)
                ->assertSee('Belum ada transaksi setoran atau penarikan')
                ->assertDontSee('Exception');

            // 5. Laporan Tahunan: tahun berjalan lalu tahun sebelumnya
            $browser->visit('/laporan/tahunan?tahun=' . date('Y'))->assertSee('Laporan Laba Rugi');
            $browser->visit('/laporan/tahunan?tahun=' . (date('Y') - 1))
                ->assertSee('Laporan Laba Rugi')
                ->assertDontSee('Exception');

            $browser->visit('/laporan/tabungan?kategori=siswa&bulan=' . date('n') . '&tahun=' . date('Y'))
                ->screenshot('TC-I2-D08');
        });
    }
}
