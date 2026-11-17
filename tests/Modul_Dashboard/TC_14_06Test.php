<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-06 (Negative) - Mengakses rekening milik nasabah lain
 * Aktor    : Nasabah (Siswa) - nasabah.siswa@sipintar.test
 * Expected : Akses ditolak; data nasabah lain tidak tampil.
 */
class TC_14_06Test extends DashboardDuskTestCase
{
    public function test_akses_rekening_nasabah_lain_ditolak(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 180000);

        $milikSendiri = $this->rekening(self::SISWA);
        $milikLain = $this->rekening(self::PEGAWAI);

        $this->browse(function (Browser $browser) use ($milikSendiri, $milikLain) {
            // 2. Buka detail rekening sendiri
            $this->loginSebagai($browser, self::SISWA)
                ->visit('/nasabah/saldo')
                ->assertSee($milikSendiri->no_rek);

            // 3. Ubah ID rekening di URL menjadi milik nasabah lain
            foreach ([
                "/nasabah/saldo?rekening_id={$milikLain->rekening_id}",
                "/nasabah/saldo?nasabah_id={$milikLain->nasabah_id}",
                "/nasabah/saldo?no_rek={$milikLain->no_rek}",
            ] as $url) {
                $browser->visit($url)
                    ->assertDontSee($milikLain->no_rek)
                    ->assertDontSee('180.000');
            }

            // Endpoint mutasi petugas dengan ID rekening lain -> 403
            $browser->visit("/tabungan/mutasi?rekening_id={$milikLain->rekening_id}")
                ->assertSee('403')
                ->assertDontSee($milikLain->no_rek)
                ->screenshot('TC-14-06');
        });
    }
}
