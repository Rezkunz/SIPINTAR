<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-08 (Negative) - Laporan periode tanpa data
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Muncul pesan 'Tidak ada data' tanpa error.
 */
class TC_14_08Test extends DashboardDuskTestCase
{
    public function test_laporan_periode_tanpa_data(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000); // data hanya ada di bulan berjalan

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/laporan/tabungan"]')
                ->waitForLocation('/laporan/tabungan')
                ->click('a.lap-tab[href*="/laporan/tabungan/kas-harian"]')
                ->waitForLocation('/laporan/tabungan/kas-harian')
                // 2-3. Pilih periode kosong (Januari tahun berjalan) -> laporan otomatis ditampilkan
                ->select('bulan', '1')
                ->waitForText('Belum ada transaksi setoran atau penarikan')
                ->assertDontSee('Exception')
                ->assertDontSee('500')
                ->screenshot('TC-14-08');
        });
    }
}
