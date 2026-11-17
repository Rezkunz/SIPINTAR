<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-14 | TC-15-03 (Negative) - Selain Manajer menutup periode
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Akses ditolak.
 */
class TC_15_03Test extends DashboardDuskTestCase
{
    public function test_selain_manajer_menutup_periode_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            // 2. Akses langsung URL menu Tutup Periode
            $this->loginSebagai($browser, self::BBM)
                ->assertMissing('a[href$="/periode/tutup"]')
                ->visit('/periode/tutup')
                ->assertSee('403')
                ->assertMissing('input[name="konfirmasi"]')
                ->screenshot('TC-15-03');
        });
    }
}
