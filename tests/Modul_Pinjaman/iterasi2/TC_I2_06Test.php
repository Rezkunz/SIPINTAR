<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-06 (Positive) - Cetak kartu piutang individu
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Permintaan cetak laporan.
 * Expected : Kartu piutang cetak memuat seluruh jadwal (10 baris), status tiap angsuran, metode, dan sisa piutang yang sama dengan layar.
 */
class TC_I2_06Test extends Iterasi2DuskTestCase
{
    public function test_cetak_kartu_piutang(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            $this->loginSebagai($browser, self::BBM)
                ->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('4.000.000');

            $href = $browser->attribute('#btnCetakKartu', 'href');
            $browser->visit($href)
                ->assertSee('KARTU PIUTANG INDIVIDU')
                ->assertSee($this->namaNasabah(self::PEGAWAI))
                ->assertSeeIn('#sisaKartuCetak', 'Rp 4.000.000')
                ->screenshot('TC-I2-06');

            $status = $this->teksSemua($browser, '#tabelJadwalCetak tbody td:last-child');
            $this->assertCount(10, $status);
            $this->assertSame(2, count(array_keys($status, 'Lunas')));
            $this->assertSame(8, count(array_keys($status, 'Belum Bayar')));
        });
    }
}
