<?php

namespace Tests\Browser\Feature\UC_14;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-14 TC-15-01 Menutup periode
 * Test Case: Menutup periode bulanan
 */
class test_uc_14_tc_15_01_menutup_periode extends SkenarioTestCase
{
    #[TestDox('UC-14 TC-15-01 Menutup periode')]
    public function test_uc_14_tc_15_01_menutup_periode(): void
    {
        // Bulan uji: September 2026 (periode terbuka paling awal, sudah berakhir)
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->clickLink('Tutup Periode')
                ->assertPathIs('/periode/tutup')
                ->click('a[href*="tahun=2026&bulan=9"]')
                ->waitForText('Tutup Buku September 2026')
                ->check('konfirmasi')
                ->press('Tutup Periode')
                ->acceptDialog()
                ->waitForText('Sudah Ditutup')
                ->assertSee('Ditutup pada')
                ->assertSee('oleh ' . $this->akun('manajer')->nama_user);
        });

        $periode = DB::table('tblPERIODE')->where(['tahun' => 2026, 'bulan' => 9])->first();
        $this->assertSame('tutup', $periode->status_periode);
        $this->assertSame($this->akun('manajer')->user_id, (int) $periode->user_id);
        $this->assertNotNull($periode->tgl_tutup);
    }
}
