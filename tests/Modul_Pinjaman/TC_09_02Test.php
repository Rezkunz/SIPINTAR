<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-09 | TC-09-02 (Negative) - Menilai pengajuan tidak layak
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Status 'Ditolak'; tombol pencairan tidak tersedia.
 */
class TC_09_02Test extends SipintarDuskTestCase
{
    public function test_menilai_pengajuan_tidak_layak(): void
    {
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/pinjaman/kelayakan"]')
                ->waitForLocation('/bbm/pinjaman/kelayakan')
                ->click("button[onclick^=\"openAssessmentModal({$pinjamanId},\"]")
                ->waitFor('#assessmentModal #modalPenghasilan')
                ->click('#radioTidakLayak');
            $this->isi($browser, '#catatan', 'Ditolak: beban cicilan melebihi toleransi komite kredit.');
            $browser->press('Simpan Keputusan');

            $this->assertStringContainsString('DITOLAK', $this->pesanPopup($browser));

            // Tombol pencairan tidak tersedia untuk pinjaman yang ditolak
            $browser->visit('/bbm/pinjaman/cair')
                ->assertMissing("button[onclick^=\"bukaModalPencairan({$pinjamanId},\"]")
                ->screenshot('TC-09-02');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'status_pinjaman' => 'ditolak']);
        $this->assertDatabaseHas('tblKELAYAKAN', ['pinjaman_id' => $pinjamanId, 'hasil_kelayakan' => 'tidak_layak']);
    }
}
