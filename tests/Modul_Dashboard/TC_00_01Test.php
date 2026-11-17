<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-00 | TC-00-01 (Positive) - Login tiap peran dengan kredensial benar
 * Aktor    : Semua Aktor - akun tiap peran
 * Expected : Setiap peran masuk ke dashboard masing-masing dan hanya melihat menu sesuai hak akses.
 */
class TC_00_01Test extends DashboardDuskTestCase
{
    public function test_login_tiap_peran(): void
    {
        $this->browse(function (Browser $browser) {
            foreach (self::MENU_PERAN as $email => $menu) {
                // 1. Login bergantian dengan akun ketujuh peran
                $this->loginSebagai($browser, $email)->assertPathIs('/dashboard');

                // 2. Periksa menu yang tampil sesuai hak akses
                foreach ($menu['ada'] as $path) {
                    $browser->assertPresent("a[href$=\"{$path}\"]");
                }
                foreach ($menu['tidak'] as $path) {
                    $browser->assertMissing("a[href$=\"{$path}\"]");
                }

                $browser->screenshot('TC-00-01-' . strstr($email, '@', true));
            }
        });
    }
}
