<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-04 (Positive) - Nasabah melihat saldo dan mutasi
 * Aktor    : Nasabah (Siswa) - nasabah.siswa@sipintar.test
 * Expected : Saldo dan mutasi tampil sesuai transaksi miliknya saja.
 */
class TC_14_04Test extends DashboardDuskTestCase
{
    public function test_nasabah_melihat_saldo_dan_mutasi(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::SISWA, 0, 25000);
        $this->transaksiTabungan(self::PEGAWAI, 180000); // milik nasabah lain

        $milikSendiri = $this->rekening(self::SISWA);
        $milikLain = $this->rekening(self::PEGAWAI);

        $this->browse(function (Browser $browser) use ($milikSendiri, $milikLain) {
            // 2. Buka menu Rekening Saya (Riwayat Mutasi)
            $this->loginSebagai($browser, self::SISWA)
                ->click('a[href$="/nasabah/saldo"]')
                ->waitForLocation('/nasabah/saldo')
                ->assertSee($milikSendiri->no_rek)
                ->assertSee('Rp 250.000')       // saldo = 275.000 - 25.000
                ->assertSee('+ Rp 275.000')     // setoran
                ->assertSee('- Rp 25.000')      // penarikan
                ->assertDontSee($milikLain->no_rek)
                ->assertDontSee('180.000')
                ->screenshot('TC-14-04');
        });
    }
}
