<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc01BukaRekeningDanDraftSiswaTest extends TabunganDuskTestCase
{
    /**
     * TC-01-01 | Positive | Input form buka rekening baru oleh Siswa Praktik valid (status pending, saldo 0, akun tblUSER terbentuk)
     */
    public function test_tc_01_01_buka_rekening_baru_oleh_siswa_praktik_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/buka-rekening')
                ->waitForText('Pembukaan Rekening Baru', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#nama_nasabah', 'Ahmad Siswa Praktik Baru');
            $browser->select('#jenis_nasabah', 'siswa');
            $this->isi($browser, '#no_induk', '0077889900');
            $this->isi($browser, '#kelas_unit', 'X AKL 1');
            $this->isi($browser, '#no_hp', '081299887766');
            $this->isi($browser, '#email', 'ahmad.baru@sipintar.test');
            $this->isi($browser, '#password', 'sipintar123');
            $this->isi($browser, '#password_confirmation', 'sipintar123');

            $browser->assertInputValue('#nama_nasabah', 'Ahmad Siswa Praktik Baru')
                ->waitFor('#btn-submit-rekening', 10)
                ->click('#btn-submit-rekening')
                ->waitForText('Ahmad Siswa Praktik Baru', 20)
                ->assertSee('Ahmad Siswa Praktik Baru');

            $this->dismissPopup($browser);
        });

        $nasabah = DB::table('tblNASABAH')->where('no_hp', '081299887766')->first();
        $this->assertNotNull($nasabah);
        $this->assertSame('Ahmad Siswa Praktik Baru', $nasabah->nama_nasabah);
        $this->assertSame('aktif', $nasabah->status_nasabah);

        $rekening = DB::table('tblREKENING')->where('nasabah_id', $nasabah->nasabah_id)->first();
        $this->assertNotNull($rekening);
        $this->assertSame('pending', $rekening->status_rekening);
        $this->assertSame(0.0, (float) $rekening->saldo);

        $user = DB::table('tblUSER')->where('nasabah_id', $nasabah->nasabah_id)->first();
        $this->assertNotNull($user);
        $this->assertSame(6, (int) $user->role_id);
        $this->assertSame('0077889900', $user->no_induk);
        $this->assertSame('aktif', $user->status_akun);
    }

    /**
     * TC-01-02 | Positive | Aktivasi rekening pending dengan setoran awal >= Rp 20.000
     */
    public function test_tc_01_02_aktivasi_rekening_setoran_awal_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/buka-rekening')
                ->waitForText('Rekening Menunggu Setoran Awal', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->assertSee($this->rekeningPending)
                ->assertSee('Citra Pending');

            $this->disableAutoReload($browser);

            $browser->press('Setujui & Aktifkan')
                ->waitForText('Tidak ada rekening yang menunggu setoran awal.', 20)
                ->assertDontSee('Citra Pending')
                ->assertSee('Tidak ada rekening yang menunggu setoran awal.');

            $this->dismissPopup($browser);
        });

        $rekening = DB::table('tblREKENING')->where('no_rek', $this->rekeningPending)->first();
        $this->assertNotNull($rekening);
        $this->assertSame('aktif', $rekening->status_rekening);
        $this->assertSame(20000.0, (float) $rekening->saldo);

        $transaksi = DB::table('tblTRANSAKSI')->where('rekening_id', $rekening->rekening_id)->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(20000.0, (float) $transaksi->kredit);
        $this->assertSame(20000.0, (float) $transaksi->saldo_akhir);

        $mutasiKas = DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($mutasiKas);
        $this->assertSame('masuk', $mutasiKas->arah);
        $this->assertSame(20000.0, (float) $mutasiKas->nominal);
        $this->assertSame(10020000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $bukti = DB::table('tblBUKTI')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($bukti);
        $this->assertStringStartsWith('OPEN-', $bukti->no_bukti);
    }

    /**
     * TC-01-03 | Negative | Buka rekening dengan no_hp atau no_induk duplikat ditolak validasi
     */
    public function test_tc_01_03_buka_rekening_no_hp_atau_no_induk_duplikat_ditolak(): void
    {
        $jumlahRekeningAwal = DB::table('tblREKENING')->count();

        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/buka-rekening')
                ->waitForText('Pembukaan Rekening Baru', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#nama_nasabah', 'Nasabah Duplikat HP');
            $browser->select('#jenis_nasabah', 'siswa');
            $this->isi($browser, '#no_induk', '0099112233');
            $this->isi($browser, '#kelas_unit', 'XI AKL 1');
            $this->isi($browser, '#no_hp', '081200000001');
            $this->isi($browser, '#password', 'sipintar123');
            $this->isi($browser, '#password_confirmation', 'sipintar123');

            $browser->waitFor('#btn-submit-rekening', 10)
                ->click('#btn-submit-rekening')
                ->waitForText('Nomor HP/WhatsApp ini sudah terdaftar sebagai nasabah/pengguna.', 20)
                ->assertPathIs('/tabungan/buka-rekening')
                ->assertSee('Nomor HP/WhatsApp ini sudah terdaftar sebagai nasabah/pengguna.');
        });

        $this->assertSame($jumlahRekeningAwal, DB::table('tblREKENING')->count());
    }

    /**
     * TC-01-04 | Negative | Aktivasi rekening pending dengan setoran awal < Rp 20.000 (10000) ditolak
     */
    public function test_tc_01_04_aktivasi_rekening_setoran_awal_kurang_minimal_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/buka-rekening')
                ->waitForText('Rekening Menunggu Setoran Awal', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->assertSee($this->rekeningPending);

            $this->disableAutoReload($browser);
            $this->isi($browser, 'form[action*="/buka-rekening/"] input[type="text"]', '10000');

            $browser->assertInputValue('form[action*="/buka-rekening/"] input[type="text"]', '10.000')
                ->press('Setujui & Aktifkan')
                ->waitForText('Terdapat kesalahan pengisian formulir:', 20)
                ->assertPathIs('/tabungan/buka-rekening');
        });

        $rekening = DB::table('tblREKENING')->where('no_rek', $this->rekeningPending)->first();
        $this->assertSame('pending', $rekening->status_rekening);
        $this->assertSame(0.0, (float) $rekening->saldo);
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
    }

    /**
     * TC-01-05 | Negative | Siswa Praktik mengakses detail draft milik Siswa Praktik lain ditolak (403 Anti-IDOR)
     */
    public function test_tc_01_05_akses_detail_draft_milik_siswa_lain_ditolak_anti_idor(): void
    {
        $this->createDraft($this->rekeningAktif, 'setoran', 30000, $this->siswaId, 'pending');
        $draftSiswaLainId = $this->createDraft($this->rekeningPegawai, 'setoran', 45000, $this->siswaLainId, 'disetujui');

        $this->browse(function (Browser $browser) use ($draftSiswaLainId) {
            $this->loginAsSiswa($browser)
                ->visit('/tabungan/draft/riwayat?status=pending')
                ->waitForText('Riwayat Draft Transaksi Saya', 15)
                ->assertSee('Rp 30.000')
                ->assertDontSee('Rp 45.000')
                ->visit('/tabungan/draft/' . $draftSiswaLainId)
                ->waitForText('403', 15)
                ->assertSee('403');
        });
    }
}
