<?php

namespace Tests\Browser\Feature\UC_15;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-15 TC-16-02 Mengelola akun pengguna
 * Test Case: Email sudah terdaftar
 */
class test_uc_15_tc_16_02_mengelola_akun_pengguna extends SkenarioTestCase
{
    #[TestDox('UC-15 TC-16-02 Mengelola akun pengguna')]
    public function test_uc_15_tc_16_02_mengelola_akun_pengguna(): void
    {
        $jumlahAkun = DB::table('tblUSER')->count();

        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->clickLink('Kelola Pengguna')
                ->type('#akun_nama', 'Akun Ganda Uji')
                ->select('#akun_role', 'teller')
                ->type('#akun_email', self::AKUN['teller'])
                ->type('#akun_hp', '089000000998')
                ->type('#akun_password', 'rahasia123')
                ->type('#akun_password2', 'rahasia123')
                ->press('Tambah Akun')
                ->waitForText('Email ini sudah dipakai akun lain.');
        });

        $this->assertSame($jumlahAkun, DB::table('tblUSER')->count());
    }
}
