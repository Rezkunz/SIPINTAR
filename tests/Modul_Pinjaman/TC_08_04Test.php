<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-08 | TC-08-04 (Positive) - Melihat status pengajuan
 * Aktor    : Nasabah (Pegawai) - pegawai@sipintar.test
 * Expected : Status pengajuan tampil sesuai proses (Menunggu/Disetujui/Ditolak/Dicairkan).
 */
class TC_08_04Test extends SipintarDuskTestCase
{
    public function test_melihat_status_pengajuan(): void
    {
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            // Status: Menunggu
            $this->loginSebagai($browser, self::PEGAWAI)
                ->clickLink('Info Pinjaman')
                ->waitForLocation('/nasabah/pinjaman')
                ->assertSee('Menunggu Penilaian Kelayakan')
                ->assertSee('5.000.000')
                ->screenshot('TC-08-04');

            // Status: Disetujui (layak) mengikuti proses di Bank Mini
            $this->nilaiLayak($pinjamanId);
            $browser->refresh()->assertSee('Disetujui (Menunggu Pencairan)');
        });
    }
}
