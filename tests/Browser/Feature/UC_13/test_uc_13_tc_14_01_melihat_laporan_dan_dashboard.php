<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-01 Melihat laporan dan dashboard
 * Test Case: Dashboard ringkasan keuangan
 */
class test_uc_13_tc_14_01_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-01 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_01_melihat_laporan_dan_dashboard(): void
    {
        $this->transaksiTeller('1001-2026-00001', 100000, 'setoran');
        $this->transaksiTeller('1001-2026-00002', 40000, 'penarikan');

        // Penjumlahan data langsung dari tabel
        $simpanan = (float) DB::table('tblREKENING')->where('status_rekening', 'aktif')->sum('saldo');
        $piutang  = $this->hitungSisaPiutangManual();
        $kasTunai = (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');
        $kasBank  = (float) DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo');

        $this->browse(function (Browser $browser) use ($simpanan, $piutang, $kasTunai, $kasBank) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->assertSeeIn('@dashboard-simpanan', self::rp($simpanan))
                ->assertSeeIn('@dashboard-piutang', self::rp($piutang))
                ->assertSeeIn('@dashboard-kas', self::rp($kasTunai + $kasBank))
                ->assertSeeIn('@dashboard-kas-tunai', self::rp($kasTunai))
                ->assertSeeIn('@dashboard-kas-bank', self::rp($kasBank));
        });
    }
}
