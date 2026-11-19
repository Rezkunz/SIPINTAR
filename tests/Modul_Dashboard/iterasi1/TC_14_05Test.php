<?php

namespace Tests\Modul_Dashboard\iterasi1;

use Tests\Modul_Dashboard\DashboardDuskTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-05 (Positive) - Peminjam melihat jadwal angsuran
 * Aktor    : Nasabah (Pegawai) - pegawai@sipintar.test
 * Expected : Jadwal, status tiap angsuran, dan sisa piutang tampil benar.
 */
class TC_14_05Test extends DashboardDuskTestCase
{
    public function test_peminjam_melihat_jadwal_angsuran(): void
    {
        // Pinjaman aktif 5.000.000 / 10 bulan, 2 angsuran sudah dibayar
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            // 2. Buka menu Jadwal Angsuran
            $this->loginSebagai($browser, self::PEGAWAI)
                ->click('a[href$="/nasabah/angsuran"]')
                ->waitForLocation('/nasabah/angsuran')
                ->assertSee('4.000.000')    // sisa piutang
                ->assertSee('600.000')      // angsuran per bulan
                ->screenshot('TC-14-05');

            $status = $browser->script(
                'return [...document.querySelectorAll("span.badge-pill")].map(e => e.innerText.trim());'
            )[0];
            $this->assertSame(2, count(array_keys($status, 'Lunas')), 'Jumlah angsuran berstatus Lunas');
            $this->assertSame(8, count(array_keys($status, 'Belum')), 'Jumlah angsuran berstatus Belum');
        });
    }
}
