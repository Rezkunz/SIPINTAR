<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc02TolakDraftDanToggleSetoranTellerTest extends TabunganDuskTestCase
{
    /**
     * TC-02-01 | Positive | Teller menolak draft transaksi pending valid
     */
    public function test_tc_02_01_teller_menolak_draft_transaksi_pending_valid(): void
    {
        $draftId = $this->createDraft($this->rekeningAktif, 'setoran', 60000, $this->siswaId, 'pending');

        $this->browse(function (Browser $browser) use ($draftId) {
            $this->disableAutoReload($browser);
            $this->loginAsTeller($browser)
                ->visit('/tabungan/verifikasi/' . $draftId)
                ->waitForText('Detail Draft Transaksi', 15)
                ->assertSee('Rp 60.000')
                ->press('Tolak Draft')
                ->waitForLocation('/tabungan/verifikasi', 15)
                ->waitForText('Draft berhasil ditolak.', 15)
                ->assertSee('Draft berhasil ditolak.');

            $this->dismissPopup($browser);
        });

        $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->first();
        $this->assertNotNull($draft);
        $this->assertSame('ditolak', $draft->status_verifikasi);
        $this->assertSame($this->tellerId, (int) $draft->teller_id);
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(10000000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));
    }

    /**
     * TC-02-02 | Negative | Mencoba menolak draft yang sudah berstatus disetujui/ditolak ditolak dengan pesan validasi
     */
    public function test_tc_02_02_teller_menolak_draft_yang_sudah_diproses_ditolak(): void
    {
        $draftId = $this->createDraft($this->rekeningAktif, 'setoran', 50000, $this->siswaId, 'disetujui');
        DB::table('tblDRAFT')->where('draft_id', $draftId)->update(['teller_id' => $this->tellerId]);

        $this->browse(function (Browser $browser) use ($draftId) {
            $this->disableAutoReload($browser);
            $this->loginAsTeller($browser)
                ->visit('/tabungan/verifikasi/' . $draftId)
                ->waitForText('Detail Draft Transaksi', 15)
                ->assertSee('Draft sudah diproses: Disetujui')
                ->assertDontSee('Tolak Draft');

            $this->disableAutoReload($browser);

            // Uji penolakan backend jika request POST penolakan dikirim pada draft yang sudah diproses
            $browser->script("
                const token = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/tabungan/verifikasi/{$draftId}/tolak';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = token;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            ");

            $browser->waitForText('Draft tidak ditemukan atau sudah diproses.', 15)
                ->assertSee('Draft tidak ditemukan atau sudah diproses.')
                ->assertSee('Draft sudah diproses: Disetujui');
        });

        $this->assertSame('disetujui', DB::table('tblDRAFT')->where('draft_id', $draftId)->value('status_verifikasi'));
    }

    /**
     * TC-02-03 | Negative | Setoran langsung Rp 5.000 dengan aturan minimal aktif ditolak validasi minimal Rp 10.000
     */
    public function test_tc_02_03_setoran_kurang_minimal_tanpa_toggle_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/setoran')
                ->waitForText('Setoran Tunai', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);

            $browser->waitFor('select[name="no_rek"]', 15)
                ->select('no_rek', $this->rekeningAktif);

            $this->isi($browser, '#nominal_display', '5000');

            $browser->assertInputValue('#nominal_display', '5.000')
                ->assertChecked('#toggleMinSetoran')
                ->script('document.querySelector("form[action*=\\"/tabungan/setoran\\"]").noValidate = true;');

            $browser->press('Simpan Setoran')
                ->waitForText('Nominal setoran minimal Rp 10.000.', 20)
                ->assertPathIs('/tabungan/setoran')
                ->assertSee('Nominal setoran minimal Rp 10.000.');
        });

        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }

    /**
     * TC-02-04 | Positive | Setoran langsung Rp 5.000 dengan mengaktifkan opsi abaikan_minimal berhasil dicatat
     */
    public function test_tc_02_04_setoran_kurang_minimal_dengan_toggle_abaikan_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/setoran')
                ->waitForText('Setoran Tunai', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);

            $browser->waitFor('select[name="no_rek"]', 15)
                ->select('no_rek', $this->rekeningAktif);

            $this->isi($browser, '#nominal_display', '5000');

            $browser->waitFor('#toggleMinSetoran', 10)
                ->uncheck('#toggleMinSetoran')
                ->press('Simpan Setoran')
                ->waitUntil("window.location.pathname.startsWith('/tabungan/bukti/')", 15)
                ->assertPathBeginsWith('/tabungan/bukti/')
                ->waitForText('Cetak Bukti', 15)
                ->assertSee('Setoran')
                ->assertSee('Rp 5.000')
                ->assertSee('Cetak Bukti');

            $this->dismissPopup($browser);
        });

        $transaksi = DB::table('tblTRANSAKSI')->latest('transaksi_id')->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(5000.0, (float) $transaksi->kredit);
        $this->assertSame(1005000.0, (float) $transaksi->saldo_akhir);
        $this->assertSame(1005000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(10005000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));
    }
}
