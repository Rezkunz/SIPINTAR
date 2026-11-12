<?php

namespace Tests\Browser\Feature\UC_00;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-00 TC-00-02 Login dan autentikasi
 * Test Case: Password salah
 */
class test_uc_00_tc_00_02_login_dan_autentikasi extends SkenarioTestCase
{
    #[TestDox('UC-00 TC-00-02 Login dan autentikasi')]
    public function test_uc_00_tc_00_02_login_dan_autentikasi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('#btn-submit')
                ->type('login', self::AKUN['teller'])
                ->type('password', 'sandi-salah-123')
                ->click('#btn-submit')
                ->waitForText('Kata sandi yang Anda masukkan salah')
                ->assertPathIs('/login');

            // Tidak masuk sistem: halaman yang memerlukan login tetap mengarah ke halaman login
            $browser->visit('/dashboard')->assertPathIs('/login');
        });
    }
}
