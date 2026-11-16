<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-11 | TC-11-01 (Positive) - Pembayaran angsuran tunai
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Angsuran tercatat, sisa piutang berkurang, kartu piutang terbarui.
 */
class TC_11_01Test extends SipintarDuskTestCase
{
    public function test_pembayaran_angsuran_tunai(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'tunai');
        $jadwal1 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 1)->first();
        $kasAwal = $this->saldoKas('kas');

        $this->browse(function (Browser $browser) use ($jadwal1, $pinjamanId) {
            // 2-3. Menu Angsuran, pilih peminjam & angsuran ke-1
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/angsuran/langsung"]')
                ->waitForLocation('/bbm/angsuran/langsung')
                ->click("button[onclick^=\"bukaModalBayar({$jadwal1->jadwal_angsuran_id},\"]")
                ->waitFor('#modalBayarKasir #formBayarTunai')
                // 4. Nominal sesuai jadwal (pokok 500.000 + jasa 100.000)
                ->assertSeeIn('#dispModalTotal', '600.000')
                // 5. Simpan
                ->press('Konfirmasi Terima Kas');
            $this->assertStringContainsString('berhasil dicatat', $this->pesanPopup($browser));

            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('4.500.000') // sisa piutang
                ->screenshot('TC-11-01');
        });

        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $jadwal1->jadwal_angsuran_id, 'status_bayar' => 'lunas']);
        $this->assertDatabaseHas('tblANGSURAN', ['jadwal_angsuran_id' => $jadwal1->jadwal_angsuran_id, 'bayar_pokok' => 500000, 'bayar_bunga' => 100000]);
        $this->assertEquals($kasAwal + 600000, $this->saldoKas('kas'));
    }
}
