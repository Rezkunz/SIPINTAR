<?php

namespace Tests\Browser\Feature\UC_00;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-00 TC-00-01 Login dan autentikasi
 * Test Case: Login tiap peran dengan kredensial benar
 */
class test_uc_00_tc_00_01_login_dan_autentikasi extends SkenarioTestCase
{
    /**
     * Menu yang wajib tampil dan yang tidak boleh tampil di sidebar setiap peran.
     */
    private const MENU_PERAN = [
        'manajer' => [
            'tampil'  => ['Dashboard', 'Laporan Tabungan', 'Neraca & Gabungan', 'Tutup Periode', 'Kelola Pengguna'],
            'dilarang' => ['Verifikasi Draft', 'Kartu Piutang', 'Input Draft', 'Riwayat Mutasi'],
        ],
        'teller' => [
            'tampil'  => ['Dashboard Loket', 'Verifikasi Draft', 'Setoran', 'Penarikan', 'Audit Log'],
            'dilarang' => ['Kelola Pengguna', 'Tutup Periode', 'Kartu Piutang', 'Input Draft'],
        ],
        'bendahara' => [
            'tampil'  => ['Daftar Pinjaman', 'Pencairan Pinjaman', 'Kartu Piutang', 'Kas Tunai (Cash)'],
            'dilarang' => ['Verifikasi Draft', 'Kelola Pengguna', 'Tutup Periode', 'Input Draft'],
        ],
        'bendahara_sekolah' => [
            'tampil'  => ['Dashboard', 'Rencana & Hasil Potongan'],
            'dilarang' => ['Kartu Piutang', 'Verifikasi Draft', 'Kelola Pengguna', 'Tutup Periode'],
        ],
        'siswa' => [
            'tampil'  => ['Input Draft', 'Buka Rekening', 'Entri Setoran', 'Entri Penarikan'],
            'dilarang' => ['Verifikasi Draft', 'Kelola Pengguna', 'Kartu Piutang', 'Audit Log'],
        ],
        'nasabah_siswa' => [
            'tampil'  => ['Informasi Rekening', 'Riwayat Mutasi'],
            'dilarang' => ['Jadwal Angsuran', 'Verifikasi Draft', 'Kelola Pengguna', 'Input Draft'],
        ],
        'pegawai' => [
            'tampil'  => ['Informasi Rekening', 'Riwayat Mutasi', 'Jadwal Angsuran', 'Info Pinjaman'],
            'dilarang' => ['Verifikasi Draft', 'Kelola Pengguna', 'Input Draft', 'Kartu Piutang'],
        ],
    ];

    #[TestDox('UC-00 TC-00-01 Login dan autentikasi')]
    public function test_uc_00_tc_00_01_login_dan_autentikasi(): void
    {
        $this->browse(function (Browser $browser) {
            foreach (self::MENU_PERAN as $peran => $menu) {
                $this->loginForm($browser, self::AKUN[$peran])
                    ->assertPathIs('/dashboard')
                    ->assertSeeIn('#topbar-user-name-label', $this->akun($peran)->nama_user);

                foreach ($menu['tampil'] as $label) {
                    $browser->assertSeeIn('#app-sidebar', $label);
                }
                foreach ($menu['dilarang'] as $label) {
                    $browser->assertDontSeeIn('#app-sidebar', $label);
                }

                $this->logoutMenu($browser);
            }
        });
    }
}
