<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-03 Melihat laporan dan dashboard
 * Test Case: Laporan tabungan bulanan
 */
class test_uc_13_tc_14_03_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-03 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_03_melihat_laporan_dan_dashboard(): void
    {
        // Dipetakan ke menu Log Transaksi Teller (rekap per tanggal)
        $this->transaksiTeller('1001-2026-00001', 100000, 'setoran');
        $this->transaksiTeller('1001-2026-00002', 50000, 'setoran');
        $this->transaksiTeller('1001-2026-00003', 30000, 'penarikan');

        $hariIni = DB::table('tblTRANSAKSI')->whereDate('tgl_transaksi', today());
        $jumlah = (clone $hariIni)->count();
        $kredit = (float) (clone $hariIni)->sum('kredit');
        $debit  = (float) (clone $hariIni)->sum('debit');

        $this->browse(function (Browser $browser) use ($jumlah, $kredit, $debit) {
            $this->loginForm($browser, self::AKUN['teller'])
                ->clickLink('Log Transaksi')
                ->assertPathIs('/tabungan/log-transaksi')
                ->waitForReload(fn (Browser $b) => $b->script(
                    "document.getElementById('tanggal').value = '" . today()->toDateString() . "'; document.getElementById('tanggal').form.submit();"
                ))
                ->assertSee('Transaksi ' . today()->toDateString())
                ->assertSee('Rp' . self::rp($kredit))
                ->assertSee('Rp' . self::rp($debit))
                ->assertSeeIn('.metric-card.card-blue .metric-value', (string) $jumlah)
                ->assertSee('1001-2026-00001')
                ->assertSee('1001-2026-00003');
        });
    }
}
