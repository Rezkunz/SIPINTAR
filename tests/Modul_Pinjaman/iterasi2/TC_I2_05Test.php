<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-05 (Positive) - Cetak bukti pembayaran angsuran
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : "Setiap transaksi itu bisa dicetak? Buktinya apa?"
 * Expected : Setelah pembayaran tersedia tombol Cetak Bukti; bukti memuat nomor, rincian pokok/jasa, total, dan sisa piutang.
 */
class TC_I2_05Test extends Iterasi2DuskTestCase
{
    public function test_cetak_bukti_pembayaran_angsuran(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'tunai');
        $jadwal1 = $this->jadwal($pinjamanId, 1);

        $this->browse(function (Browser $browser) use ($jadwal1) {
            $this->loginSebagai($browser, self::BBM);
            $this->assertStringContainsString('berhasil dicatat', $this->bayarLangsung($browser, $jadwal1->jadwal_angsuran_id));
            $this->tutupPopup($browser);

            $href = $browser->waitFor('#linkCetakBuktiBaru')->attribute('#linkCetakBuktiBaru', 'href');
            $browser->assertPresent('#tableRiwayatAngsuran a[href$="/bbm/angsuran/bukti/' . basename($href) . '"]')
                ->visit($href)
                ->assertSee('BUKTI PEMBAYARAN ANGSURAN')
                ->assertSee($this->namaNasabah(self::PEGAWAI))
                ->assertSeeIn('#nomorBukti', 'BA-')
                ->assertSeeIn('#totalBukti', 'Rp 600.000')
                ->assertSeeIn('#sisaBukti', 'Rp 4.500.000')
                ->assertSee('Pembayaran Langsung (Tunai)')
                ->screenshot('TC-I2-05');
        });
    }
}
