<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-05 Melihat laporan dan dashboard
 * Test Case: Nasabah melihat saldo dan mutasi
 */
class test_uc_13_tc_14_05_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-05 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_05_melihat_laporan_dan_dashboard(): void
    {
        $this->transaksiTeller('1001-2026-00001', 125000, 'setoran');
        $this->transaksiTeller('1001-2026-00002', 77000, 'setoran'); // milik nasabah lain

        $saldo = (float) DB::table('tblREKENING')->where('no_rek', '1001-2026-00001')->value('saldo');

        $this->browse(function (Browser $browser) use ($saldo) {
            $this->loginForm($browser, self::AKUN['nasabah_siswa'])
                ->clickLink('Riwayat Mutasi')
                ->assertPathIs('/nasabah/saldo')
                ->assertSee('1001-2026-00001')
                ->assertSee(self::rp($saldo))
                ->assertSee('125.000')
                ->assertDontSee('1001-2026-00002')
                ->assertDontSee('77.000');
        });
    }
}
