<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-10 | TC-10-02 (Positive) - Melihat dan mengunduh rencana potongan
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Rencana tampil dan file terunduh dengan isi sama seperti di layar.
 */
class TC_10_02Test extends SipintarDuskTestCase
{
    public function test_melihat_dan_mengunduh_rencana_potongan(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $r = $this->buatRencanaPotongan($pinjamanId);
        $nama = $this->namaNasabah(self::PEGAWAI);

        $this->browse(function (Browser $browser) use ($r, $nama) {
            // 2. Buka menu Rencana Potongan (halaman tidak memiliki filter bulan)
            $this->loginSebagai($browser, self::BND_SKL)
                ->click('a[href$="/bendahara/potongan"]')
                ->waitForLocation('/bendahara/potongan')
                ->assertSee('600.000');

            // 4. Unduh: ambil isi file CSV dari tombol Unduh
            $linkUnduh = "a[href$=\"/bendahara/potongan/download/{$r['rencana_id']}\"]";
            $href = $browser->attribute($linkUnduh, 'href');
            $csv = $browser->driver->executeAsyncScript(
                'const cb = arguments[arguments.length - 1];'
                . 'fetch(arguments[0]).then(r => r.text()).then(cb).catch(e => cb("ERROR " + e));',
                [$href]
            );

            // Rincian di layar
            $browser->click("#btn-eye-{$r['rencana_id']}")
                ->waitForTextIn('#bannerPegawaiTableBody', $nama)
                ->assertSeeIn('#bannerPegawaiTableBody', '600.000')
                ->screenshot('TC-10-02');

            // Isi file sama dengan yang tampil di layar
            $this->assertStringContainsString($nama, $csv);
            $this->assertStringContainsString('500000', $csv);  // pokok
            $this->assertStringContainsString('100000', $csv);  // jasa
            $this->assertStringContainsString('600000', $csv);  // total potongan
        });
    }
}
