<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;

class Uc02VerifikasiDraftTest extends TabunganDuskTestCase
{
    /**
     * TC-02-01 | Positive | Menyetujui draf setoran Rp50.000 (kredit, saldo +50.000)
     */
    public function test_tc_02_01_menyetujui_draft_setoran_mengubah_saldo_dan_membuat_transaksi(): void
    {
        $draftId = $this->createDraft($this->rekeningAktif, 'setoran', 50000, $this->siswaId);

        $this->browse(function (Browser $browser) use ($draftId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/verifikasi')
                ->waitForText('Periksa', 15)
                ->clickLink('Periksa')
                ->waitForText('Detail Draft Transaksi', 15)
                ->assertSee('Detail Draft Transaksi')
                ->assertSee('Rp 50.000')
                ->press('Setujui dan Catat')
                ->waitForLocation('/tabungan/verifikasi', 15)
                ->waitForText('Draft berhasil disetujui dan transaksi telah dicatat.', 15)
                ->assertSee('Draft berhasil disetujui dan transaksi telah dicatat.');

            $this->dismissPopup($browser);
        });

        $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->first();
        $this->assertSame('disetujui', $draft->status_verifikasi);
        $this->assertSame($this->tellerId, (int) $draft->teller_id);

        $transaksi = DB::table('tblTRANSAKSI')->where('draft_id', $draftId)->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(0.0, (float) $transaksi->debit);
        $this->assertSame(50000.0, (float) $transaksi->kredit);
        $this->assertSame(1050000.0, (float) $transaksi->saldo_akhir);
        $this->assertSame($this->tellerId, (int) $transaksi->user_id);
        $this->assertSame(1050000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(10050000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $mutasi = DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($mutasi);
        $this->assertSame('masuk', $mutasi->arah);
        $this->assertSame(50000.0, (float) $mutasi->nominal);
        $this->assertSame(1, DB::table('tblBUKTI')->count());
    }

    /**
     * TC-02-02 | Positive | Menyetujui draf penarikan (debit, saldo berkurang)
     */
    public function test_tc_02_02_menyetujui_draft_penarikan_hanya_mengurangi_saldo_setelah_approve(): void
    {
        $draftId = $this->createDraft($this->rekeningAktif, 'penarikan', 100000, $this->siswaId);

        $this->browse(function (Browser $browser) use ($draftId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/verifikasi/'.$draftId)
                ->waitForText('Detail Draft Transaksi', 15)
                ->assertSee('Detail Draft Transaksi')
                ->press('Setujui dan Catat')
                ->waitForLocation('/tabungan/verifikasi', 15)
                ->waitForText('Draft berhasil disetujui dan transaksi telah dicatat.', 15)
                ->assertSee('Draft berhasil disetujui dan transaksi telah dicatat.');

            $this->dismissPopup($browser);
        });

        $transaksi = DB::table('tblTRANSAKSI')->where('draft_id', $draftId)->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(100000.0, (float) $transaksi->debit);
        $this->assertSame(0.0, (float) $transaksi->kredit);
        $this->assertSame(900000.0, (float) $transaksi->saldo_akhir);
        $this->assertSame(900000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
        $this->assertSame(9900000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $mutasi = DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($mutasi);
        $this->assertSame('keluar', $mutasi->arah);
        $this->assertSame(100000.0, (float) $mutasi->nominal);
    }

    /**
     * TC-02-03 | Negative | Menyetujui draf penarikan melebihi saldo / melanggar sisa minimal Rp10.000 ditolak
     */
    public function test_tc_02_03_approve_penarikan_melebihi_saldo_ditolak(): void
    {
        $draftId = $this->createDraft($this->rekeningAktif, 'penarikan', 1000000, $this->siswaId);

        $this->browse(function (Browser $browser) use ($draftId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/verifikasi/'.$draftId)
                ->waitForText('Detail Draft Transaksi', 15)
                ->press('Setujui dan Catat')
                ->waitForText('Saldo rekening harus menyisakan minimal Rp 10.000.', 15)
                ->assertPathIs('/tabungan/verifikasi/'.$draftId)
                ->assertSee('Saldo rekening harus menyisakan minimal Rp 10.000.');
        });

        $draft = DB::table('tblDRAFT')->where('draft_id', $draftId)->first();
        $this->assertSame('pending', $draft->status_verifikasi);
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }
}
