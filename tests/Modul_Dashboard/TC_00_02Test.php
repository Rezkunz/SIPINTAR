<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-00 | TC-00-02 (Negative) - Password salah
 * Aktor    : Semua Aktor - teller@sipintar.test
 * Expected : Pesan 'Email atau password salah'; tidak masuk sistem.
 */
class TC_00_02Test extends DashboardDuskTestCase
{
    public function test_password_salah_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            // 1-2. Nomor HP terdaftar + password salah, klik Login
            $this->kirimLogin($browser, self::TELLER, 'PasswordSalah#123');

            $pesan = $this->pesanPopup($browser);
            $browser->assertPathIs('/login')->screenshot('TC-00-02');
            $this->assertStringContainsString('salah', $pesan);

            // Tidak masuk sistem: dashboard tetap meminta login
            $browser->visit('/dashboard')->assertPathIs('/login');
        });
    }

    public function test_login_memakai_email_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            // Login hanya dengan nomor HP: email + password benar tetap ditolak
            $browser->visit('/login');
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/login')->waitFor('#login');
            $browser->script("document.querySelector('#login').value = " . json_encode(self::TELLER) . ";");
            $this->isi($browser, '#password', \Database\Seeders\DuskSeeder::PASSWORD);
            $browser->press('#btn-submit');

            $pesan = $this->pesanPopup($browser);
            $browser->assertPathIs('/login')->screenshot('TC-00-02-email');
            $this->assertStringContainsString('nomor HP', $pesan);
        });
    }
}
