<?php

namespace Tests\Browser\Feature\UC_00;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-00 TC-00-03 Login dan autentikasi
 * Test Case: Logout
 */
class test_uc_00_tc_00_03_login_dan_autentikasi extends SkenarioTestCase
{
    #[TestDox('UC-00 TC-00-03 Login dan autentikasi')]
    public function test_uc_00_tc_00_03_login_dan_autentikasi(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['teller'])
                ->visit('/tabungan/setoran')
                ->assertSee('Simpan Setoran');

            $this->logoutMenu($browser)->assertPathIs('/login');

            // Tombol Back di browser tidak boleh menampilkan halaman sebelumnya
            $browser->back()->pause(800)
                ->assertPathIs('/login')
                ->assertDontSee('Simpan Setoran');

            $browser->visit('/tabungan/setoran')->assertPathIs('/login');
        });
    }
}
