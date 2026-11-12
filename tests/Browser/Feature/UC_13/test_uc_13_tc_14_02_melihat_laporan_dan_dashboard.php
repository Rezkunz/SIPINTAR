<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-02 Melihat laporan dan dashboard
 * Test Case: Laporan gabungan (neraca)
 */
class test_uc_13_tc_14_02_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-02 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_02_melihat_laporan_dan_dashboard(): void
    {
        $this->transaksiTeller('1001-2026-00001', 100000, 'setoran');

        // Laporan manual: dihitung langsung dari tabel
        $tabunganSiswa = (float) DB::table('tblREKENING as r')->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('n.jenis_nasabah', 'non_pegawai')->sum('r.saldo');
        $tabunganGuru = (float) DB::table('tblREKENING as r')->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('n.jenis_nasabah', 'pegawai')->sum('r.saldo');
        $manual = [
            'Kas Tunai'                 => (float) DB::table('tblKAS')->where('jenis_kas', 'kas')->sum('saldo'),
            'Kas Bank'                  => (float) DB::table('tblKAS')->where('jenis_kas', 'bank')->sum('saldo'),
            'Piutang Pinjaman Pegawai'  => $this->hitungSisaPiutangManual(),
            'Tabungan Siswa'            => $tabunganSiswa,
            'Tabungan Guru / Karyawan'  => $tabunganGuru,
        ];

        $this->browse(function (Browser $browser) use ($manual) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->clickLink('Neraca & Gabungan')
                ->assertPathIs('/laporan/neraca');
            $this->pilihPeriode($browser, now()->month, now()->year);

            foreach ($manual as $pos => $nilai) {
                $this->assertStringContainsString(self::rp($nilai), $this->nilaiBaris($browser, $pos), "Pos {$pos} tidak sama dengan laporan manual");
            }
        });
    }
}
