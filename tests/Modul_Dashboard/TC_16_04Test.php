<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-15 | TC-16-04 (Positive) - Mengubah peran akun
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Menu yang tampil berubah sesuai peran baru.
 */
class TC_16_04Test extends DashboardDuskTestCase
{
    public function test_mengubah_peran_akun(): void
    {
        $praktikId = $this->userId(self::PRAKTIK);

        $this->browse(function (Browser $browser) use ($praktikId) {
            // Menu awal sebagai Siswa Praktik
            $this->loginSebagai($browser, self::PRAKTIK)
                ->assertPresent('a[href$="/tabungan/draft"]')
                ->assertMissing('a[href$="/tabungan/verifikasi"]');

            // 2. Manajer mengubah peran menjadi Teller
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/akun/kelola"]')
                ->waitForLocation('/akun/kelola')
                ->click("a[href*=\"edit={$praktikId}\"]")
                ->waitFor('#akun_role')
                ->select('role', 'teller')
                ->press('Simpan Perubahan')
                ->waitForText('berhasil diperbarui')
                ->screenshot('TC-16-04-ubah');

            // 3. Login ulang dengan akun tersebut
            $this->loginSebagai($browser, self::PRAKTIK)
                ->assertPresent('a[href$="/tabungan/verifikasi"]')
                ->assertPresent('a[href$="/tabungan/setoran"]')
                ->assertMissing('a[href$="/tabungan/draft"]')
                ->screenshot('TC-16-04');
        });

        $this->assertDatabaseHas('tblUSER', ['user_id' => $praktikId, 'role_id' => 1]);
    }
}
