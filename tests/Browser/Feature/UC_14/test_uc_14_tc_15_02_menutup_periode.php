<?php

namespace Tests\Browser\Feature\UC_14;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-14 TC-15-02 Menutup periode
 * Test Case: Input transaksi pada periode tertutup
 */
class test_uc_14_tc_15_02_menutup_periode extends SkenarioTestCase
{
    #[TestDox('UC-14 TC-15-02 Menutup periode')]
    public function test_uc_14_tc_15_02_menutup_periode(): void
    {
        // Form setoran Teller selalu memakai tanggal hari ini, sehingga periode bulan berjalan
        // disiapkan dalam keadaan tertutup untuk mensimulasikan setoran bertanggal di periode tertutup.
        DB::table('tblPERIODE')->updateOrInsert(
            ['tahun' => now()->year, 'bulan' => now()->month],
            ['status_periode' => 'tutup', 'tgl_tutup' => now(), 'user_id' => $this->akun('manajer')->user_id]
        );
        $saldoAwal = (float) DB::table('tblREKENING')->where('no_rek', '1001-2026-00001')->value('saldo');
        $jumlahTransaksi = DB::table('tblTRANSAKSI')->count();

        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['teller'])
                ->clickLink('Setoran')
                ->select('no_rek', '1001-2026-00001')
                ->type('nominal', '50000')
                ->press('Simpan Setoran')
                ->assertPathIs('/tabungan/setoran')
                ->assertSee('sudah ditutup Manajer dan terkunci');
        });

        $this->assertEquals($saldoAwal, (float) DB::table('tblREKENING')->where('no_rek', '1001-2026-00001')->value('saldo'));
        $this->assertSame($jumlahTransaksi, DB::table('tblTRANSAKSI')->count());
    }
}
