<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D04 (Positive) - Transaksi Teller masuk ke Rekap Harian Manajer tanpa input ulang
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Teller - teller@sipintar.test
 * Temuan   : 2 - Laporan perlu sama dengan rekap harian Teller.
 * Expected : Setoran Rp50.000 & penarikan Rp20.000 hari ini tampil di Rekap Harian; total sama dengan Log Transaksi.
 */
class TC_I2_D04Test extends Iterasi2DashboardTestCase
{
    public function test_transaksi_teller_masuk_rekap_harian_manajer(): void
    {
        // Saldo awal rekening siswa Rp100.000 (awal bulan)
        $this->transaksiTabungan(self::SISWA, 100000, 0, now()->startOfMonth()->setTime(8, 0)->toDateTimeString());
        $rek = $this->rekening(self::SISWA);

        $this->browse(function (Browser $browser) use ($rek) {
            // 1-2. Teller: setoran Rp50.000 dan penarikan Rp20.000
            $this->loginSebagai($browser, self::TELLER)->visit('/tabungan/setoran')->select('no_rek', $rek->no_rek);
            $this->isi($browser, '#nominal', 50000);
            $browser->press('Simpan Setoran')->pause(1500);

            $browser->visit('/tabungan/penarikan')->select('no_rek', $rek->no_rek);
            $this->isi($browser, '#nominal', 20000);
            $browser->press('Simpan Penarikan')->pause(1500);

            // 3. Log Transaksi hari ini
            $kreditHariIni = (float) DB::table('tblTRANSAKSI')->whereDate('tgl_transaksi', now()->toDateString())->sum('kredit');
            $browser->visit('/tabungan/log-transaksi');
            $this->assertSame('Rp' . $this->rp($kreditHariIni), $this->metrik($browser, 'Total Kredit'));
            $this->assertSame('Rp20.000', $this->metrik($browser, 'Total Debit'));

            // 4-5. Manajer: Laporan Tabungan - Rekap Harian kategori Siswa, bulan berjalan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/laporan/tabungan"]')
                ->waitForLocation('/laporan/tabungan')
                ->visit('/laporan/tabungan?kategori=siswa&bulan=' . date('n') . '&tahun=' . date('Y'))
                ->screenshot('TC-I2-D04');
            $this->assertSame('Rp150.000', $this->metrik($browser, 'Total Setoran'));
            $this->assertSame('Rp20.000', $this->metrik($browser, 'Total Penarikan'));
        });

        $this->assertEquals(130000, (float) DB::table('tblREKENING')->where('rekening_id', $rek->rekening_id)->value('saldo'));
    }
}
