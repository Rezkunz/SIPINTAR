<?php

namespace Tests\Browser\Feature\UC_14;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-14 TC-15-03 Kontrol akses tutup periode
 * Test Case: Selain Manajer menutup periode
 */
class test_uc_14_tc_15_03_kontrol_akses_tutup_periode extends SkenarioTestCase
{
    #[TestDox('UC-14 TC-15-03 Kontrol akses tutup periode')]
    public function test_uc_14_tc_15_03_kontrol_akses_tutup_periode(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['bendahara'])
                ->assertDontSeeIn('#app-sidebar', 'Tutup Periode')
                ->visit('/periode/tutup')
                ->assertSee('Halaman Tidak Dapat Diakses')
                ->assertDontSee('Tutup Buku');
        });

        $this->assertSame(0, DB::table('tblPERIODE')->where('status_periode', 'tutup')->count());
    }
}
