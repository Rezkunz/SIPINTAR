<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-09 (Positive) - Navigasi alur pinjaman & tampilan resolusi laptop
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Bingung "ke mana daftar pinjaman / pengajuan", dan perlu zoom out agar layar muat.
 * Expected : Daftar Pinjaman punya tombol Ajukan Pinjaman;
 *            halaman Bendahara Bank Mini (termasuk Dashboard) tidak meluber horizontal pada 1280x720,
 *            dan nominal di kartu metrik tidak keluar dari kartunya.
 */
class TC_I2_09Test extends Iterasi2DuskTestCase
{
    public function test_navigasi_alur_dan_resolusi_laptop(): void
    {
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BBM)->resize(1280, 720);

            // Dari Daftar Pinjaman langsung ke form pengajuan
            $browser->click('a[href$="/bbm/pinjaman"]')
                ->waitForLocation('/bbm/pinjaman')
                ->click('a.btn-banner-white[href$="/bbm/pinjaman/ajukan"]')
                ->waitForLocation('/bbm/pinjaman/ajukan');

            foreach (['/dashboard', '/bbm/pinjaman', '/bbm/pinjaman/ajukan', '/bbm/pinjaman/kelayakan', '/bbm/pinjaman/cair',
                      '/bbm/angsuran/langsung', '/bbm/angsuran/potongan', '/bbm/kartu', '/kas/harian', '/kas/bank'] as $path) {
                $browser->visit($path);
                $lebar = $browser->script('return [document.documentElement.scrollWidth, window.innerWidth];')[0];
                $this->assertLessThanOrEqual($lebar[1] + 1, $lebar[0], "Halaman {$path} meluber horizontal ({$lebar[0]}px > {$lebar[1]}px)");

                $angkaKeluarKartu = $browser->script(
                    'return [...document.querySelectorAll(".metric-value")].filter(e => e.scrollWidth > e.clientWidth + 1).map(e => e.innerText.trim());'
                )[0];
                $this->assertSame([], $angkaKeluarKartu, "Nominal keluar dari kartu metrik di {$path}");
            }

            $browser->visit('/dashboard')->screenshot('TC-I2-09');
        });
    }
}
