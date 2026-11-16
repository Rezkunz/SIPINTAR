<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-08 | TC-08-02 (Negative) - Nominal kosong / 0
 * Aktor    : Nasabah (Pegawai) - pegawai@sipintar.test
 * Expected : Validasi nominal muncul; pengajuan tidak tersimpan.
 */
class TC_08_02Test extends SipintarDuskTestCase
{
    public function test_nominal_nol_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::PEGAWAI)->visit('/nasabah/pinjaman/ajukan');

            $this->isi($browser, '#nominal_pinjaman', 0);
            $browser->click('button[onclick="konfirmasiKirimPinjaman()"]')
                ->waitForDialog()
                ->assertDialogOpened('Nominal pinjaman minimal adalah Rp 500.000')
                ->acceptDialog()
                ->assertPathIs('/nasabah/pinjaman/ajukan')
                ->screenshot('TC-08-02');
        });

        $this->assertSame(0, DB::table('tblPINJAMAN')->count());
    }
}
