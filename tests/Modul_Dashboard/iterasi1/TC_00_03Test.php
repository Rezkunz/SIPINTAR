<?php

namespace Tests\Modul_Dashboard\iterasi1;

use Tests\Modul_Dashboard\DashboardDuskTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-00 | TC-00-03 (Positive) - Logout
 * Aktor    : Semua Aktor - manajer@sipintar.test
 * Expected : Kembali ke halaman login; halaman sebelumnya tidak bisa diakses tanpa login.
 */
class TC_00_03Test extends DashboardDuskTestCase
{
    public function test_logout(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Klik Logout (menu profil > Sign Out / Keluar)
            $this->loginSebagai($browser, self::MANAJER)
                ->click('#topbar-profile-btn')
                ->waitFor('.item-logout')
                ->click('.item-logout')
                ->waitForLocation('/login')
                ->assertPresent('#login');

            // 2. Tekan tombol Back di browser
            $browser->back()->pause(1500)->screenshot('TC-00-03');

            // Halaman sebelumnya tidak boleh menampilkan data/area login-only
            $browser->assertMissing('#topbar-profile-btn');

            // Akses langsung juga diarahkan ke login
            $browser->visit('/dashboard')->assertPathIs('/login');
        });
    }
}
