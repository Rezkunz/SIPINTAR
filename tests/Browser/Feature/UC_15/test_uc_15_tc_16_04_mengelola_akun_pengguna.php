<?php

namespace Tests\Browser\Feature\UC_15;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-15 TC-16-04 Mengelola akun pengguna
 * Test Case: Mengubah peran akun
 */
class test_uc_15_tc_16_04_mengelola_akun_pengguna extends SkenarioTestCase
{
    #[TestDox('UC-15 TC-16-04 Mengelola akun pengguna')]
    public function test_uc_15_tc_16_04_mengelola_akun_pengguna(): void
    {
        $this->browse(function (Browser $browser) {
            // Sebelum: menu Siswa Praktik
            $this->loginForm($browser, self::AKUN['siswa'])
                ->assertSeeIn('#app-sidebar', 'Input Draft');
            $this->logoutMenu($browser);

            $this->loginForm($browser, self::AKUN['manajer'])
                ->visit('/akun/kelola?cari=' . urlencode(self::AKUN['siswa']))
                ->assertSee('Siswa Praktik Uji')
                ->clickLink('Ubah')
                ->waitForText('Ubah Akun')
                ->select('#akun_role', 'teller')
                ->press('Simpan Perubahan')
                ->waitForText('berhasil diperbarui');
            $this->logoutMenu($browser);

            // Login ulang: menu berubah menjadi menu Teller
            $this->loginForm($browser, self::AKUN['siswa'])
                ->assertSeeIn('#app-sidebar', 'Verifikasi Draft')
                ->assertSeeIn('#app-sidebar', 'Dashboard Loket')
                ->assertDontSeeIn('#app-sidebar', 'Input Draft');
        });

        $this->assertSame('teller', $this->akun('siswa')->role);
    }
}
