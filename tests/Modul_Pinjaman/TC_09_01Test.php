<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-09 | TC-09-01 (Positive) - Menilai pengajuan layak
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Hasil penilaian terdokumentasi; status 'Disetujui'.
 */
class TC_09_01Test extends SipintarDuskTestCase
{
    public function test_menilai_pengajuan_layak(): void
    {
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/pinjaman/kelayakan"]')
                ->waitForLocation('/bbm/pinjaman/kelayakan')
                ->click("button[onclick^=\"openAssessmentModal({$pinjamanId},\"]")
                ->waitFor('#assessmentModal #modalPenghasilan')
                // 4. Data tunjangan peminjam (gaji 4.500.000 + tunjangan 1.500.000) tampil di form penilaian
                ->assertInputValue('#modalPenghasilan', '6000000')
                ->radio('hasil_kelayakan', 'layak');
            $this->isi($browser, '#catatan', 'Rasio cicilan aman (< 30%). Data tunjangan terverifikasi.');

            // 5. Simpan
            $browser->press('Simpan Keputusan');
            $this->assertStringContainsString('LAYAK', $this->pesanPopup($browser));
            $browser->screenshot('TC-09-01');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'status_pinjaman' => 'layak']);
        $this->assertDatabaseHas('tblKELAYAKAN', ['pinjaman_id' => $pinjamanId, 'hasil_kelayakan' => 'layak', 'penghasilan' => 6000000]);
    }
}
