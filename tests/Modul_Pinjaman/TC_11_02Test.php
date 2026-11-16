<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-11 | TC-11-02 (Negative) - Nominal melebihi sisa piutang
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Sistem menolak dengan pesan melebihi sisa piutang.
 */
class TC_11_02Test extends SipintarDuskTestCase
{
    public function test_nominal_melebihi_sisa_piutang_ditolak(): void
    {
        // Pinjaman Rp1.000.000 / 2 bulan, angsuran ke-1 lunas -> sisa piutang Rp500.000
        $pinjamanId = $this->buatPinjamanCair(1000000, 2, 'tunai', 1);
        $jadwal2 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 2)->first();

        $this->browse(function (Browser $browser) use ($jadwal2) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/angsuran/langsung"]')
                ->waitForLocation('/bbm/angsuran/langsung')
                ->click("button[onclick^=\"bukaModalBayar({$jadwal2->jadwal_angsuran_id},\"]")
                ->waitFor('#modalBayarKasir #formBayarTunai');

            // 2. Isi nominal Rp1.000.000 (form hanya punya nilai pokok tersembunyi -> diisi langsung)
            $this->isi($browser, '#form_bayar_pokok', 1000000);

            // 3. Simpan
            $browser->press('Konfirmasi Terima Kas');
            $pesan = $this->pesanPopup($browser);
            $browser->screenshot('TC-11-02');

            $this->assertStringContainsString('melebihi sisa piutang', $pesan);
        });

        $this->assertSame(1, DB::table('tblANGSURAN')->count(), 'Pembayaran melebihi sisa piutang tidak boleh tercatat');
    }
}
