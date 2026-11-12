<?php

namespace Tests\Browser\Feature\UC_13;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-09 Melihat laporan dan dashboard
 * Test Case: Laporan periode tanpa data
 */
class test_uc_13_tc_14_09_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-09 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_09_melihat_laporan_dan_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->clickLink('Laporan Tabungan')
                ->visit('/laporan/tabungan/bulanan');

            $this->pilihPeriode($browser, 1, now()->year - 3);

            $browser->assertSee('Belum ada')
                ->assertDontSee('Terjadi Kendala')
                ->assertDontSee('1001-2026-00001');
        });
    }
}
