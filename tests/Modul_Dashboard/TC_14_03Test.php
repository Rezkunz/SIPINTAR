<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-03 (Positive) - Laporan pinjaman, rekap piutang, dan kas
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Rekap piutang dan saldo kas sama dengan catatan manual.
 */
class TC_14_03Test extends DashboardDuskTestCase
{
    public function test_laporan_pinjaman_rekap_piutang_dan_kas(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);
        $this->transaksiTabungan(self::SISWA, 275000);

        // Catatan manual
        $sisaPiutang = 5000000 - (float) DB::table('tblANGSURAN')->sum('bayar_pokok'); // 4.000.000
        $saldoKas    = $this->saldoKas('kas');                                           // 50.275.000

        $this->browse(function (Browser $browser) use ($pinjamanId, $sisaPiutang, $saldoKas) {
            // 2. Rekap piutang (Kartu Piutang)
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/kartu"]')
                ->waitForLocation('/bbm/kartu')
                ->assertSee('Rp ' . $this->rp($sisaPiutang))
                ->screenshot('TC-14-03-rekap-piutang');

            // 4. Bandingkan rekap dengan kartu piutang individu
            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee($this->rp($sisaPiutang));

            // 3. Kas: pilih bulan berjalan
            $browser->click('a[href$="/kas/harian"]')
                ->waitForLocation('/kas/harian')
                ->visit('/kas/harian?filter_type=bulanan&bulan=' . date('n') . '&tahun=' . date('Y'))
                ->screenshot('TC-14-03');
            $this->assertContains('Rp' . $this->rp($saldoKas), $this->nilaiMetrik($browser), 'Saldo kas tunai');
        });
    }
}
