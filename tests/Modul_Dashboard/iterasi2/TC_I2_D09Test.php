<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D09 (Positive) - Halaman Manajer tampil rapi pada resolusi laptop 1280x720
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Temuan   : 8 - Tampilan perlu muat pada resolusi laptop tanpa zoom out.
 * Expected : Tidak ada scroll horizontal; nominal kartu metrik tidak keluar dari kartunya.
 */
class TC_I2_D09Test extends Iterasi2DashboardTestCase
{
    public function test_halaman_manajer_rapi_pada_resolusi_laptop(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::MANAJER)->resize(1280, 720);

            foreach (['/dashboard', '/laporan/tabungan', '/laporan/tabungan/bulanan', '/laporan/tabungan/kas-harian',
                      '/laporan/pinjaman', '/laporan/pinjaman/kas-bendahara', '/laporan/tahunan', '/laporan/neraca',
                      '/laporan/ekspor', '/periode/tutup', '/akun/kelola'] as $path) {
                $browser->visit($path);
                $lebar = $browser->script('return [document.documentElement.scrollWidth, window.innerWidth];')[0];
                $this->assertLessThanOrEqual($lebar[1] + 1, $lebar[0], "Halaman {$path} meluber horizontal ({$lebar[0]}px > {$lebar[1]}px)");

                $angkaKeluarKartu = $browser->script(
                    'return [...document.querySelectorAll(".metric-value")].filter(e => e.scrollWidth > e.clientWidth + 1).map(e => e.innerText.trim());'
                )[0];
                $this->assertSame([], $angkaKeluarKartu, "Nominal keluar dari kartu metrik di {$path}");
            }

            $browser->visit('/dashboard')->screenshot('TC-I2-D09');
        });
    }
}
