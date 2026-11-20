<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D05 (Positive) - Angsuran yang dicatat BBM tampil di Buku Kas Bendahara & Angsuran
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Temuan   : 2 - Laporan perlu sama dengan kartu piutang.
 * Expected : Angsuran ke-1 (pokok + jasa) tampil sesuai nominal; sisa piutang sama dengan Kartu Piutang.
 */
class TC_I2_D05Test extends Iterasi2DashboardTestCase
{
    public function test_angsuran_bbm_tampil_di_buku_kas_bendahara(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'tunai');
        $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 1)->first();

        $this->browse(function (Browser $browser) use ($pinjamanId, $jadwal) {
            // 1-2. BBM: bayar angsuran ke-1 secara langsung
            $this->loginSebagai($browser, self::BBM);
            $this->assertStringContainsString('berhasil dicatat', $this->bayarLangsung($browser, $jadwal->jadwal_angsuran_id));
            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")->assertSee('4.500.000');

            // 3-4. Manajer: Laporan Pinjaman - Buku Kas Bendahara & Angsuran
            $this->loginSebagai($browser, self::MANAJER)
                ->visit('/laporan/pinjaman')
                ->click('a.lap-tab[href*="/laporan/pinjaman/kas-bendahara"]')
                ->waitForLocation('/laporan/pinjaman/kas-bendahara')
                ->assertSee($this->rp($jadwal->pokok))
                ->assertSee($this->rp($jadwal->bunga))
                ->screenshot('TC-I2-D05');

            $browser->visit('/laporan/pinjaman');
            $this->assertSame('Rp4.500.000', $this->metrik($browser, 'Total Sisa Pokok (Outstanding)'));
        });
    }
}
