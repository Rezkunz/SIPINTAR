<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-09 | TC-09-04 (Positive) - Mencairkan pinjaman
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Status 'Dicairkan'; jadwal angsuran 10 bulan terbentuk dengan jasa 2% per bulan.
 */
class TC_09_04Test extends SipintarDuskTestCase
{
    public function test_mencairkan_pinjaman(): void
    {
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');
        $this->nilaiLayak($pinjamanId);

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            // 2. Klik Cairkan
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/pinjaman/cair"]')
                ->waitForLocation('/bbm/pinjaman/cair')
                ->click("button[onclick^=\"bukaModalPencairan({$pinjamanId},\"]")
                ->waitFor('#modalPencairanDana #formEksekusiPencairan')
                // 3. Tanggal pencairan otomatis = hari ini (field tersembunyi)
                ->assertInputValue('#formEksekusiPencairan input[name="tgl_cair"]', date('Y-m-d'))
                // 4. Konfirmasi
                ->press('Ya, Cairkan Dana Sekarang');
            $this->setujuiKonfirmasi($browser);
            $this->assertStringContainsString('berhasil dicairkan', $this->pesanPopup($browser));

            // 5. Buka jadwal angsuran (kartu piutang)
            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('Rp 500.000')
                ->assertSee('Rp 100.000')
                ->assertSee('Rp 600.000')
                ->screenshot('TC-09-04');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'status_pinjaman' => 'cair']);
        $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->orderBy('angsuran_ke')->get();
        $this->assertCount(10, $jadwal);
        foreach ($jadwal as $baris) {
            $this->assertEquals(500000, (float) $baris->pokok);
            $this->assertEquals(100000, (float) $baris->bunga); // 2% x 5.000.000
        }
    }
}
