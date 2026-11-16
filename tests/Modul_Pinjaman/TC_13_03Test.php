<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-12 | TC-13-03 (Negative) - Nominal atau keterangan kosong
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Validasi muncul; mutasi tidak tersimpan.
 */
class TC_13_03Test extends SipintarDuskTestCase
{
    public function test_nominal_kosong_ditolak(): void
    {
        $saldoAwal = $this->saldoKas('kas');

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BBM)
                ->visit('/kas/harian')
                ->click('button[onclick="bukaModalKasManual()"]')
                ->waitFor('#formMutasiKas #nominal');

            // 2. Kosongkan nominal (keterangan diisi)
            $this->isi($browser, '#nominal', '');
            $this->isi($browser, '#uraian', 'Uji validasi nominal kosong');

            // 3. Simpan
            $browser->click('button[onclick="konfirmasiSimpanKas()"]')
                ->waitForDialog()
                ->assertDialogOpened('Nominal minimal adalah Rp 1.000')
                ->acceptDialog()
                ->screenshot('TC-13-03');
        });

        $this->assertSame(0, DB::table('tblMUTASI_KAS')->count());
        $this->assertEquals($saldoAwal, $this->saldoKas('kas'));
    }
}
