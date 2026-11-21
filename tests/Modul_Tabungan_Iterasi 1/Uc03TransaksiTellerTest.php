<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;

class Uc03TransaksiTellerTest extends TabunganDuskTestCase
{
    /**
     * TC-03-01 | Positive | Setoran langsung Rp100.000
     */
    public function test_tc_03_01_setoran_langsung_di_loket_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/setoran')
                ->waitForText('Setoran Tunai', 15)
                ->waitFor('select[name="no_rek"]', 10)
                ->select('no_rek', $this->rekeningAktif)
                ->waitFor('#nominal_display', 10)
                ->type('#nominal_display', '100000')
                ->press('Simpan Setoran')
                ->waitUntil("window.location.pathname.startsWith('/tabungan/bukti/')", 15)
                ->assertPathBeginsWith('/tabungan/bukti/')
                ->waitForText('Cetak Bukti', 15)
                ->assertSee('SIPINTAR')
                ->assertSee('Bank Mini Sekolah')
                ->assertSee('Setoran')
                ->assertSee('Rp 100.000')
                ->assertSee('Cetak Bukti');

            $this->dismissPopup($browser);
        });

        $transaksi = DB::table('tblTRANSAKSI')->latest('transaksi_id')->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(0.0, (float) $transaksi->debit);
        $this->assertSame(100000.0, (float) $transaksi->kredit);
        $this->assertSame(1100000.0, (float) $transaksi->saldo_akhir);
        $this->assertSame($this->tellerId, (int) $transaksi->user_id);
        $this->assertSame(1100000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(10100000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));
        $this->assertSame(1, DB::table('tblBUKTI')->where('transaksi_id', $transaksi->transaksi_id)->count());
    }

    /**
     * TC-03-02 | Positive | Penarikan <= saldo Rp50.000
     */
    public function test_tc_03_02_penarikan_kurang_dari_atau_sama_dengan_saldo(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/penarikan')
                ->waitForText('Penarikan Tunai', 15)
                ->waitFor('select[name="no_rek"]', 10)
                ->select('no_rek', $this->rekeningAktif)
                ->waitFor('#nominal_display', 10)
                ->type('#nominal_display', '50000')
                ->press('Simpan Penarikan')
                ->waitUntil("window.location.pathname.startsWith('/tabungan/bukti/')", 15)
                ->assertPathBeginsWith('/tabungan/bukti/')
                ->waitForText('Cetak Bukti', 15)
                ->assertSee('Bukti Transaksi')
                ->assertSee('Penarikan')
                ->assertSee('Rp 50.000')
                ->assertSee('Cetak Bukti');

            $this->dismissPopup($browser);
        });

        $transaksi = DB::table('tblTRANSAKSI')->latest('transaksi_id')->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(50000.0, (float) $transaksi->debit);
        $this->assertSame(0.0, (float) $transaksi->kredit);
        $this->assertSame(950000.0, (float) $transaksi->saldo_akhir);
        $this->assertSame(950000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(9950000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $mutasi = DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($mutasi);
        $this->assertSame('keluar', $mutasi->arah);
        $this->assertSame(50000.0, (float) $mutasi->nominal);
    }

    /**
     * TC-03-03 | Negative | Penarikan melebihi saldo (saldo Rp30.000, tarik Rp50.000)
     */
    public function test_tc_03_03_penarikan_melebihi_saldo_ditolak(): void
    {
        // Sesuaikan saldo rekening menjadi Rp30.000 sesuai prakondisi Excel
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 30000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/penarikan')
                ->waitForText('Penarikan Tunai', 15)
                ->waitFor('select[name="no_rek"]', 10)
                ->select('no_rek', $this->rekeningAktif)
                ->waitFor('#nominal_display', 10)
                ->type('#nominal_display', '50000')
                ->press('Simpan Penarikan')
                ->waitForText('Saldo rekening harus menyisakan minimal Rp 10.000.', 15)
                ->assertPathIs('/tabungan/penarikan')
                ->assertSee('Saldo rekening harus menyisakan minimal Rp 10.000.');
        });

        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(30000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }

    /**
     * TC-03-04 | Negative | Nominal negatif / huruf pada form setoran ('-10000', lalu 'abc')
     */
    public function test_tc_03_04_nominal_negatif_atau_huruf_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/setoran')
                ->assertSee('Setoran Tunai')
                ->select('no_rek', $this->rekeningAktif);

            // Kasus 1: Pengguna mencoba mengisi nominal negatif '-10000'
            // Masking JS pada #nominal_display membersihkan '-' menjadi '10.000'
            $browser->type('#nominal_display', '-10000')
                ->assertInputValue('#nominal_display', '10.000');

            // Kasus 2: Pengguna mencoba mengisi huruf 'abc' -> dibersihkan menjadi kosong & tertahan required
            $browser->clear('#nominal_display')
                ->type('#nominal_display', 'abc')
                ->assertInputValue('#nominal_display', '')
                ->press('Simpan Setoran')
                ->assertPathIs('/tabungan/setoran');
        });

        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }

    /**
     * TC-03-05 | Negative | Penarikan langsung di loket dengan nominal < Rp10.000 (Rp5.000) ditolak
     */
    public function test_tc_03_05_penarikan_kurang_dari_minimal_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/penarikan')
                ->waitForText('Penarikan Tunai', 15)
                ->waitFor('select[name="no_rek"]', 15)
                ->select('no_rek', $this->rekeningAktif);

            $this->isi($browser, '#nominal_display', '5000');

            $browser->assertInputValue('#nominal_display', '5.000')
                ->press('Simpan Penarikan')
                ->waitForText('Nominal penarikan minimal Rp 10.000.', 15)
                ->assertPathIs('/tabungan/penarikan')
                ->assertSee('Nominal penarikan minimal Rp 10.000.');
        });

        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }
}
