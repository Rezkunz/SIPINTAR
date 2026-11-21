<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

class Uc01DrafSiswaPraktikTest extends TabunganDuskTestCase
{
    /**
     * TC-01-01 | Positive | Input draf setoran valid (status Pending, saldo belum berubah)
     */
    public function test_tc_01_01_input_draf_setoran_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/dashboard')
                ->waitForText('Input Draft', 15)
                ->clickLink('Input Draft')
                ->waitForLocation('/tabungan/draft', 15)
                ->waitForText('Input Draft Transaksi Tabungan', 15)
                ->assertSee('Input Draft Transaksi Tabungan')
                ->radio('jenis', 'setoran')
                ->waitFor('#rekSearchDraftInput', 10)
                ->type('#rekSearchDraftInput', $this->rekeningAktif)
                ->pause(150)
                ->waitFor('.draft-rek-option', 10)
                ->click('.draft-rek-option')
                ->type('#nominal_display', '50000')
                ->click('#btn-submit-draft')
                ->waitForLocation('/tabungan/draft/riwayat', 15)
                ->assertPathIs('/tabungan/draft/riwayat')
                ->waitForText('Draft transaksi berhasil disimpan dengan status menunggu verifikasi loket.', 15)
                ->assertSee('Draft transaksi berhasil disimpan dengan status menunggu verifikasi loket.');

            $this->dismissPopup($browser);
        });

        $draft = DB::table('tblDRAFT')->where('siswa_id', $this->siswaId)->first();
        $this->assertNotNull($draft);
        $this->assertSame('pending', $draft->status_verifikasi);
        $this->assertSame(50000.0, (float) $draft->nominal);
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
    }

    /**
     * TC-01-02 | Positive | Input draf penarikan valid (saldo >= Rp20.000)
     */
    public function test_tc_01_02_input_draf_penarikan_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/dashboard')
                ->waitForText('Input Draft', 15)
                ->clickLink('Input Draft')
                ->waitForLocation('/tabungan/draft', 15)
                ->waitForText('Input Draft Transaksi Tabungan', 15)
                ->assertSee('Input Draft Transaksi Tabungan')
                ->radio('jenis', 'penarikan')
                ->waitFor('#rekSearchDraftInput', 10)
                ->type('#rekSearchDraftInput', $this->rekeningAktif)
                ->pause(150)
                ->waitFor('.draft-rek-option', 10)
                ->click('.draft-rek-option')
                ->type('#nominal_display', '20000')
                ->click('#btn-submit-draft')
                ->waitForLocation('/tabungan/draft/riwayat', 15)
                ->assertPathIs('/tabungan/draft/riwayat');

            $this->dismissPopup($browser);
        });

        $draft = DB::table('tblDRAFT')->where('siswa_id', $this->siswaId)->first();
        $this->assertNotNull($draft);
        $this->assertSame('pending', $draft->status_verifikasi);
        $this->assertSame(20000.0, (float) $draft->nominal);
        $this->assertSame(2, (int) $draft->jenis_transaksi_id);
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
    }

    /**
     * TC-01-03 | Negative | Nominal kosong / 0 / kurang dari minimal Rp10.000 / negatif pada draf setoran ditolak
     */
    public function test_tc_01_03_nominal_kosong_nol_dan_negatif_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/draft?jenis=setoran')
                ->waitFor('#rekSearchDraftInput', 15);

            $this->isi($browser, '#rekSearchDraftInput', $this->rekeningAktif);

            $browser->pause(250)
                ->waitFor('.draft-rek-option', 15)
                ->script("document.querySelector('.draft-rek-option')?.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));");

            // Kasus 1: Nominal 0 ditolak oleh validasi minimal Rp10.000
            $this->isi($browser, '#nominal_display', '0');
            $browser->click('#btn-submit-draft')
                ->waitForText('Nominal transaksi minimal adalah Rp 10.000.', 15)
                ->assertPathIs('/tabungan/draft')
                ->assertSee('Nominal transaksi minimal adalah Rp 10.000.');

            // Kasus 2: Nominal kurang dari Rp10.000 (5000) & negatif (-5000 -> 5.000) ditolak validasi minimal Rp10.000
            $browser->clear('#nominal_display')
                ->type('#nominal_display', '-5000')
                ->assertInputValue('#nominal_display', '5.000')
                ->click('#btn-submit-draft')
                ->waitForText('Nominal transaksi minimal adalah Rp 10.000.', 15)
                ->assertPathIs('/tabungan/draft')
                ->assertSee('Nominal transaksi minimal adalah Rp 10.000.');

            // Kasus 3: Nominal dikosongkan
            $browser->clear('#nominal_display')
                ->click('#btn-submit-draft')
                ->assertPathIs('/tabungan/draft');
        });

        $this->assertSame(0, DB::table('tblDRAFT')->count());
    }

    /**
     * TC-01-04 | Negative | Nomor rekening tidak terdaftar ('9999999999')
     */
    public function test_tc_01_04_nomor_rekening_tidak_terdaftar_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/dashboard')
                ->waitForText('Input Draft', 15)
                ->clickLink('Input Draft')
                ->waitForLocation('/tabungan/draft', 15)
                ->waitForText('Input Draft Transaksi Tabungan', 15)
                ->assertSee('Input Draft Transaksi Tabungan')
                ->waitFor('#rekSearchDraftInput', 10)
                ->radio('jenis', 'setoran');

            // Searchable dropdown hanya menampilkan rekening aktif yang terdaftar di database
            // Nomor rekening fiktif '9999999999' tidak ditemukan pada hasil pencarian
            $browser->type('#rekSearchDraftInput', '9999999999')
                ->waitForText('Rekening aktif tidak ditemukan.', 10)
                ->assertSee('Rekening aktif tidak ditemukan.');

            // Mengisi nominal tanpa memilih nomor rekening valid dicegah
            $browser->type('#nominal_display', '10000')
                ->click('#btn-submit-draft')
                ->assertPathIs('/tabungan/draft');
        });

        $this->assertSame(0, DB::table('tblDRAFT')->count());
    }

    /**
     * TC-01-05 | Negative | Siswa membuka halaman verifikasi Teller (403/redirect)
     */
    public function test_tc_01_05_siswa_membuka_halaman_verifikasi_teller_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/verifikasi')
                ->assertSee('403')
                ->assertDontSee('Verifikasi Draft Transaksi');

            $browser->visit('/tabungan/setoran')
                ->assertPathIs('/tabungan/draft')
                ->assertQueryStringHas('jenis', 'setoran')
                ->assertDontSee('Simpan Setoran');

            $browser->visit('/tabungan/penarikan')
                ->assertPathIs('/tabungan/draft')
                ->assertQueryStringHas('jenis', 'penarikan')
                ->assertDontSee('Simpan Penarikan');
        });
    }

    /**
     * TC-01-06 | Negative | Input draf penarikan dengan nominal < Rp10.000 (Rp5.000) ditolak validasi
     */
    public function test_tc_01_06_input_draf_penarikan_kurang_minimal_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/draft?jenis=penarikan')
                ->waitForText('Input Draft Transaksi Tabungan', 15)
                ->waitFor('#rekSearchDraftInput', 15);

            $this->isi($browser, '#rekSearchDraftInput', $this->rekeningAktif);

            $browser->pause(250)
                ->waitFor('.draft-rek-option', 15)
                ->script("document.querySelector('.draft-rek-option')?.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));");

            $this->isi($browser, '#nominal_display', '5000');

            $browser->assertInputValue('#nominal_display', '5.000')
                ->click('#btn-submit-draft')
                ->waitForText('Nominal transaksi minimal adalah Rp 10.000.', 15)
                ->assertPathIs('/tabungan/draft')
                ->assertSee('Nominal transaksi minimal adalah Rp 10.000.');
        });

        $this->assertSame(0, DB::table('tblDRAFT')->count());
    }
}
