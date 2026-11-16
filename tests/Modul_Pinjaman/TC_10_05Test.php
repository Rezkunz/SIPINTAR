<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-10 | TC-10-05 (Negative) - Akses menu pencairan pinjaman
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Akses ditolak.
 */
class TC_10_05Test extends SipintarDuskTestCase
{
    public function test_akses_menu_pencairan_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BND_SKL)
                ->assertMissing('a[href$="/bbm/pinjaman/cair"]')
                ->visit('/bbm/pinjaman/cair')
                ->assertSee('403')
                ->assertMissing('#tableSiapCair')
                ->screenshot('TC-10-05');
        });
    }
}
