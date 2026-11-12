<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-06 Melihat laporan dan dashboard
 * Test Case: Peminjam melihat jadwal angsuran
 */
class test_uc_13_tc_14_06_melihat_laporan_dan_dashboard extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-06 Melihat laporan dan dashboard')]
    public function test_uc_13_tc_14_06_melihat_laporan_dan_dashboard(): void
    {
        $nasabahId = $this->akun('pegawai')->nasabah_id;
        $pinjamanId = DB::table('tblPINJAMAN')->where('nasabah_id', $nasabahId)->where('status_pinjaman', 'cair')->value('pinjaman_id');
        $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->pluck('jadwal_angsuran_id');
        $lunas = DB::table('tblANGSURAN')->whereIn('jadwal_angsuran_id', $jadwal)->distinct()->count('jadwal_angsuran_id');
        $sisa = $this->hitungSisaPiutangManual($nasabahId);

        $this->browse(function (Browser $browser) use ($jadwal, $lunas, $sisa) {
            $this->loginForm($browser, self::AKUN['pegawai'])
                ->clickLink('Jadwal Angsuran')
                ->assertPathIs('/nasabah/angsuran')
                ->assertSee('SISA POKOK PIUTANG')
                ->assertSee(self::rp($sisa));

            $badge = $browser->script(
                "const rows = [...document.querySelectorAll('table tbody tr')];"
                . "return { lunas: rows.filter(r => /\\bLunas\\b/.test(r.innerText)).length, belum: rows.filter(r => /\\bBelum\\b/.test(r.innerText)).length };"
            )[0];

            $this->assertSame($lunas, $badge['lunas'], 'Jumlah angsuran berstatus Lunas tidak sesuai data');
            $this->assertSame($jadwal->count() - $lunas, $badge['belum'], 'Jumlah angsuran berstatus Belum tidak sesuai data');
        });
    }
}
