<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-11 | TC-11-03 (Positive) - Pelunasan angsuran terakhir
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Sisa piutang Rp0 dan status pinjaman 'Lunas'.
 */
class TC_11_03Test extends SipintarDuskTestCase
{
    public function test_pelunasan_angsuran_terakhir(): void
    {
        // Tersisa satu angsuran (ke-2 dari 2)
        $pinjamanId = $this->buatPinjamanCair(1000000, 2, 'tunai', 1);
        $jadwal2 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 2)->first();

        $this->browse(function (Browser $browser) use ($jadwal2, $pinjamanId) {
            // 2. Catat angsuran terakhir
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/angsuran/langsung"]')
                ->waitForLocation('/bbm/angsuran/langsung')
                ->click("button[onclick^=\"bukaModalBayar({$jadwal2->jadwal_angsuran_id},\"]")
                ->waitFor('#modalBayarKasir #formBayarTunai')
                ->press('Konfirmasi Terima Kas');
            $this->assertStringContainsString('berhasil dicatat', $this->pesanPopup($browser));

            // 3. Buka status pinjaman
            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('Lunas Sempurna')
                ->screenshot('TC-11-03');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'status_pinjaman' => 'lunas']);
        $terbayar = (float) DB::table('tblANGSURAN as a')
            ->join('tblJADWAL_ANGSURAN as j', 'a.jadwal_angsuran_id', '=', 'j.jadwal_angsuran_id')
            ->where('j.pinjaman_id', $pinjamanId)->sum('a.bayar_pokok');
        $this->assertEquals(0, 1000000 - $terbayar, 'Sisa piutang harus Rp0');
    }
}
