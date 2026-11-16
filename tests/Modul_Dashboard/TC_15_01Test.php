<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-14 | TC-15-01 (Positive) - Menutup periode bulanan
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Periode berstatus 'Ditutup' dan tercatat siapa serta kapan menutup.
 */
class TC_15_01Test extends DashboardDuskTestCase
{
    public function test_menutup_periode_bulanan(): void
    {
        // Periode bulan lalu masih terbuka dan seluruh transaksinya sudah sesuai
        $bulanLalu = now()->subMonthNoOverflow()->startOfMonth();
        $periodeId = $this->periodeId($bulanLalu->toDateString());

        $this->browse(function (Browser $browser) use ($bulanLalu) {
            // 2-3. Menu Tutup Periode, pilih bulan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/periode/tutup"]')
                ->waitForLocation('/periode/tutup')
                ->click("a.periode-item[href*=\"tahun={$bulanLalu->year}&bulan={$bulanLalu->month}\"]")
                ->waitFor('input[name="konfirmasi"]');

            // 4. Tutup Periode + konfirmasi
            $this->isi($browser, '#catatan_tutup', 'Kas fisik sudah dihitung (uji UAT)');
            $browser->check('konfirmasi')
                ->press('Tutup Periode')
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('berhasil ditutup')
                ->assertSee('Manajer Uji')
                ->screenshot('TC-15-01');
        });

        $periode = DB::table('tblPERIODE')->where('periode_id', $periodeId)->first();
        $this->assertSame('tutup', $periode->status_periode);
        $this->assertNotNull($periode->tgl_tutup, 'Waktu penutupan tercatat');
        $this->assertSame($this->userId(self::MANAJER), (int) $periode->user_id, 'Penutup periode tercatat');
    }
}
