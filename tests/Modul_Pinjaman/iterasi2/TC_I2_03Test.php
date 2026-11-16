<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-03 (Positive) - Mengubah metode pembayaran yang salah pilih
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Saat UAT metode terpilih "pembayaran langsung" padahal seharusnya potong gaji, dan tidak bisa diubah.
 * Expected : Metode berubah menjadi Potong Gaji, tercatat di log audit, dan angsuran bulan ini muncul di Rencana Potongan.
 */
class TC_I2_03Test extends Iterasi2DuskTestCase
{
    public function test_ubah_metode_tunai_menjadi_potong_gaji(): void
    {
        // Pinjaman sudah cair dengan metode keliru (tunai), angsuran ke-1 jatuh tempo bulan ini
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'tunai');
        $jadwal1 = $this->jadwal($pinjamanId, 1);

        $this->browse(function (Browser $browser) use ($pinjamanId, $jadwal1) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/pinjaman"]')
                ->waitForLocation('/bbm/pinjaman')
                ->click("#btn-ubah-metode-{$pinjamanId}")
                ->waitFor('#formUbahMetode #metodeAlasan')
                ->assertRadioSelected('metode_bayar', 'potong_gaji');
            $this->isi($browser, '#metodeAlasan', 'Salah pilih metode saat pengajuan (UAT)');
            $browser->screenshot('TC-I2-03-form')->press('Simpan Metode');

            $this->assertStringContainsString('diubah menjadi Potong Gaji', $this->pesanPopup($browser));
            $browser->screenshot('TC-I2-03');

            // Angsuran bulan ini kini dapat disusun dalam Rencana Potongan
            $browser->visit('/bbm/angsuran/potongan')
                ->assertPresent("input[name=\"jadwal_ids[]\"][value=\"{$jadwal1->jadwal_angsuran_id}\"]")
                ->screenshot('TC-I2-03-rencana');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'metode_bayar' => 'potong_gaji']);
        $this->assertDatabaseHas('tblAUDIT_LOG', ['aksi' => 'UBAH_METODE_BAYAR', 'entitas_id' => $pinjamanId]);
    }
}
