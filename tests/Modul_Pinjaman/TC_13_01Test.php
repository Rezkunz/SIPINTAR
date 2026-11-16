<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-12 | TC-13-01 (Positive) - Mencatat kas masuk
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Mutasi tercatat dan saldo kas bertambah.
 */
class TC_13_01Test extends SipintarDuskTestCase
{
    public function test_mencatat_kas_masuk(): void
    {
        $saldoAwal = $this->saldoKas('kas');

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/kas/harian"]')
                ->waitForLocation('/kas/harian')
                ->click('button[onclick="bukaModalKasManual()"]')
                ->waitFor('#formMutasiKas #nominal')
                ->click('#pill-masuk');
            $this->isi($browser, '#nominal', 250000);
            $this->isi($browser, '#uraian', 'Penerimaan iuran koperasi (uji)');
            $browser->click('button[onclick="konfirmasiSimpanKas()"]');
            $this->setujuiKonfirmasi($browser);
            $this->assertStringContainsString('berhasil dicatat', $this->pesanPopup($browser));
            $browser->assertSee('Penerimaan iuran koperasi (uji)')->screenshot('TC-13-01');
        });

        $this->assertDatabaseHas('tblMUTASI_KAS', ['kas_id' => 1, 'arah' => 'masuk', 'nominal' => 250000, 'uraian' => 'Penerimaan iuran koperasi (uji)']);
        $this->assertEquals($saldoAwal + 250000, $this->saldoKas('kas'));
    }
}
