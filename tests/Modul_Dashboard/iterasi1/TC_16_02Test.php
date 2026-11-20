<?php

namespace Tests\Modul_Dashboard\iterasi1;

use Tests\Modul_Dashboard\DashboardDuskTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-15 | TC-16-02 (Negative) - Email sudah terdaftar
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Validasi 'Email sudah terdaftar'.
 */
class TC_16_02Test extends DashboardDuskTestCase
{
    public function test_email_sudah_terdaftar_ditolak(): void
    {
        $jumlahAwal = DB::table('tblUSER')->count();

        $this->browse(function (Browser $browser) {
            // 2-3. Tambah akun dengan email yang sudah ada, Simpan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/akun/kelola"]')
                ->waitForLocation('/akun/kelola')
                ->waitFor('#akun_role')
                ->select('role', 'teller');
            $this->isi($browser, '#akun_nama', 'Teller Duplikat');
            $this->isi($browser, '#akun_hp', '089933334444');
            $this->isi($browser, '#akun_email', 'teller@sipintar.test');
            $this->isi($browser, '#akun_password', 'TellerBaru#2026');
            $this->isi($browser, '#akun_password2', 'TellerBaru#2026');
            $browser->press('Tambah Akun');
            $browser->waitForText('Email ini sudah dipakai akun lain.')
                ->screenshot('TC-16-02');
        });

        $this->assertSame($jumlahAwal, DB::table('tblUSER')->count());
        $this->assertSame(1, DB::table('tblUSER')->where('email', 'teller@sipintar.test')->count());
    }
}
