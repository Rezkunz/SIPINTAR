<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-12 | TC-13-02 (Positive) - Mencatat kas keluar / kas bank
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Mutasi tercatat pada jenis kas yang benar; saldo terbarui.
 */
class TC_13_02Test extends SipintarDuskTestCase
{
    public function test_mencatat_kas_keluar_dan_kas_bank(): void
    {
        $kasAwal = $this->saldoKas('kas');
        $bankAwal = $this->saldoKas('bank');

        $this->browse(function (Browser $browser) {
            // Kas Keluar (kas tunai)
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/kas/harian"]')
                ->waitForLocation('/kas/harian')
                ->click('button[onclick="bukaModalKasManual()"]')
                ->waitFor('#formMutasiKas #nominal')
                ->click('#pill-keluar');
            $this->isi($browser, '#nominal', 100000);
            $this->isi($browser, '#uraian', 'Pembelian ATK loket (uji)');
            $browser->click('button[onclick="konfirmasiSimpanKas()"]');
            $this->setujuiKonfirmasi($browser);
            $this->assertStringContainsString('berhasil dicatat', $this->pesanPopup($browser));
            $browser->screenshot('TC-13-02-kas-keluar');

            // Kas Bank (masuk)
            $this->tutupPopup($browser);
            $browser->click('a[href$="/kas/bank"]')
                ->waitForLocation('/kas/bank')
                ->click('button[onclick="bukaModalBankManual()"]')
                ->waitFor('#formMutasiBank #nominal_bank')
                ->click('#pill-bank-masuk');
            $this->isi($browser, '#nominal_bank', 1000000);
            $this->isi($browser, '#uraian_bank', 'Setoran kas tunai ke rekening bank (uji)');
            $browser->click('button[onclick="konfirmasiSimpanBank()"]');
            $this->setujuiKonfirmasi($browser);
            $this->assertStringContainsString('berhasil dicatat', $this->pesanPopup($browser));
            $browser->screenshot('TC-13-02');
        });

        $this->assertDatabaseHas('tblMUTASI_KAS', ['kas_id' => 1, 'arah' => 'keluar', 'nominal' => 100000]);
        $this->assertDatabaseHas('tblMUTASI_KAS', ['kas_id' => 2, 'arah' => 'masuk', 'nominal' => 1000000]);
        $this->assertEquals($kasAwal - 100000, $this->saldoKas('kas'));
        $this->assertEquals($bankAwal + 1000000, $this->saldoKas('bank'));
    }
}
