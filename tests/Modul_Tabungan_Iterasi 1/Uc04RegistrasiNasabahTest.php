<?php

namespace Tests\Modul_Tabungan;

require_once __DIR__ . '/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

class Uc04RegistrasiNasabahTest extends TabunganDuskTestCase
{
    /**
     * TC-04-01 | Positive | Pengajuan dengan data lengkap (Pending Approval)
     *
     * Catatan: Excel mensyaratkan nasabah login lalu membuka menu Pengajuan Rekening.
     * Aplikasi mengimplementasikan alur ini melalui registrasi mandiri di /register.
     */
    public function test_tc_04_01_pengajuan_pembukaan_rekening_data_lengkap(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/register')
                ->waitForLocation('/register', 15)
                ->waitForText('Buka Rekening', 15)
                ->waitFor('select[name="jenis_nasabah"]', 10)
                ->select('jenis_nasabah', 'non_pegawai')
                ->waitFor('input[name="no_induk"]', 10)
                ->type('no_induk', '0098765432')
                ->type('nama_lengkap', 'Rina Siswa Baru')
                ->type('email', 'rina.siswa.baru@gmail.com')
                ->type('kelas_unit', 'XI AKL 3')
                ->type('no_hp', '081200000021')
                ->type('password', 'sipintar123')
                ->type('password_confirmation', 'sipintar123')
                ->waitFor('#btn-submit-register', 10)
                ->click('#btn-submit-register')
                ->waitForLocation('/dashboard', 15)
                ->waitForText('Pendaftaran akun berhasil', 15)
                ->assertSee('Pendaftaran akun berhasil');

            $this->dismissPopup($browser);
        });

        $nasabah = DB::table('tblNASABAH')->where('no_hp', '081200000021')->first();
        $this->assertNotNull($nasabah);
        $this->assertSame('Rina Siswa Baru', $nasabah->nama_nasabah);

        $rekening = DB::table('tblREKENING')->where('nasabah_id', $nasabah->nasabah_id)->first();
        $this->assertNotNull($rekening);
        $this->assertSame('pending', $rekening->status_rekening);
        $this->assertSame(0.0, (float) $rekening->saldo);

        $user = DB::table('tblUSER')->where('nasabah_id', $nasabah->nasabah_id)->first();
        $this->assertNotNull($user);
        $this->assertSame(6, (int) $user->role_id);
        $this->assertSame($nasabah->nasabah_id, (int) $user->nasabah_id);
    }

    /**
     * TC-04-02 | Negative | Field wajib kosong
     */
    public function test_tc_04_02_pengajuan_rekening_field_wajib_kosong_ditolak(): void
    {
        $jumlahNasabah = DB::table('tblNASABAH')->count();

        $this->browse(function (Browser $browser) {
            // Pengguna mengosongkan nomor HP
            $browser->logout()
                ->visit('/register')
                ->select('jenis_nasabah', 'non_pegawai')
                ->type('no_induk', '0098765499')
                ->type('nama_lengkap', 'Siswa Tanpa HP')
                ->type('email', 'tanpa.hp@gmail.com')
                ->type('kelas_unit', 'XI AKL 1')
                ->type('password', 'sipintar123')
                ->type('password_confirmation', 'sipintar123')
                ->click('#btn-submit-register')
                ->assertPathIs('/register');
        });

        $this->assertSame($jumlahNasabah, DB::table('tblNASABAH')->count());
    }

    /**
     * TC-04-03 | Negative | Nomor HP sudah terdaftar
     */
    public function test_tc_04_03_pengajuan_rekening_nomor_hp_sudah_terdaftar_ditolak(): void
    {
        $jumlahNasabah = DB::table('tblNASABAH')->count();

        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/register')
                ->waitForText('Buka Rekening', 15)
                ->waitFor('select[name="jenis_nasabah"]', 10)
                ->select('jenis_nasabah', 'non_pegawai')
                ->type('no_induk', '0098765433')
                ->type('nama_lengkap', 'Duplikat Nomor Hp')
                ->type('email', 'duplikat.hp@gmail.com')
                ->type('kelas_unit', 'XI AKL 4')
                ->type('no_hp', '081200000001')
                ->type('password', 'sipintar123')
                ->type('password_confirmation', 'sipintar123')
                ->click('#btn-submit-register')
                ->assertPathIs('/register')
                ->waitForText('digunakan pada akun nasabah lain.', 15)
                ->assertSee('digunakan pada akun nasabah lain.');
        });

        $this->assertSame($jumlahNasabah, DB::table('tblNASABAH')->count());
        $this->assertSame(0, DB::table('tblREKENING')->where('no_rek', 'like', '%00005')->count());
    }
}
