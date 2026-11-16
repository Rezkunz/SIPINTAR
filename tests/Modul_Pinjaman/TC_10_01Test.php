<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-10 | TC-10-01 (Positive) - Menyusun rencana potongan
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Daftar rencana potongan per pegawai terbentuk sesuai jadwal angsuran.
 */
class TC_10_01Test extends SipintarDuskTestCase
{
    public function test_menyusun_rencana_potongan(): void
    {
        // Pinjaman aktif (potong gaji) dengan angsuran ke-1 jatuh tempo bulan ini
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $jadwal1 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 1)->first();

        $this->browse(function (Browser $browser) {
            // 2. Buka menu Rencana Potongan
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/angsuran/potongan"]')
                ->waitForLocation('/bbm/angsuran/potongan')
                // 3. Pilih bulan
                ->select('bulan', (string) date('n'))
                ->waitFor('input[name="jadwal_ids[]"]')
                ->assertSee($this->namaNasabah(self::PEGAWAI))
                ->assertSee('600.000')
                // 4. Susun / terbitkan rencana
                ->press('Terbitkan Rencana ke Bendahara Sekolah')
                ->waitForDialog()
                ->acceptDialog();

            $this->assertStringContainsString('berhasil disusun', $this->pesanPopup($browser));
            $browser->screenshot('TC-10-01');
        });

        $rencana = DB::table('tblRENCANA_POTONGAN')->first();
        $this->assertNotNull($rencana);
        $this->assertSame('dikirim', $rencana->status_potongan);
        $this->assertEquals(600000, (float) $rencana->total_potongan);
        $this->assertDatabaseHas('tblDETAIL_POTONGAN', [
            'rencana_potongan_id' => $rencana->rencana_potongan_id,
            'jadwal_angsuran_id'  => $jadwal1->jadwal_angsuran_id,
            'nominal_rencana'     => 600000,
        ]);
    }
}
