<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-15 | TC-16-01 (Positive) - Membuat akun baru
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Akun tersimpan dan bisa login dengan menu sesuai peran Teller.
 */
class TC_16_01Test extends DashboardDuskTestCase
{
    public function test_membuat_akun_baru(): void
    {
        $this->browse(function (Browser $browser) {
            // 2-4. Kelola Akun > Tambah, isi nama, email, peran Teller, Simpan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/akun/kelola"]')
                ->waitForLocation('/akun/kelola')
                ->waitFor('#akun_role')
                ->select('role', 'teller');
            $this->isi($browser, '#akun_nama', 'Teller Baru Uji');
            $this->isi($browser, '#akun_hp', '089911112222');
            $this->isi($browser, '#akun_email', 'teller.baru@sipintar.test');
            $this->isi($browser, '#akun_password', 'TellerBaru#2026');
            $this->isi($browser, '#akun_password2', 'TellerBaru#2026');
            $browser->press('Tambah Akun');
            $browser->waitForText('berhasil dibuat')->screenshot('TC-16-01-tambah');

            // 5. Login dengan akun baru
            $this->kirimLogin($browser, 'teller.baru@sipintar.test', 'TellerBaru#2026')
                ->waitForLocation('/dashboard', 15)
                ->assertPresent('a[href$="/tabungan/setoran"]')
                ->assertPresent('a[href$="/tabungan/verifikasi"]')
                ->assertMissing('a[href$="/akun/kelola"]')
                ->screenshot('TC-16-01');
        });

        $this->assertDatabaseHas('tblUSER', ['email' => 'teller.baru@sipintar.test', 'role_id' => 1, 'status_akun' => 'aktif']);
    }
}
