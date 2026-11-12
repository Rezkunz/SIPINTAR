<?php

namespace Tests\Browser\Feature\UC_15;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-15 TC-16-03 Mengelola akun pengguna
 * Test Case: Menonaktifkan akun
 */
class test_uc_15_tc_16_03_mengelola_akun_pengguna extends SkenarioTestCase
{
    #[TestDox('UC-15 TC-16-03 Mengelola akun pengguna')]
    public function test_uc_15_tc_16_03_mengelola_akun_pengguna(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->visit('/akun/kelola?cari=' . urlencode(self::AKUN['teller']))
                ->assertSee('Teller Uji')
                ->press('Nonaktifkan')
                ->acceptDialog()
                ->waitForText('Aktifkan');

            $this->logoutMenu($browser);

            // Akun nonaktif tidak dapat login
            $browser->visit('/login')
                ->waitFor('#btn-submit')
                ->type('login', self::AKUN['teller'])
                ->type('password', self::SANDI)
                ->click('#btn-submit')
                ->waitForText('NONAKTIF')
                ->assertPathIs('/login');
        });

        $this->assertSame('nonaktif', $this->akun('teller')->status_akun);
    }
}
