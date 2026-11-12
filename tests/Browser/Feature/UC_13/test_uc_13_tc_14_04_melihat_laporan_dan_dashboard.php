<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-04 Melihat laporan dan dashboard
 * Test Case: Laporan pinjaman, rekap piutang, dan kas
 */
class test_uc_13_tc_14_04_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-04 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_04_melihat_laporan_dan_dashboard(): void
    {
        // Dipetakan ke menu Kartu Piutang dan Kas Tunai milik Bendahara Bank Mini
        $sisaPiutang = $this->hitungSisaPiutangManual();
        $kasTunai = (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo');

        $this->browse(function (Browser $browser) use ($sisaPiutang, $kasTunai) {
            $this->loginForm($browser, self::AKUN['bendahara'])
                ->clickLink('Kartu Piutang')
                ->assertPathBeginsWith('/bbm/kartu');

            // Rekap piutang = jumlah "Sisa Pokok" di seluruh kartu piutang debitur
            $totalKartu = (float) $browser->script(
                "return [...document.querySelectorAll('div')].filter(d => d.children.length === 0 && d.innerText.trim().toUpperCase() === 'SISA POKOK')"
                . ".map(d => d.nextElementSibling ? parseInt(d.nextElementSibling.innerText.replace(/[^0-9]/g, '') || '0', 10) : 0)"
                . ".reduce((a, b) => a + b, 0);"
            )[0];
            $this->assertEquals($sisaPiutang, $totalKartu, 'Rekap sisa piutang di Kartu Piutang tidak sama dengan perhitungan manual');

            $browser->clickLink('Kas Tunai (Cash)')
                ->assertPathIs('/kas/harian')
                ->assertSee('POSISI SALDO KAS TUNAI')
                ->assertSee(self::rp($kasTunai));
        });
    }
}
