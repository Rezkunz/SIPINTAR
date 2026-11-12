<?php

namespace Tests\Browser\Feature\UC_15;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-15 TC-16-01 Mengelola akun pengguna
 * Test Case: Membuat akun baru
 */
class test_uc_15_tc_16_01_mengelola_akun_pengguna extends SkenarioTestCase
{
    #[TestDox('UC-15 TC-16-01 Mengelola akun pengguna')]
    public function test_uc_15_tc_16_01_mengelola_akun_pengguna(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->clickLink('Kelola Pengguna')
                ->assertPathIs('/akun/kelola')
                ->type('#akun_nama', 'Teller Baru Uji')
                ->select('#akun_role', 'teller')
                ->type('#akun_email', 'teller.baru@sipintar.test')
                ->type('#akun_hp', '089000000999')
                ->type('#akun_password', 'rahasia123')
                ->type('#akun_password2', 'rahasia123')
                ->press('Tambah Akun')
                ->waitForText('berhasil dibuat');

            $this->logoutMenu($browser);

            // Login dengan akun baru: menu sesuai peran Teller
            $this->loginForm($browser, 'teller.baru@sipintar.test', 'rahasia123')
                ->assertSeeIn('#topbar-user-name-label', 'Teller Baru Uji')
                ->assertSeeIn('#app-sidebar', 'Verifikasi Draft')
                ->assertSeeIn('#app-sidebar', 'Setoran')
                ->assertDontSeeIn('#app-sidebar', 'Kelola Pengguna');
        });

        $this->assertSame(1, DB::table('tblUSER')->where('email', 'teller.baru@sipintar.test')->where('role_id', 1)->count());
    }
}
