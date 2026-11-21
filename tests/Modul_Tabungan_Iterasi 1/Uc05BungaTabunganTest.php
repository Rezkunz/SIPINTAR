<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

class Uc05BungaTabunganTest extends TabunganDuskTestCase
{
    /**
     * TC-05-01 | Positive | Hitung jasa satu periode (sama dengan perhitungan manual, tercatat ke saldo)
     */
    public function test_tc_05_01_hitung_jasa_satu_periode(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/jasa')
                ->waitForText('Kalkulator Bunga Tabungan', 15)
                ->assertSee('Kalkulator Bunga Tabungan')
                ->waitFor('select[name="no_rek"]', 10)
                ->select('no_rek', $this->rekeningAktif)
                ->press('Hitung')
                ->waitForText('Status Pembukuan Periode', 15)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee('+ Rp 1.000')
                ->assertSee('Rp 1.001.000')
                ->assertSee('Belum Dibukukan');
        });

        $exitCode = Artisan::call('sipintar:process-bunga-tabungan');
        $this->assertSame(0, $exitCode);

        $this->assertSame(1001000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(2004000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningPegawai)->value('saldo'));

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/jasa?no_rek='.urlencode($this->rekeningAktif))
                ->waitForText('Status Pembukuan Periode', 15)
                ->assertSee('Sudah Dibukukan');
        });
    }
}
