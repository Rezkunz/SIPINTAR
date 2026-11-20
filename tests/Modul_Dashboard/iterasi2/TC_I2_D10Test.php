<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D10 (Negative) - Transaksi pada periode yang sudah ditutup ditolak, laporan tidak berubah
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Bendahara Bank Mini - bendahara@sipintar.test
 * Temuan   : 2 - Laporan perlu tetap valid setelah periode ditutup.
 * Expected : Kas manual bertanggal periode tertutup ditolak (periode terkunci); total laporan periode itu tetap.
 */
class TC_I2_D10Test extends Iterasi2DashboardTestCase
{
    public function test_transaksi_periode_tertutup_ditolak_laporan_tetap(): void
    {
        $bulanLalu = now()->subMonthNoOverflow()->startOfMonth();
        $this->transaksiTabungan(self::SISWA, 100000, 0, $bulanLalu->copy()->setTime(8, 0)->toDateTimeString());
        $periodeId = $this->periodeId($bulanLalu->toDateString());
        $jumlahMutasi = DB::table('tblMUTASI_KAS')->count();
        $urlLaporan = '/laporan/tabungan/kas-harian?bulan=' . $bulanLalu->month . '&tahun=' . $bulanLalu->year;

        $this->browse(function (Browser $browser) use ($bulanLalu, $urlLaporan) {
            // 1-2. Manajer: catat total laporan bulan lalu
            $this->loginSebagai($browser, self::MANAJER)->visit($urlLaporan);
            $sebelum = $this->nilaiMetrik($browser);

            // 3. Tutup periode bulan lalu
            $browser->click('a[href$="/periode/tutup"]')
                ->waitForLocation('/periode/tutup')
                ->click("a.periode-item[href*=\"tahun={$bulanLalu->year}&bulan={$bulanLalu->month}\"]")
                ->waitFor('input[name="konfirmasi"]');
            $this->isi($browser, '#catatan_tutup', 'Tutup periode uji iterasi 2');
            $browser->check('konfirmasi')
                ->press('Tutup Periode')
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('berhasil ditutup');

            // 4. BBM: menu Kas Tunai (Cash) > Catat Mutasi Manual bertanggal bulan lalu
            $this->loginSebagai($browser, self::BBM)->visit('/kas/harian');
            $browser->script('bukaModalKasManual();');
            $this->isi($browser, '#tgl_mutasi', $bulanLalu->copy()->addDays(9)->toDateString());
            $this->isi($browser, '#nominal', 25000);
            $this->isi($browser, '#uraian', 'Uji kas manual periode tertutup');
            $browser->press('Simpan Mutasi Kas');
            $this->setujuiKonfirmasi($browser);
            $pesan = $this->pesanPopup($browser);
            $browser->screenshot('TC-I2-D10');
            $this->assertStringContainsString('terkunci', $pesan);

            // 5. Manajer: laporan bulan lalu tidak berubah
            $this->loginSebagai($browser, self::MANAJER)->visit($urlLaporan);
            $this->assertSame($sebelum, $this->nilaiMetrik($browser), 'Laporan periode tertutup tidak boleh berubah');
        });

        $this->assertSame('tutup', DB::table('tblPERIODE')->where('periode_id', $periodeId)->value('status_periode'));
        $this->assertSame($jumlahMutasi, DB::table('tblMUTASI_KAS')->count(), 'Mutasi kas tidak boleh tercatat');
    }
}
