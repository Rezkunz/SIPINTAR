<?php

namespace Tests\Modul_Dashboard\iterasi1;

use Tests\Modul_Dashboard\DashboardDuskTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-14 | TC-15-02 (Negative) - Input transaksi pada periode tertutup
 * Aktor    : Teller - teller@sipintar.test
 * Expected : Sistem menolak karena periode terkunci.
 */
class TC_15_02Test extends DashboardDuskTestCase
{
    public function test_input_transaksi_periode_tertutup_ditolak(): void
    {
        // Periode bulan uji (bulan berjalan, tanggal setoran = hari ini) sudah ditutup
        DB::table('tblPERIODE')->where('tahun', (int) date('Y'))->where('bulan', (int) date('n'))
            ->update(['status_periode' => 'tutup', 'tgl_tutup' => now(), 'user_id' => $this->userId(self::MANAJER)]);
        $rek = $this->rekening(self::SISWA);

        $this->browse(function (Browser $browser) use ($rek) {
            // 2. Input setoran bertanggal di periode tertutup
            $this->loginSebagai($browser, self::TELLER)
                ->click('a[href$="/tabungan/setoran"]')
                ->waitForLocation('/tabungan/setoran')
                ->select('no_rek', $rek->no_rek);
            $this->isi($browser, '#nominal', 50000);
            $browser->press('Simpan Setoran')
                ->pause(1500)
                ->screenshot('TC-15-02')
                ->assertSee('terkunci');
        });

        $this->assertSame(0, DB::table('tblTRANSAKSI')->count(), 'Setoran tidak boleh tercatat');
        $this->assertEquals(0, (float) DB::table('tblREKENING')->where('rekening_id', $rek->rekening_id)->value('saldo'));
    }
}
