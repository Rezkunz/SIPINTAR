<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-15 | TC-16-03 (Positive) - Menonaktifkan akun
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Akun nonaktif tidak dapat login.
 */
class TC_16_03Test extends DashboardDuskTestCase
{
    public function test_menonaktifkan_akun(): void
    {
        $tellerId = $this->userId(self::TELLER);

        $this->browse(function (Browser $browser) use ($tellerId) {
            // 2. Klik Nonaktifkan pada akun Teller
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/akun/kelola"]')
                ->waitForLocation('/akun/kelola')
                ->click("form[action$=\"/akun/kelola/{$tellerId}/status\"] button[type=submit]")
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('nonaktif dan tidak dapat login')
                ->screenshot('TC-16-03-nonaktif');

            // 3. Coba login dengan akun tersebut
            $this->kirimLogin($browser, self::TELLER, \Database\Seeders\DuskSeeder::PASSWORD);
            $pesan = $this->pesanPopup($browser);
            $browser->assertPathIs('/login')->screenshot('TC-16-03');
            $this->assertStringContainsString('NONAKTIF', $pesan);
        });

        $this->assertDatabaseHas('tblUSER', ['user_id' => $tellerId, 'status_akun' => 'nonaktif']);
    }
}
