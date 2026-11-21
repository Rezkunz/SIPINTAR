<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc03KelolaDanTutupAkunNasabahTest extends TabunganDuskTestCase
{
    /**
     * TC-03-01 | Positive | Pencarian/filter di /tabungan/kelola-akun dan edit profil nasabah valid
     */
    public function test_tc_03_01_cari_filter_dan_edit_profil_nasabah_valid(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');

        $this->browse(function (Browser $browser) use ($rekeningId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/kelola-akun')
                ->waitForText('Kelola Akun Nasabah', 25)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#search', $this->rekeningAktif);

            $browser->waitFor('#status', 10)
                ->select('#status', 'aktif')
                ->press('Cari')
                ->waitUntilMissingText('Citra Pending', 20)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee($this->rekeningAktif)
                ->assertDontSee('Citra Pending')
                ->waitFor('a[href$="/tabungan/kelola-akun/' . $rekeningId . '"]', 10)
                ->click('a[href$="/tabungan/kelola-akun/' . $rekeningId . '"]')
                ->waitForLocation('/tabungan/kelola-akun/' . $rekeningId, 15)
                ->waitForText('Edit Profil Nasabah', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#nama_nasabah', 'Budi Siswa Diperbarui');
            $this->isi($browser, '#no_hp', '081200009999');
            $browser->select('#jenis_nasabah', 'non_pegawai');
            $this->isi($browser, '#kelas_unit', 'XII AKL 2');

            $browser->press('Simpan Perubahan Profil')
                ->waitForText('Budi Siswa Diperbarui', 20)
                ->assertSee('Budi Siswa Diperbarui');

            $this->dismissPopup($browser);
        });

        $nasabah = DB::table('tblNASABAH')->where('nasabah_id', $this->nasabahId)->first();
        $this->assertNotNull($nasabah);
        $this->assertSame('Budi Siswa Diperbarui', $nasabah->nama_nasabah);
        $this->assertSame('081200009999', $nasabah->no_hp);
        $this->assertSame('XII AKL 2', $nasabah->kelas_unit);

        $user = DB::table('tblUSER')->where('nasabah_id', $this->nasabahId)->first();
        $this->assertNotNull($user);
        $this->assertSame('Budi Siswa Diperbarui', $user->nama_user);
        $this->assertSame('081200009999', $user->no_hp);

        $audit = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'EDIT_PROFIL_NASABAH')
            ->where('entitas', 'tblNASABAH')
            ->where('entitas_id', $this->nasabahId)
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * TC-03-02 | Positive | Reset password akun nasabah menampilkan password sementara sekali dan mencatat audit
     */
    public function test_tc_03_02_reset_password_nasabah_sekali_tampil_valid(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');
        $nasabahUserId = (int) DB::table('tblUSER')->where('nasabah_id', $this->nasabahId)->value('user_id');

        $this->browse(function (Browser $browser) use ($rekeningId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/kelola-akun/' . $rekeningId)
                ->waitForText('Reset Password / PIN Nasabah', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $browser->script('window.confirm = function() { return true; };');

            $browser->press('Generate Password/PIN Baru')
                ->waitForText('Password / PIN Sementara (Hanya Ditampilkan Sekali):', 20)
                ->assertSee('Password / PIN Sementara (Hanya Ditampilkan Sekali):');

            $this->dismissPopup($browser);

            // Setelah halaman dimuat ulang, banner password sementara tidak boleh tampil lagi
            $browser->visit('/tabungan/kelola-akun/' . $rekeningId)
                ->waitForText('Reset Password / PIN Nasabah', 15)
                ->assertDontSee('Password / PIN Sementara (Hanya Ditampilkan Sekali):');
        });

        $audit = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'RESET_PASSWORD_NASABAH')
            ->where('entitas', 'tblUSER')
            ->where('entitas_id', $nasabahUserId)
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * TC-03-03 | Positive | Tutup rekening aktif bersaldo mencairkan kas dan membuka halaman bukti-tutup
     */
    public function test_tc_03_03_tutup_rekening_aktif_bersaldo_mencairkan_kas_valid(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');

        $this->browse(function (Browser $browser) use ($rekeningId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/kelola-akun/' . $rekeningId)
                ->waitForText('Penutupan Rekening', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#alasan', 'Siswa telah lulus sekolah');
            $browser->script('window.confirm = function() { return true; };');

            $browser->press('Tutup Rekening Permanen')
                ->waitForLocation('/tabungan/kelola-akun/' . $rekeningId . '/bukti-tutup', 20)
                ->waitForText('Cetak Bukti Penutupan', 15)
                ->assertSee('BUKTI PENUTUPAN REKENING TABUNGAN')
                ->assertSee($this->rekeningAktif)
                ->assertSee('TUTUP')
                ->assertSee('Rp 1.000.000')
                ->assertSee('Siswa telah lulus sekolah');
        });

        $rekening = DB::table('tblREKENING')->where('rekening_id', $rekeningId)->first();
        $this->assertNotNull($rekening);
        $this->assertSame('tutup', $rekening->status_rekening);
        $this->assertSame(0.0, (float) $rekening->saldo);

        $transaksi = DB::table('tblTRANSAKSI')->where('rekening_id', $rekeningId)->first();
        $this->assertNotNull($transaksi);
        $this->assertSame(1000000.0, (float) $transaksi->debit);
        $this->assertSame(0.0, (float) $transaksi->saldo_akhir);

        $mutasiKas = DB::table('tblMUTASI_KAS')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($mutasiKas);
        $this->assertSame('keluar', $mutasiKas->arah);
        $this->assertSame(1000000.0, (float) $mutasiKas->nominal);
        $this->assertSame(9000000.0, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $bukti = DB::table('tblBUKTI')->where('transaksi_id', $transaksi->transaksi_id)->first();
        $this->assertNotNull($bukti);
        $this->assertStringStartsWith('CLOSE-', $bukti->no_bukti);

        $audit = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'TUTUP_REKENING')
            ->where('entitas', 'tblREKENING')
            ->where('entitas_id', $rekeningId)
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * TC-03-04 | Negative | Menutup rekening yang masih memiliki draft pending ditolak sistem
     */
    public function test_tc_03_04_tutup_rekening_dengan_draft_pending_ditolak(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');
        $this->createDraft($this->rekeningAktif, 'setoran', 50000, $this->siswaId, 'pending');

        $this->browse(function (Browser $browser) use ($rekeningId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/kelola-akun/' . $rekeningId)
                ->waitForText('Penutupan Rekening', 15)
                ->waitUntil("document.readyState === 'complete'", 15)
                ->pause(250);

            $this->disableAutoReload($browser);
            $this->isi($browser, '#alasan', 'Tutup saat masih ada draft pending');
            $browser->script('window.confirm = function() { return true; };');

            $browser->press('Tutup Rekening Permanen')
                ->waitForText('Rekening masih memiliki draft transaksi pending yang belum diverifikasi.', 20)
                ->assertPathIs('/tabungan/kelola-akun/' . $rekeningId)
                ->assertSee('Rekening masih memiliki draft transaksi pending yang belum diverifikasi.');
        });

        $rekening = DB::table('tblREKENING')->where('rekening_id', $rekeningId)->first();
        $this->assertSame('aktif', $rekening->status_rekening);
        $this->assertSame(1000000.0, (float) $rekening->saldo);
        $this->assertSame(0, DB::table('tblAUDIT_LOG')->where('aksi', 'TUTUP_REKENING')->count());
    }

    /**
     * TC-03-05 | Negative | Mencoba menutup ulang rekening yang sudah berstatus tutup ditolak sistem
     */
    public function test_tc_03_05_tutup_rekening_yang_sudah_tutup_ditolak(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');
        DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update([
            'status_rekening' => 'tutup',
            'saldo' => 0,
        ]);

        $this->browse(function (Browser $browser) use ($rekeningId) {
            $this->disableAutoReload($browser);
            $this->loginAsTeller($browser)
                ->visit('/tabungan/kelola-akun/' . $rekeningId)
                ->waitForText('Penutupan Rekening', 15)
                ->assertSee('Rekening ini sudah berstatus Tutup.')
                ->assertDontSee('Tutup Rekening Permanen');

            $this->disableAutoReload($browser);

            // Uji penolakan backend jika POST penutupan dikirim ulang pada rekening yang sudah tutup
            $browser->script("
                const token = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/tabungan/kelola-akun/{$rekeningId}/tutup';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = token;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            ");

            $browser->waitForText('Rekening sudah berstatus tutup.', 15)
                ->assertPathIs('/tabungan/kelola-akun/' . $rekeningId)
                ->assertSee('Rekening sudah berstatus tutup.')
                ->assertSee('Rekening ini sudah berstatus Tutup.');
        });

        $this->assertSame('tutup', DB::table('tblREKENING')->where('rekening_id', $rekeningId)->value('status_rekening'));
    }
}
