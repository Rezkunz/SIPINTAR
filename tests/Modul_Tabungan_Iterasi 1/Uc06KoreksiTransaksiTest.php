<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

class Uc06KoreksiTransaksiTest extends TabunganDuskTestCase
{
    /**
     * TC-06-01 | Positive | Koreksi nominal transaksi melalui halaman /tabungan/koreksi & Modal Koreksi
     */
    public function test_tc_06_01_koreksi_transaksi_membuat_pembalik_dan_transaksi_baru(): void
    {
        $transaksiId = $this->createTransaction($this->rekeningAktif, 'setoran', 50000, 1050000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1050000]);
        DB::table('tblKAS')->where('kas_id', 1)->update(['saldo' => 10050000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/koreksi')
                ->waitForText('Koreksi Transaksi', 15)
                ->waitFor('#rekSearchInput', 10)
                ->type('#rekSearchInput', $this->rekeningAktif)
                ->pause(300)
                ->waitFor('.rek-option', 10)
                ->click('.rek-option')
                ->waitForText('Daftar Transaksi Rekening '.$this->rekeningAktif, 15)
                ->assertSee('Rp 50.000')
                ->waitFor('.btn-open-koreksi', 10)
                ->click('.btn-open-koreksi')
                ->waitFor('#koreksiModal.open', 10)
                ->waitFor('#koreksiNominalDisplay', 10)
                ->select('#koreksiJenis', '1')
                ->clear('#koreksiNominalDisplay')
                ->type('#koreksiNominalDisplay', '75000')
                ->assertInputValue('#koreksiNominalDisplay', '75.000')
                ->waitFor('#koreksiAlasan', 10)
                ->type('#koreksiAlasan', 'Koreksi nominal setoran yang salah input')
                ->press('Simpan Koreksi')
                ->waitForLocation('/tabungan/koreksi', 15)
                ->assertPathIs('/tabungan/koreksi')
                ->waitForText('Transaksi berhasil dikoreksi melalui transaksi baru dan pembalik.', 15)
                ->assertSee('Riwayat Koreksi Transaksi')
                ->assertSee('Koreksi nominal setoran yang salah input');

            $this->dismissPopup($browser);
        });

        $this->assertSame(3, DB::table('tblTRANSAKSI')->count());

        $pembalik = DB::table('tblTRANSAKSI')
            ->where('transaksi_id', '!=', $transaksiId)
            ->where('debit', 50000)
            ->where('kredit', 0)
            ->first();
        $this->assertNotNull($pembalik);
        $this->assertSame(1000000.0, (float) $pembalik->saldo_akhir);
        $this->assertSame($this->tellerId, (int) $pembalik->user_id);

        $transaksiBaru = DB::table('tblTRANSAKSI')
            ->where('transaksi_id', '!=', $transaksiId)
            ->where('kredit', 75000)
            ->first();
        $this->assertNotNull($transaksiBaru);
        $this->assertSame(1075000.0, (float) $transaksiBaru->saldo_akhir);
        $this->assertSame(1075000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(10075000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $audit = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'KOREKSI_TRANSAKSI')
            ->where('entitas_id', $transaksiId)
            ->first();
        $this->assertNotNull($audit);
        $this->assertNotNull(DB::table('tblBUKTI')->where('transaksi_id', $transaksiBaru->transaksi_id)->first());
    }

    /**
     * TC-06-02 | Negative | Koreksi tanpa alasan ditolak
     */
    public function test_tc_06_02_koreksi_tanpa_alasan_ditolak(): void
    {
        $this->createTransaction($this->rekeningAktif, 'setoran', 50000, 1050000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1050000]);
        DB::table('tblKAS')->where('kas_id', 1)->update(['saldo' => 10050000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/koreksi?no_rek='.urlencode($this->rekeningAktif))
                ->waitForText('Daftar Transaksi Rekening '.$this->rekeningAktif, 15)
                ->waitFor('.btn-open-koreksi', 10)
                ->click('.btn-open-koreksi')
                ->waitFor('#koreksiModal.open', 10)
                ->waitFor('#koreksiNominalDisplay', 10)
                ->select('#koreksiJenis', '1')
                ->clear('#koreksiNominalDisplay')
                ->type('#koreksiNominalDisplay', '75000')
                ->clear('#koreksiAlasan')
                ->press('Simpan Koreksi')
                ->assertPathIs('/tabungan/koreksi');
        });

        $this->assertSame(1, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(0, DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->count());
        $this->assertSame(1050000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }

    /**
     * TC-06-03 | Negative | Koreksi pada periode yang sudah ditutup ditolak
     */
    public function test_tc_06_03_koreksi_pada_periode_tutup_ditolak(): void
    {
        $this->createTransaction($this->rekeningAktif, 'setoran', 50000, 1050000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1050000]);
        DB::table('tblKAS')->where('kas_id', 1)->update(['saldo' => 10050000]);
        $this->markPeriodClosed();

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/koreksi?no_rek='.urlencode($this->rekeningAktif))
                ->waitForText('Daftar Transaksi Rekening '.$this->rekeningAktif, 15)
                ->waitFor('.btn-open-koreksi', 10)
                ->click('.btn-open-koreksi')
                ->waitFor('#koreksiModal.open', 10)
                ->waitFor('#koreksiNominalDisplay', 10)
                ->select('#koreksiJenis', '1')
                ->clear('#koreksiNominalDisplay')
                ->type('#koreksiNominalDisplay', '75000')
                ->waitFor('#koreksiAlasan', 10)
                ->type('#koreksiAlasan', 'Koreksi saat periode tutup')
                ->press('Simpan Koreksi')
                ->waitForText('Transaksi ditolak: Periode', 15)
                ->assertSee('telah ditutup oleh Manajer dan terkunci.');
        });

        $this->assertSame(1, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(0, DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->count());
        $this->assertSame(1050000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }
}
