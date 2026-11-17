<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-12 (Positive) - Menyerahkan data tunjangan pegawai (FR-16)
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Data tunjangan bulan berjalan tersimpan, tunjangan bersih terhitung, dan dapat dilihat
 *            Bendahara Bank Mini saat penilaian kelayakan (sumber data + penghasilan terbaru).
 */
class TC_I2_12Test extends Iterasi2DuskTestCase
{
    public function test_bendahara_sekolah_menyerahkan_data_tunjangan(): void
    {
        $nasabahId = $this->nasabahId(self::PEGAWAI);
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');
        $bulan = now()->translatedFormat('F Y');

        $this->browse(function (Browser $browser) use ($nasabahId, $pinjamanId, $bulan) {
            // 1-2. Login Bendahara Sekolah, buka menu Data Tunjangan
            $this->loginSebagai($browser, self::BND_SKL)
                ->click('a[href$="/bendahara/tunjangan"]')
                ->waitForLocation('/bendahara/tunjangan');

            // 3. Input tunjangan pegawai bulan berjalan
            $this->isi($browser, "#gaji-{$nasabahId}", 5000000);
            $this->isi($browser, "#tunjangan-{$nasabahId}", 2000000);
            $this->isi($browser, "#potongan-{$nasabahId}", 250000);
            $this->isi($browser, "#ket-{$nasabahId}", 'Tunjangan sertifikasi');
            $browser->assertSeeIn("#bersih-{$nasabahId}", 'Rp 1.750.000');

            // 4. Simpan
            $browser->press('Simpan Data Tunjangan');
            $this->assertStringContainsString('berhasil disimpan', $this->pesanPopup($browser));
            $browser->screenshot('TC-I2-12-sekolah');

            // Dilihat Bendahara Bank Mini saat penilaian
            $this->loginSebagai($browser, self::BBM)
                ->visit('/bbm/pinjaman/kelayakan')
                ->assertSeeIn('#tableKelayakan', "Data tunjangan {$bulan}")
                ->assertSeeIn('#tableKelayakan', 'Rp 7.000.000') // gaji + tunjangan terbaru
                ->click("button[onclick^=\"openAssessmentModal({$pinjamanId},\"]")
                ->waitFor('#assessmentModal #modalPenghasilan')
                ->assertInputValue('#modalPenghasilan', '7000000')
                ->assertSeeIn('#modalSumberTunjangan', 'Potongan lain: Rp 250.000')
                ->screenshot('TC-I2-12');
        });

        $this->assertDatabaseHas('tblDATA_TUNJANGAN', [
            'nasabah_id' => $nasabahId, 'tahun' => (int) date('Y'), 'bulan' => (int) date('n'),
            'gaji_pokok' => 5000000, 'tunjangan' => 2000000, 'potongan_lain' => 250000,
        ]);
        $this->assertDatabaseHas('tblNASABAH', ['nasabah_id' => $nasabahId, 'gaji_pokok' => 5000000, 'tunjangan' => 2000000]);
        $this->assertDatabaseHas('tblAUDIT_LOG', ['aksi' => 'SIMPAN_DATA_TUNJANGAN']);
    }

    public function test_penilaian_tanpa_data_tunjangan_memberi_peringatan(): void
    {
        $this->buatPinjaman(5000000, 10, 'diajukan');

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BBM)
                ->visit('/bbm/pinjaman/kelayakan')
                ->assertSeeIn('#tableKelayakan', 'Belum ada data tunjangan dari Bendahara Sekolah')
                ->screenshot('TC-I2-12-tanpa-data');
        });
    }
}
