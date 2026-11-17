<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-11 (Positive & Negative) - Keperluan pinjaman (UC-08)
 * Aktor    : Nasabah (Pegawai) - pegawai@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Pengajuan tanpa keperluan ditolak validasi; keperluan tersimpan dan tampil di
 *            antrean & form penilaian kelayakan sebagai bahan pertimbangan Bendahara Bank Mini.
 */
class TC_I2_11Test extends Iterasi2DuskTestCase
{
    public function test_keperluan_pinjaman_tersimpan_dan_tampil_saat_penilaian(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::PEGAWAI)->visit('/nasabah/pinjaman/ajukan');
            $this->isi($browser, '#nominal_pinjaman', 5000000);
            $this->isi($browser, '#tenor', 10);

            // Negatif: keperluan kosong ditolak
            $this->isi($browser, '#keperluan', '');
            $browser->click('button[onclick="konfirmasiKirimPinjaman()"]')
                ->waitForDialog()
                ->assertDialogOpened('Keperluan pinjaman wajib diisi (minimal 5 karakter).')
                ->acceptDialog();
            $this->assertSame(0, DB::table('tblPINJAMAN')->count());

            // Positif: pilih keperluan cepat lalu kirim
            $browser->press('Biaya kesehatan')
                ->assertInputValue('#keperluan', 'Biaya kesehatan')
                ->screenshot('TC-I2-11-form')
                ->click('button[onclick="konfirmasiKirimPinjaman()"]');
            $this->setujuiKonfirmasi($browser)->waitForLocation('/nasabah/pinjaman', 15);
            $browser->assertSee('Biaya kesehatan');

            // Bendahara Bank Mini melihat keperluan saat menilai
            $pinjamanId = DB::table('tblPINJAMAN')->value('pinjaman_id');
            $this->loginSebagai($browser, self::BBM)
                ->visit('/bbm/pinjaman/kelayakan')
                ->assertSeeIn('#tableKelayakan', 'Keperluan: Biaya kesehatan')
                ->click("button[onclick^=\"openAssessmentModal({$pinjamanId},\"]")
                ->waitFor('#assessmentModal #modalPenghasilan')
                ->assertSeeIn('#modalKeperluan', 'Biaya kesehatan')
                ->screenshot('TC-I2-11');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['nasabah_id' => $this->nasabahId(self::PEGAWAI), 'keperluan' => 'Biaya kesehatan']);
    }
}
