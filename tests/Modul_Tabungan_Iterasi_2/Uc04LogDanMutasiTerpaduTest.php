<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc04LogDanMutasiTerpaduTest extends TabunganDuskTestCase
{
    /**
     * TC-04-01 | Positive | Pilih rekening via searchable dropdown di /tabungan/mutasi, filter periode, dan verifikasi cetak mutasi
     */
    public function test_tc_04_01_filter_mutasi_rekening_dropdown_dan_cetak_mutasi(): void
    {
        $this->createTransaction($this->rekeningAktif, 'setoran', 100000, 1100000);
        $this->createTransaction($this->rekeningAktif, 'penarikan', 40000, 1060000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1060000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/mutasi')
                ->waitForText('Log & Mutasi Rekening', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(300);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#rekSearchInput', $this->rekeningAktif);

            $browser->pause(350)
                ->waitFor('.rek-option', 15)
                ->script("document.querySelector('.rek-option')?.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));");

            $browser->waitForText('Budi Siswa Aktif', 20)
                ->waitFor('#periodeSelect', 15)
                ->select('#periodeSelect', 'harian')
                ->press('Tampilkan')
                ->waitForText('Budi Siswa Aktif', 15)
                ->assertSee('100.000')
                ->assertSee('40.000')
                ->assertSee('1.060.000')
                ->visit('/tabungan/mutasi/cetak?no_rek=' . urlencode($this->rekeningAktif) . '&periode=bulanan')
                ->waitForText('Cetak Laporan Mutasi', 15)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee($this->rekeningAktif)
                ->assertSee('TOTAL')
                ->assertSee('Rp 100.000')
                ->assertSee('Rp 40.000')
                ->assertSee('Rp 1.060.000');
        });
    }

    /**
     * TC-04-02 | Positive | Buka tab Log Transaksi (/tabungan/mutasi?tab=harian), uji filter log_periode, dan verifikasi cetak log
     */
    public function test_tc_04_02_filter_log_transaksi_periode_dan_cetak_log(): void
    {
        $this->createTransaction($this->rekeningAktif, 'setoran', 75000, 1075000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1075000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/mutasi?tab=harian')
                ->waitForText('Log Transaksi', 15)
                ->assertSee($this->rekeningAktif)
                ->assertSee('Rp 75.000')
                ->waitFor('#logPeriodeSelect', 10)
                ->select('#logPeriodeSelect', 'bulanan')
                ->press('Tampilkan')
                ->waitForText('Log Transaksi', 15)
                ->assertSee('Rp 75.000')
                ->visit('/tabungan/log-transaksi/cetak?log_periode=harian')
                ->waitForText('Cetak Log Transaksi', 15)
                ->assertSee($this->rekeningAktif)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee('TOTAL (1 transaksi)')
                ->assertSee('Rp 75.000')
                ->assertSee('Selisih Bersih');
        });
    }
}
