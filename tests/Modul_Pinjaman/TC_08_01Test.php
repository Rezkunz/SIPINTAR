<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-08 | TC-08-01 (Positive) - Pengajuan pinjaman valid
 * Aktor    : Nasabah (Pegawai) - pegawai@sipintar.test
 * Expected : Pengajuan tersimpan dengan status 'Menunggu Penilaian'.
 */
class TC_08_01Test extends SipintarDuskTestCase
{
    public function test_pengajuan_pinjaman_valid(): void
    {
        $this->browse(function (Browser $browser) {
            // 1-2. Login pegawai, buka menu Pinjaman > Ajukan
            $this->loginSebagai($browser, self::PEGAWAI)
                ->clickLink('Ajukan Pinjaman')
                ->waitForLocation('/nasabah/pinjaman/ajukan');

            // 3. Nominal Rp5.000.000, tenor 10 bulan (4. form tidak memiliki field keperluan)
            $this->isi($browser, '#nominal_pinjaman', 5000000);
            $this->isi($browser, '#tenor', 10);
            $browser->assertSeeIn('#simTotalBulan', '600.000');

            // 5. Klik Ajukan + konfirmasi
            $browser->click('button[onclick="konfirmasiKirimPinjaman()"]');
            $this->setujuiKonfirmasi($browser)->waitForLocation('/nasabah/pinjaman', 15);

            $this->assertStringContainsString('berhasil diajukan', $this->pesanPopup($browser));
            $browser->assertSee('Menunggu Penilaian Kelayakan')->screenshot('TC-08-01');
        });

        $this->assertDatabaseHas('tblPINJAMAN', [
            'nasabah_id'       => $this->nasabahId(self::PEGAWAI),
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'status_pinjaman'  => 'diajukan',
        ]);
    }
}
