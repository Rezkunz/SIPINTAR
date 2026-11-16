<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-04 (Negative) - Ubah metode saat angsuran masih dalam paket potongan
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Perubahan ditolak dengan pesan paket potongan masih diproses; metode tetap Potong Gaji.
 */
class TC_I2_04Test extends Iterasi2DuskTestCase
{
    public function test_ubah_metode_ditolak_saat_paket_potongan_aktif(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $this->buatRencanaPotongan($pinjamanId);

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            $this->loginSebagai($browser, self::BBM)
                ->visit('/bbm/pinjaman')
                ->click("#btn-ubah-metode-{$pinjamanId}")
                ->waitFor('#formUbahMetode #metodeAlasan')
                ->assertRadioSelected('metode_bayar', 'tunai');
            $this->isi($browser, '#metodeAlasan', 'Peminjam ingin bayar langsung');
            $browser->press('Simpan Metode');

            $pesan = $this->pesanPopup($browser);
            $browser->screenshot('TC-I2-04');
            $this->assertStringContainsString('Metode belum dapat diubah', $pesan);
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'metode_bayar' => 'potong_gaji']);
    }
}
